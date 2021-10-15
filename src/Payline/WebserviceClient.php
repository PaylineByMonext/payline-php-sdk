<?php

namespace Payline;
use SoapClient;
use SoapVar;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;


class WebserviceClient
{
    CONST CALL_WITH_CURL = 'curl';

    CONST CALL_WITH_FILE_CONTENT = 'file_get_contents';

    CONST ERROR_CODE_TIMEOUT = 'TIMEOUT';
    //CONST ERROR_INTERNAL_SERVER_ERROR = 'Internal Server Error';

    /** @var array $endpointsUrls */
    private $endpointsUrls;

    /** @var bool $useFailvover */
    private $useFailvover = false;

    /**
     * Main Soap URL used for all SDK methods except method that can used failover endpoint ($servicesWithFailover)
     * @var string $sdkDefaultLocation
     */
    private $sdkDefaultLocation;

    /** @var string $sdkAPI */
    private $sdkAPI;

    /**
     * Soap Url used for method with failover ($servicesWithFailover)
     * @var $sdkFailoverCurrentLocation
     */
    private $sdkFailoverCurrentLocation;

    /**
     * Headers SOAP
     * @var array $sdkFailoverCurrentHeaders
     */
    private $sdkFailoverCurrentHeaders = [];

    /**
     * Soap Url to access endpoint directory
     * @var $endpointsDirectoryLocation
     */
    private $endpointsDirectoryLocation;


    /** @var string[] $servicesWithFailover */
    private $servicesWithFailover = array(
        'doAuthorization',
        'doReAuthorization',
        'doWebPayment',
        'getWebPaymentDetails',
        'doImmediateWalletPayment',
        'verifyEnrollment',
        'verifyAuthentication'
    );

    /** @var string[] $paylineErrorList */
    private $paylineErrorList = array(
        '04901',
        '02101'
    );



    /** @var string[] $httpErrorList */
    private $httpErrorList = array(
        '502',
        '503',
        '408',
        '504',
        '500',
        self::ERROR_CODE_TIMEOUT
    );

    /**
     *
     * @var string[]
     */
    private $timeoutErrorList = array(
        "Error Fetching http headers",
        "Could not connect to host"
    );

    //TODO: If one day we we have to test exeption error codes
    /** @var string[]  */
    private $exeptionErrorList = array(
    );

    /** @var array $failoverOptions */
    private $failoverOptions;

    /** @var  \Payline\Cache\CacheInterface $cachePool */
    private $cachePool;

    private $cachePoolsAvailable = ['file', 'apc'];

    private $tryNum = 0;

    private $lastCallData = [];

    /**
     * WebserviceClient constructor.
     * @param $wsdl
     * @param array|null $options
     * @throws \SoapFault
     */
    public function __construct($wsdl, array $options = null)
    {
        $this->sdkWsdl = $wsdl;
        $this->sdkOptions = $options;
    }


    /**
     *
     * Basic params
     * - disabled => true, false
     * - cache_pool => file (default), apc
     * - cache_file_path => directory path to store file cache
     * - cache_namespace
     * - cache_default_ttl
     * Class properties
     * - services_with_failover => servicesWithFailover
     * - payline_error_list => paylineErrorList
     * - http_error_list => httpErrorList
     * - timeout_error_list => timeoutErrorList
     *
     *
     * @param array $options
     * @return $this
     */
    public function setFailoverOptions(array $options)
    {
        //Search for class properties
        foreach ($options as $optionKey => $optionValue) {
            if(in_array($optionKey, array('services_with_failover', 'payline_error_list', 'http_error_list', 'timeout_error_list'))) {
                if($this->setWebserviceProperty($optionKey, $optionValue)) {
                    unset($options[$optionKey]);
                } else {
                    throw new \Exception(sprintf('Cannot set property "%s" via setFailoverOptions', $optionKey));
                }
            }
        }

        $this->failoverOptions = $options;
        return $this;
    }

    /**
     * @param $property
     * @param $value
     * @return bool
     */
    protected function setWebserviceProperty($property, $value)
    {
        $classProperty = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $property))));
        if(property_exists($this, $classProperty)) {
            $this->$classProperty = $value;
            return true;
        }
        return false;
    }


    /**
     * @param bool $use
     * @return $this
     */
    public function setUseFailover($use = true)
    {
        $this->useFailvover = (bool)$use;
        return $this;
    }

    /**
     * @param $location
     * @return $this
     */
    public function setSdkDefaultLocation($location)
    {
        $this->sdkDefaultLocation = $location;
        return $this;
    }

    /**
     * @param $api
     * @return $this
     */
    public function setSdkAPI($api)
    {
        $this->sdkAPI = $api;
        return $this;
    }

    /**
     * @param $location
     * @return $this
     */
    public function setEndpointsDirectoryLocation($location)
    {
        $this->endpointsDirectoryLocation = $location;
        return $this;
    }


    /**
     * @return int
     */
    public function getMinSoapTimeout()
    {
        $systemTimeout = defined('SOAP_CONNECTION_TIMEOUT') ? (int)SOAP_CONNECTION_TIMEOUT : 5;
        if(ini_get('default_socket_timeout')>0) {
            $systemTimeout = min($systemTimeout, (int)ini_get('default_socket_timeout'));
        }

        if(ini_get('max_execution_time')>0) {
            $systemTimeout = min($systemTimeout, (int)ini_get('max_execution_time'));
        }

        if(!empty($this->sdkOptions['connection_timeout'])) {
            $systemTimeout = min($systemTimeout, (int)$this->sdkOptions['connection_timeout']);
        }

        return (int)$systemTimeout;
    }

    /**
     * @param $method
     * @param $tryNum
     * @return SoapClient
     * @throws \SoapFault
     */
    protected function getClientSDK($method, $tryNum)
    {
        if(!$tryNum) {
            $this->lastCallData = [];
        }
        $sdkClient = false;
        if($this->useSercicesEndpointsFailover($method)) {
            if($location = $this->getFailoverServicesEndpoint($tryNum)) {
                $extraOptions = array();
                if($this->sdkFailoverCurrentHeaders) {
                    $extraOptions = array('stream_context_to_create' => array(
                        'http' => array(
                            'header' => $this->sdkFailoverCurrentHeaders)));

                }
                $extraOptions['exceptions'] = true;
                $extraOptions['trace'] = true;

                $this->sdkFailoverCurrentLocation = $location;
                $sdkClient = $this->buildClientSdk($location . $this->sdkAPI, $extraOptions);
            }

        } else {
            $sdkClient = $this->buildClientSdk($this->sdkDefaultLocation . $this->sdkAPI);
        }

        if(!$sdkClient) {
            throw new \Exception('Cannot build SDK Soap client');
        }

        return $sdkClient;
    }

    /**
     * @param $location
     * @param array $extraOptions
     * @return SoapClient
     * @throws \SoapFault
     */
    protected function buildClientSdk($location, $extraOptions = array())
    {
        $defaultOptions = array();
        $defaultOptions['style'] = defined('SOAP_DOCUMENT') ? SOAP_DOCUMENT : 2;
        $defaultOptions['use'] = defined('SOAP_LITERAL') ? SOAP_LITERAL : 2;
        $defaultOptions['connection_timeout'] = $this->getMinSoapTimeout();
        $defaultOptions['trace'] = false;

        $options = array_merge($defaultOptions, $this->sdkOptions);
        if(!empty($extraOptions)) {
            $options = $this->array_merge_recursive_distinct($options, $extraOptions);
        }

        if(isset($options['stream_context_to_create'])) {
            if(!empty($options['stream_context_to_create'])) {

                if(!empty($options['stream_context_to_create']['http']['header']) && is_array($options['stream_context_to_create']['http']['header'])) {
                    $httpHeader = array();
                    foreach ($options['stream_context_to_create']['http']['header'] as $headerKey =>$headerValue) {
                        $httpHeader[] = $headerKey . ': ' . $headerValue;
                    }
                    $options['stream_context_to_create']['http']['header'] = implode("\r\n", $httpHeader);
                }

                $options['stream_context'] = stream_context_create($options['stream_context_to_create']);
            }
            unset($options['stream_context_to_create']);
        }

        $sdkClient = new SoapClient($this->sdkWsdl, $options);
        $sdkClient->__setLocation($location);

        return $sdkClient;
    }


    /**
     * @return \Payline\Cache\CacheInterface
     */
    protected function getCachePool()
    {
        if(is_null($this->cachePool)) {
            $namespace = !empty($this->failoverOptions['cache_namespace']) ? $this->failoverOptions['cache_namespace'] : '';
            $ttl = !empty($this->failoverOptions['cache_default_ttl']) ? (int)$this->failoverOptions['cache_default_ttl'] : 0;

            $cachePool = !empty($this->failoverOptions['cache_pool']) ? $this->failoverOptions['cache_pool'] : 'file';
            switch ($cachePool) {
                case 'apc':
                    $version = !empty($this->failoverOptions['cache_apc_version']) ? $this->failoverOptions['cache_apc_version'] : null;
                    $this->cachePool = new \Payline\Cache\Apc($namespace, $ttl, $version);
                    break;
                default:
                    $directory = !empty($this->failoverOptions['cache_file_path']) ? $this->failoverOptions['cache_file_path'] : 'cache';
                    $this->cachePool = new \Payline\Cache\File($namespace, $ttl, $directory);
                    break;
            }
        }

        return $this->cachePool;
    }

    /**
     * @return bool
     */
    protected function getUseEndpointsDirectory()
    {
        return $this->useFailvover && !empty($this->endpointsDirectoryLocation) && $this->getCallEndpointsDirectoryMethod();
    }


    /**
     * @return false|string
     */
    protected function getCallEndpointsDirectoryMethod()
    {
        if(ini_get('allow_url_fopen') ) {
            return self::CALL_WITH_FILE_CONTENT;
        } elseif (extension_loaded('curl')) {
            return self::CALL_WITH_CURL;
        }
        return false;
    }


    /**
     * @return false|array
     */
    protected function getAllFailoverServicesEndpoint()
    {
        $endpointsUrls = array();
        $endpointsTTL = 60;

        if($this->getCachePool()->hasServicesEndpoints()) {
            $endpointsUrls = $this->getCachePool()->loadServicesEndpoints();
        } else {
            $method = $this->getCallEndpointsDirectoryMethod();
            $jsonContent = false;

            try {
                switch ($method) {
                    case self::CALL_WITH_CURL:
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL, $this->endpointsDirectoryLocation);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                        $jsonContent = curl_exec($ch);
                        curl_close($ch);
                        break;
                    case self::CALL_WITH_FILE_CONTENT:
                        $opts = array(
                            'http'=>array(
                                'method'=>"GET"
                            )
                        );
                        $context = stream_context_create($opts);
                        $jsonContent = file_get_contents($this->endpointsDirectoryLocation, false, $context);
                        break;
                    default:
                        break;
                }


                if(!empty($jsonContent)) {
                    $endpointData = json_decode($jsonContent, true);
                    if (!empty($endpointData['urls']) && is_array($endpointData['urls'])) {
                        $endpointsUrls = $endpointData['urls'];
                        foreach ($endpointsUrls as $endpointKey => $endpointUrl) {
                            $endpointsUrls[$endpointKey] = $endpointUrl . '/services/';
                        }
                        $endpointsTTL = $endpointData['ttl'];
                    }
                }

            } catch ( \Exception $e) {

            }

            if(!empty($endpointsUrls)) {
                $this->getCachePool()->saveServicesEndpoints($endpointsUrls, $endpointsTTL);
            } else {
                $endpointsUrls = array($this->sdkDefaultLocation);
            }

        }

        return $endpointsUrls;
    }


    /**
     * @return false|string
     */
    protected function getFailoverServicesEndpoint($tryNumber)
    {
        if(is_null($this->endpointsUrls)) {
            $this->endpointsUrls = $this->getAllFailoverServicesEndpoint();
        }
        $serviceIndex = $tryNumber -1;

        return !empty($this->endpointsUrls[$serviceIndex]) ? $this->endpointsUrls[$serviceIndex] : false;
    }

    /**
     * @param string $error
     * @param int $tryNumber
     * @param int $callDuration
     * @return bool
     */
    protected function switchSoapContext($error = '', $nextTryNum=0, $callDuration = 0)
    {
        $location = $this->getFailoverServicesEndpoint($nextTryNum);
        if($location) {
            if ($nextTryNum>0) {
                $headers = array();
                $headers['x-failover-cause'] = $error;
                $headers['x-failover-duration'] = $callDuration;
                $headers['x-failover-origin'] = $this->sdkFailoverCurrentLocation;
                $headers['x-failover-index'] = $nextTryNum -1;
            }

            $this->sdkFailoverCurrentHeaders  = $headers;
            return true;
        }

        return false;
    }


    /**
     * @param string $name
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args = null)
    {
        $this->tryNum++;
        $callStart = microtime(true);
        $WSRequest = isset($args[0]) ? $args[0] : null;

        try {
            $sdkClient = $this->getClientSDK($method, $this->tryNum);
            $response =  $sdkClient->$method($WSRequest);
            $this->saveCallData($sdkClient);
            if($this->switchSoapContextFailoverOnPaylineError($method, $this->tryNum+1, $callStart, $response)) {
                return $this->__call($method, $args);
            }
            return $response;
        } catch ( \SoapFault $fault) {
            $this->saveCallData($sdkClient);
            /*
            var_dump('-------- failover SoapFault',
                'faultcode=> ' . $fault->faultcode,
                ' faultstring=> ' . $fault->faultstring,
                ' RequestHeaders: ',
                $sdkClient->__getLastRequestHeaders(),
                ' ResponseHeaders: ',
                $sdkClient->__getLastResponseHeaders(),
                '-----------------------');
            */

            $identifiedSoapError = false;
            $lastResponseHeader = $sdkClient->__getLastResponseHeaders();
            if(empty($lastResponseHeader)
                && in_array($fault->faultstring, $this->timeoutErrorList)
            ) {
                $identifiedSoapError = self::ERROR_CODE_TIMEOUT;
            } elseif (preg_match("/HTTP\/\d\.\d\s*\K[\d]+/", $lastResponseHeader,$match)) {
                $identifiedSoapError = $match[0];
            }

            if($identifiedSoapError && $this->switchSoapContextFailoverOnFault($method, $this->tryNum+1, $callStart, $identifiedSoapError)) {
                return $this->__call($method, $args);
            }

            throw $fault;
        } catch ( \Exception $e) {
            /*
            var_dump('-------- failover Exception: ' . $e->getCode() . ' ' . $e->getMessage(),
                $sdkClient->__getLastRequestHeaders(),
                $sdkClient->__getLastResponseHeaders(),
                '-----------------------');
            if($this->useSercicesEndpointsFailoverOnException($method, $this->tryNum +1, $callStart, $e)) {
                return $this->__call($method, $args);
            }
            */

            throw $e;
        }
    }


    protected function saveCallData($sdkClient) {
        $this->lastCallData[$this->tryNum] = array(
            'Request' => $sdkClient->__getLastRequest(),
            'RequestHeaders' => $sdkClient->__getLastRequestHeaders(),
            'HttpHeaders' => $this->sdkFailoverCurrentHeaders,
            'Response' => $sdkClient->__getLastResponse(),
            'ResponseHeaders' => $sdkClient->__getLastResponseHeaders()

        );
    }



    /**
     * @param $method
     * @return bool
     */
    protected function useSercicesEndpointsFailover($method) {
        return $this->getUseEndpointsDirectory() && in_array($method, $this->servicesWithFailover);
    }


    /**
     * @param $method
     * @param $nextTryNum
     * @param $callStart
     * @param $response
     * @return bool
     */
    protected function switchSoapContextFailoverOnPaylineError($method, $nextTryNum, $callStart, $response) {
        $callDuration = round(1000 * (microtime(true) - $callStart));

        if( $this->useSercicesEndpointsFailover($method) &&
            in_array($response->result->code, $this->paylineErrorList)) {

            $error = 'APP_' . $response->result->code;

            return $this->switchSoapContext($error, $nextTryNum, $callDuration);
        }
        return false;
    }


    /**
     * @param $method
     * @param $nextTryNum
     * @param $callStart
     * @param \SoapFault $fault
     * @return bool
     */
    //protected function switchSoapContextFailoverOnFault($method, $nextTryNum, $callStart, \SoapFault $fault) {
    protected function switchSoapContextFailoverOnFault($method, $nextTryNum, $callStart, $errorCode) {
        $callDuration = round(1000 * (microtime(true) - $callStart));
        if($this->useSercicesEndpointsFailover($method) &&
            in_array($errorCode, $this->httpErrorList)) {

            if(self::ERROR_CODE_TIMEOUT == $errorCode) {
                $error = $errorCode;
            } else {
                $error = 'HTTP_' . $errorCode;
            }
            return $this->switchSoapContext($error, $nextTryNum, $callDuration);
        }
        return false;
    }



    /**
     * @deprecated Not used
     *
     * @param $method
     * @param $nextTryNum
     * @param $callStart
     * @param \SoapFault $fault
     * @return bool
     */
    protected function switchSoapContextFailoverOnException($method, $nextTryNum, $callStart, \Exception $e) {
        $callDuration = round(1000 * (microtime(true) - $callStart));
        if($this->useSercicesEndpointsFailover($method) &&
            in_array($e->getCode(), $this->exeptionErrorList)) {

            $error = 'EXCEPTION';

            return $this->switchSoapContext($error, $nextTryNum, $callDuration);
        }
        return false;
    }



    /**
     *
     * @see https://www.php.net/manual/en/function.array-merge-recursive.php#92195
     *
     *
     * array_merge_recursive does indeed merge arrays, but it converts values with duplicate
     * keys to arrays rather than overwriting the value in the first array with the duplicate
     * value in the second array, as array_merge does. I.e., with array_merge_recursive,
     * this happens (documented behavior):
     *
     * array_merge_recursive(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('org value', 'new value'));
     *
     * array_merge_recursive_distinct does not change the datatypes of the values in the arrays.
     * Matching keys' values in the second array overwrite those in the first array, as is the
     * case with array_merge, i.e.:
     *
     * array_merge_recursive_distinct(array('key' => 'org value'), array('key' => 'new value'));
     *     => array('key' => array('new value'));
     *
     * Parameters are passed by reference, though only for performance reasons. They're not
     * altered by this function.
     *
     * @param array $array1
     * @param array $array2
     * @return array
     * @author Daniel <daniel (at) danielsmedegaardbuus (dot) dk>
     * @author Gabriel Sobrinho <gabriel (dot) sobrinho (at) gmail (dot) com>
     */
    protected function array_merge_recursive_distinct ( array &$array1, array &$array2 )
    {
        $merged = $array1;

        foreach ( $array2 as $key => &$value )
        {
            if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
            {
                $merged [$key] = $this->array_merge_recursive_distinct ( $merged [$key], $value );
            }
            else
            {
                $merged [$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * @param string $key
     * @return array|false|mixed
     */
    public function retrieveSoapLastContent()
    {
        return $this->lastCallData;
    }

}