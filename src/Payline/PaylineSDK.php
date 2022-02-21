<?php
/*
 * This file is part of the Payline package.
 *
 * (c) Monext <http://www.monext.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Payline;

use Payline\MerchantAuthentication;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Payline\Browser;
use SoapClient;
use SoapVar;
use Payline\Payment;
use Payline\Order;
use Payline\OrderDetail;
use Payline\Card;
use Payline\PaymentData;
use Payline\Buyer;
use Payline\Address;
use Payline\AddressOwner;
use Payline\Owner;
use Payline\Authentication3DSecure;
use Payline\BillingRecordForUpdate;
use Payline\Wallet;
use Payline\Authorization;
use Payline\Creditor;
use Payline\Cheque;
use Payline\Recurring;
use Payline\ThreeDSInfo;
use Payline\Sdk;

class PaylineSDK
{

    /**
     * Payline release corresponding to this version of the package
     * @see https://docs.payline.com/display/DT/API+version+history
     */
    const SDK_RELEASE = 'PHP SDK 4.69';

    /**
     * development environment flag
     */
    const ENV_DEV = "DEV";

    /**
     * integration environment flag
     */
    const ENV_INT = "INT";

    /**
     * homologation environment flag
     */
    const ENV_HOMO = "HOMO";

    /**
     * homologation environment flag - uses certificate-based authentication
     */
    const ENV_HOMO_CC = "HOMO_CC";

    /**
     * production environment flag
     */
    const ENV_PROD = "PROD";

    /**
     * production environment flag - uses certificate-based authentication
     */
    const ENV_PROD_CC = "PROD_CC";

    /**
     * name of Payline DirectPaymentAPI
     */
    const DIRECT_API = 'DirectPaymentAPI';

    /**
     * name of Payline ExtendedAPI
     */
    const EXTENDED_API = 'ExtendedAPI';

    /**
     * name of Payline WebPaymentAPI
     */
    const WEB_API = 'WebPaymentAPI';

    /**
     * namespace used in web services desciptor
     */
    const PAYLINE_NAMESPACE = 'http://obj.ws.payline.experian.com';

    /**
     * SOAP name of authorization object
     */
    const SOAP_AUTHORIZATION = 'authorization';

    /**
     * SOAP name of card object
     */
    const SOAP_CARD = 'card';

    /**
     * SOAP name of order object
     */
    const SOAP_ORDER = 'order';

    /**
     * SOAP name of orderDetail object
     */
    const SOAP_ORDERDETAIL = 'orderDetail';

    /**
     * SOAP name of payment object
     */
    const SOAP_PAYMENT = 'payment';

    /**
     * SOAP name of paymentData object
     */
    const SOAP_PAYMENT_DATA = 'paymentData';

    /**
     * SOAP name of privateData object
     */
    const SOAP_PRIVATE_DATA = 'privateData';

    /**
     * SOAP name of buyer object
     */
    const SOAP_BUYER = 'buyer';

    /**
     * SOAP name of owner object
     */
    const SOAP_OWNER = 'owner';

    /**
     * SOAP name of address object
     */
    const SOAP_ADDRESS = 'address';

    /**
     * SOAP name of addressOwner object
     */
    const SOAP_ADDRESS_OWNER = 'addressOwner';

    /**
     * SOAP name of authentication3DSecure object
     */
    const SOAP_AUTHENTICATION_3DSECURE = 'authentication3DSecure';

    /**
     * SOAP name of bankAccountData object
     */
    const SOAP_BANK_ACCOUNT_DATA = 'bankAccountData';

    /**
     * SOAP name of cheque object
     */
    const SOAP_CHEQUE = 'cheque';

    /**
     * SOAP name of creditor object
     */
    const SOAP_CREDITOR = 'creditor';

    /**
     * SOAP name of billingRecordForUpdate object
     */
    const SOAP_BILLING_RECORD_FOR_UPDATE = 'billingRecordForUpdate';

    /**
     * SOAP name of recurring object
     */
    const SOAP_RECURRING = 'recurring';

    /**
     * SOAP name of subMerchant object
     */
    const SOAP_SUBMERCHANT = 'subMerchant';

    /**
     * SOAP name of threeDSInfo object
     */
    const SOAP_THREEDSINFO = 'threeDSInfo';

    /**
     * SOAP name of browser object
     */
    const SOAP_BROWSER = 'browser';

    /**
     * SOAP name of sdk object
     */
    const SOAP_SDK = 'sdk';

    /**
     * SOAP name of merchantAuthentication object
     */
    const SOAP_MERCHANT_AUTHENTICATION = 'merchantAuthentication';

    /**
     * SOAP name of wallet object
     */
    const SOAP_WALLET = 'wallet';

    /**
     * directory services endpoint in production environment
     */
    const HOMO_SERVICES_ENDPOINT = 'https://homologation-payment.payline.com/services/servicesendpoints/SOAP';

    /**
     * directory services endpoint in development environment
     */
    const PROD_SERVICES_ENDPOINT = 'https://payment.payline.com/services/servicesendpoints/SOAP';

    /**
     * web services endpoint in development environment
     */
    const DEV_ENDPOINT = 'https://ws.dev.payline.com/V4/services/';

    /**
     * web services endpoint in integration environment
     */
    const INT_ENDPOINT = 'https://ws.int.payline.com/V4/services/';

    /**
     * standard web services endpoint in homologation environment
     */
    const HOMO_ENDPOINT = 'https://homologation.payline.com/V4/services/';

    /**
     *  certificate-based authentication web services endpoint in homologation environment
     */
    const HOMO_CC_ENDPOINT = 'https://homologation-cc.payline.com/V4/services/';

    /**
     * standard web services endpoint in production environment
     */
    const PROD_ENDPOINT = 'https://services.payline.com/V4/services/';

    /**
     * certificate-based authentication web services endpoint in production environment
     */
    const PROD_CC_ENDPOINT = 'https://services-cc.payline.com/V4/services/';

    /**
     * URL of getToken servlet, used by AJAX API, in development environment
     */
    const DEV_GET_TOKEN_SERVLET = "https://webpayment.dev.payline.com/webpayment/getToken";

    /**
     * URL of getToken servlet, used by AJAX API, in integration environment
     */
    const INT_GET_TOKEN_SERVLET = "https://webpayment.int.payline.com/webpayment/getToken";

    /**
     * URL of getToken servlet, used by AJAX API, in homologation environment
     */
    const HOMO_GET_TOKEN_SERVLET = "https://homologation-webpayment.payline.com/webpayment/getToken";

    /**
     * URL of getToken servlet, used by AJAX API, in production environment
     */
    const PROD_GET_TOKEN_SERVLET = "https://webpayment.payline.com/webpayment/getToken";

    /**
     * Widget JavaScript in development environment
     */
    const DEV_WDGT_JS = "https://webpayment.dev.payline.com/payline-widget/scripts/widget-min.js";

    /**
     * Widget JavaScript in homologation environment
     */
    const HOMO_WDGT_JS = "https://homologation-payment.payline.com/scripts/widget-min.js";

    /**
     * Widget JavaScript in production environment
     */
    const PROD_WDGT_JS = "https://payment.payline.com/scripts/widget-min.js";

    /**
     * Widget css in development environment
     */
    const DEV_WDGT_CSS = "https://webpayment.dev.payline.com/payline-widget/styles/widget-min.css";

    /**
     * Widget css in homologation environment
     */
    const HOMO_WDGT_CSS = "https://homologation-payment.payline.com/styles/widget-min.css";

    /**
     * Widget css in production environment
     */
    const PROD_WDGT_CSS = "https://payment.payline.com/styles/widget-min.css";

    /**
     * homologation administration center URL
     */
    const HOMO_CA = 'https://homologation-admin.payline.com';

    /**
     * administration center URL
     */
    const PROD_CA = 'https://admin.payline.com';

    /**
     * error code/shortMessage returned when Payline can't be reached
     */
    const ERR_CODE = 'XXXXX';
    const ERR_SHORT_MESSAGE = 'ERROR';

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var $loggerPath
     */
    protected $loggerPath;


    /**
     * tool / e-commerce module using this library
     */
    protected $usedBy = null;

    /**
     * array containing order details
     */
    protected $orderDetails;

    /**
     * array containing private data
     */
    protected $privateData;

    /**
     * array containing parent-child nodes associations
     */
    protected $parentNode = array(
        'cards'                    => 'cardsList',
        'billingRecord'            => 'billingRecordList',
        'walletId'                 => 'walletIdList',
        'transaction'              => 'transactionList',
        'pointOfSell'              => 'listPointOfSell',
        'contract'                 => 'contracts',
        'customPaymentPageCode'    => 'customPaymentPageCodeList',
        'function'                 => 'functions',
        'details'                  => 'details',
        'privateData'              => 'privateDataList',
        'associatedTransactions'   => 'associatedTransactionsList',
        'statusHistory'            => 'statusHistoryList',
        'paymentAdditional'        => 'paymentAdditionalList',
        'CustomerTrans'            => 'CustomerTransHist',
        'PaymentMeansTrans'        => 'PaymentMeansTransHist',
        'AlertsTrans'              => 'AlertsTransHist'
    );

    protected $servicesEndpoint;

    protected $soapclientOptions = array();

    protected $failoverOptions = array();

    protected $lastSoapCallData = array();

    protected $logLevel = \Monolog\Logger::INFO;

    /**
     * PaylineSDK class constructor
     *
     * @param string $merchant_id
     *            the merchant identifier
     * @param string $access_key
     *            the access key generated in Payline Administration Center
     * @param string $proxy_host
     *            host of your proxy (set null if no proxy)
     * @param string $proxy_port
     *            port used by your proxy (set null if no proxy)
     * @param string $proxy_login
     *            login required by your proxy (set null if no proxy)
     * @param string $proxy_password
     *            password required by your proxy (set null if no proxy)
     * @param string $environment
     *            target Payline environment : set PaylineSDK::ENV_HOMO for homologation, PaylineSDK::ENV_PROD for production
     * @param string $pathLog
     *            path to your custom log folder, must end by directory separator. If null, default logs folder is used. Default : null
     * @param int $logLevel
     *            \Monolog\Logger log level. Default : Logger::INFO
     * @param Logger $externalLogger
     *            \Monolog\Logger instance, used by PaylineSDK but external to it
     */
    public function __construct($merchant_id, $access_key, $proxy_host, $proxy_port, $proxy_login, $proxy_password, $environment, $pathLog = null, $logLevel = Logger::INFO, $externalLogger = null, $defaultTimezone = "Europe/Paris")
    {

        $this->logLevel = $logLevel;

        if (is_int($merchant_id)) {
            $merchant_id = (string) $merchant_id;
        }

        $logfileDate = (new \DateTime('now', new \DateTimeZone($defaultTimezone)))->format('Y-m-d');
        if (empty($pathLog) || !is_dir($pathLog)) {
            $pathLog = realpath(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;
        }

        $this->loggerPath = $pathLog;
        if ($externalLogger) {
            $this->logger = $externalLogger;
        } else {
            $this->logger = new Logger('PaylineSDK');
        }


        $this->logger->pushHandler(new StreamHandler($pathLog . $logfileDate . '.log', $logLevel)); // set default log folder

        $this->logger->info('__construct', array(
            'merchant_id' => $this->hideChars($merchant_id, 6, 1),
            'access_key' => $this->hideChars($access_key, 1, 3),
            'proxy_host' => $proxy_host,
            'proxy_port' => $proxy_port,
            'proxy_login' => $proxy_login,
            'proxy_password' => $this->hideChars($proxy_password, 1, 1),
            'environment' => $environment
        ));
        $this->soapclientOptions = array();
        $this->soapclientOptions['login'] = $merchant_id;
        $this->soapclientOptions['password'] = $access_key;
        if ($proxy_host != '') {
            $this->soapclientOptions['proxy_host'] = $proxy_host;
            $this->soapclientOptions['proxy_port'] = $proxy_port;
            $this->soapclientOptions['proxy_login'] = $proxy_login;
            $this->soapclientOptions['proxy_password'] = $proxy_password;
        }
        $plnInternal = false;
        if (strcmp($environment, self::ENV_HOMO) == 0) {
            $this->webServicesEndpoint = self::HOMO_ENDPOINT;
            $this->servicesEndpoint = self::HOMO_SERVICES_ENDPOINT . '/' . $merchant_id;
        } elseif (strcmp($environment, self::ENV_HOMO_CC) == 0) {
            $this->webServicesEndpoint = self::HOMO_CC_ENDPOINT;
        } elseif (strcmp($environment, self::ENV_PROD) == 0) {
            $this->webServicesEndpoint = self::PROD_ENDPOINT;
            $this->servicesEndpoint = self::PROD_SERVICES_ENDPOINT . '/' . $merchant_id;
        } elseif (strcmp($environment, self::ENV_PROD_CC) == 0) {
            $this->webServicesEndpoint = self::PROD_CC_ENDPOINT;
        } elseif (strcmp($environment, self::ENV_DEV) == 0) {
            $this->webServicesEndpoint = self::DEV_ENDPOINT;
            $plnInternal = true;
        } elseif (strcmp($environment, self::ENV_INT) == 0) {
            $this->webServicesEndpoint = self::INT_ENDPOINT;
            $plnInternal = true;
        } else {
            $this->webServicesEndpoint = false; // Exception is raised in PaylineSDK::webServiceRequest
            $this->servicesEndpoint = false;
        }
        $this->soapclientOptions['trace'] = true;
        $this->soapclientOptions['stream_context_to_create'] = array();

        if ($plnInternal) {
            $this->soapclientOptions['stream_context_to_create']['ssl'] = array(
                'verify_peer' => false,
                'verify_peer_name' => false
            );
        }

        $this->orderDetails = array();
        $this->privateData = array();
    }


    /**
     * Set option passed to SoapClient
     *
     * @param $key
     * - style
     * - use
     * - connection_timeout
     * - trace
     *
     *
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    public function setSoapOptions($key, $value = null)
    {
        if(is_string($key)) {
            $this->soapclientOptions[$key] = $value;
        } else {
            throw new \Exception('Cannot set Soap option');
        }
        return $this;
    }



    /**
     * @param $key
     * - disabled => true, false
     * - cache_pool => file (default), apc
     * - cache_file_path => directory path to store file cache
     * - cache_namespace
     * - cache_default_ttl     *
     *
     * @param null $value
     * @return $this
     * @throws \Exception
     */
    public function setFailoverOptions($key, $value = null)
    {
        if(is_string($key)) {
            $this->failoverOptions[$key] = $value;
        } else {
            throw new \Exception('Cannot set Failover option');
        }
        return $this;
    }


    /**
     * @return $this
     */
    public function reset()
    {
        $this->lastSoapCallData = array();
        $this->resetOrderDetails();
        $this->resetPrivateData();
        return $this;
    }



    public function resetFailoverOptions()
    {
        $this->failoverOptions = array();
        return $this;
    }


    /**
     * reset OrderDetails
     */
    public function resetOrderDetails()
    {
        $this->orderDetails = array();
        return $this;
    }


    /**
     * reset Private Data
     */
    public function resetPrivateData()
    {
        $this->privateData = array();
        return $this;
    }

    /**
     * get Private Data
     */
    public function privateDataList()
    {
        return $this->privateData;
    }

    /**
     * build Payment instance from $array and make SoapVar object for payment
     *
     * @param array $array
     *            the array keys are listed in Payment class
     * @return SoapVar representation of Payment instance
     */
    protected function payment(array $array)
    {
        return $this->buildSoapObject($array, new Payment(), self::SOAP_PAYMENT);
    }

    /**
     * build PaymentData instance from $array and make SoapVar object for payment
     *
     * @param array $array
     *            the array keys are listed in PaymentData class
     * @return SoapVar representation of PaymentData instance
     */
    protected function paymentData(array $array)
    {
        return $this->buildSoapObject($array, new PaymentData(), self::SOAP_PAYMENT_DATA);
    }

    /**
     * build Order instance from $array and make SoapVar object for order
     *
     * @param array $array
     *            the array keys are listed in Order CLASS.
     * @return SoapVar representation of Order instance
     */
    protected function order(array $array)
    {
        $order = $this->fillObject($array, new Order());
        // insert orderDetails
        $order->details = $this->orderDetails;
        return new \SoapVar($order, SOAP_ENC_OBJECT, self::SOAP_ORDER, self::PAYLINE_NAMESPACE);
    }

    /**
     * build Card instance from $array and make SoapVar object for card
     *
     * @param array $array
     *            the array keys are listed in Card CLASS.
     * @return SoapVar representation of Card instance
     */
    protected function card(array $array)
    {
        $card = $this->fillObject($array, new Card());
        $card->paymentData = null;
        if (isset($array['paymentData'])) {
            $card->paymentData = $this->paymentData($array['paymentData']);
        }
        return new \SoapVar($card, SOAP_ENC_OBJECT, self::SOAP_CARD, self::PAYLINE_NAMESPACE);
    }

    /**
     * build Buyer instance from $array, $shippingAdress and $billingAddress, and make SoapVar object for buyer
     *
     * @param array $array
     *            the array keys are listed in Buyer CLASS.
     * @param array $shippingAdress
     *            the array keys are listed in Address CLASS.
     * @param array $billingAddress
     *            the array keys are listed in Address CLASS.
     * @param array $merchantAuthentication
     *            the array keys are listed in MerchantAuthentication CLASS.
     * @return SoapVar representation of Buyer instance
     */
    protected function buyer(array $array, array $shippingAdress = array(), array $billingAddress = array(), array $merchantAuthentication = array())
    {
        $buyerArray = !empty($array['buyer']) ? $array['buyer'] : $array;
        $shippingAdress = !empty($array['shippingAddress']) ? $array['shippingAddress'] : $shippingAdress;
        $billingAddress = !empty($array['billingAddress']) ? $array['billingAddress'] : $billingAddress;
        $merchantAuthentication = !empty($array['merchantAuthentication']) ? $array['merchantAuthentication'] : $merchantAuthentication;



        $buyer = $this->fillObject($buyerArray, new Buyer());
        $buyer->shippingAdress = $this->address($shippingAdress);
        $buyer->billingAddress = $this->address($billingAddress);
        $buyer->merchantAuthentication = $this->merchantAuthentication($merchantAuthentication);
        return new \SoapVar($buyer, SOAP_ENC_OBJECT, self::SOAP_BUYER, self::PAYLINE_NAMESPACE);
    }

    /**
     * build Address instance from $array and make SoapVar object for address.
     *
     * @param array $array
     *            the array keys are listed in Address CLASS.
     * @return SoapVar representation of Address instance
     */
    protected function address(array $array)
    {
        return $this->buildSoapObject($array, new Address(), self::SOAP_ADDRESS);
    }

    /**
     * build AddressOwner instance from $array and make SoapVar object for addressOwner.
     *
     * @param array $array
     *            the array keys are listed in AddressOwner CLASS.
     * @return SoapVar representation of AddressOwner instance
     */
    protected function addressOwner(array $array)
    {
        return $this->buildSoapObject($array, new AddressOwner(), self::SOAP_ADDRESS_OWNER);
    }

    /**
     * build Owner instance from $array and make SoapVar object for owner
     *
     * @param array $array
     *            the array keys are listed in Owner CLASS.
     * @param array $addressOwner
     *            the array keys are listed in AddressOwner CLASS.
     * @return SoapVar representation of Owner instance
     *
     */
    protected function owner(array $array, array $addressOwner = array())
    {
        $ownerArray = !empty($array['owner']) ? $array['owner'] : $array;
        $addressOwner = !empty($array['ownerAddress']) ? $array['ownerAddress'] : $addressOwner;

        if ($ownerArray !== null) {
            $owner = $this->fillObject($ownerArray, new Owner());
            $owner->billingAddress = $this->addressOwner($addressOwner);
            return new \SoapVar($owner, SOAP_ENC_OBJECT, self::SOAP_OWNER, self::PAYLINE_NAMESPACE);
        } else {
            return null;
        }
    }

    /**
     * build Authentication3DSecure instance from $array and make SoapVar object for authentication3DSecure
     *
     * @param array $array
     *            the array keys are listed in Authentication3DSecure CLASS.
     * @return SoapVar representation of Authentication3DSecure instance
     */
    protected function authentication3DSecure(array $array)
    {
        return $this->buildSoapObject($array, new Authentication3DSecure(), self::SOAP_AUTHENTICATION_3DSECURE);
    }

    /**
     * build BankAccountData instance from $array and make SoapVar object for authentication3DSecure
     *
     * @param array $array
     *            the array keys are listed in BankAccountData CLASS.
     * @return SoapVar representation of BankAccountData instance
     */
    protected function bankAccountData(array $array)
    {
        return $this->buildSoapObject($array, new BankAccountData(), self::SOAP_BANK_ACCOUNT_DATA);
    }

    /**
     * build BillingRecordForUpdate instance from $array and make SoapVar object for billingRecordForUpdate
     *
     * @param array $array
     *            the array keys are listed in BillingRecordForUpdate CLASS.
     * @return SoapVar representation of BillingRecordForUpdate instance
     */
    protected function billingRecordForUpdate(array $array)
    {
        return $this->buildSoapObject($array, new BillingRecordForUpdate(), self::SOAP_BILLING_RECORD_FOR_UPDATE);
    }

    /**
     * build Wallet instance from $array and make SoapVar object for wallet
     *
     * @param array $array
     *            the array keys are listed in Wallet CLASS.
     * @param array $address
     *            the array keys are listed in Address CLASS.
     * @param array $card
     *            the array keys are listed in Card CLASS.
     * @return SoapVar representation of Wallet instance
     */
    protected function wallet(array $array, array $address, array $card)
    {
        $wallet = $this->fillObject($array, new Wallet());
        $wallet->shippingAddress = $this->address($address);
        $wallet->card = $this->card($card);
        return new \SoapVar($wallet, SOAP_ENC_OBJECT, self::SOAP_WALLET, self::PAYLINE_NAMESPACE);
    }

    /**
     * build Authorization instance from $array and make SoapVar object for authorization
     *
     * @param array $array
     *            the array keys are listed in Authorization CLASS.
     * @return SoapVar representation of Authorization instance
     *
     */
    protected function authorization(array $array)
    {
        return $this->buildSoapObject($array, new Authorization(), self::SOAP_AUTHORIZATION);

    }

    /**
     * build Creditor instance from $array and make SoapVar object for creditor
     *
     * @param array $array
     *            the array keys are listed in Creditor CLASS.
     * @return SoapVar representation of Creditor instance
     */
    protected function creditor(array $array)
    {
        return $this->buildSoapObject($array, new Creditor(), self::SOAP_CREDITOR);
    }

    /**
     * build Cheque instance from $array and make SoapVar object for cheque
     *
     * @param array $array
     *            the array keys are listed in Cheque CLASS.
     * @return SoapVar representation of Cheque instance
     */
    protected function cheque(array $array)
    {
        return $this->buildSoapObject($array, new Cheque(), self::SOAP_CHEQUE);
    }

    /**
     * build Recurring instance from $array and make SoapVar object for recurring
     *
     * @param array $array
     *            the array keys are listed in Recurring CLASS.
     * @return SoapVar representation of Recurring instance
     */
    protected function recurring(array $array)
    {
        return $this->buildSoapObject($array, new Recurring(), self::SOAP_RECURRING);
    }

    /**
     * build SubMerchant instance from $array and make SoapVar object for subMerchant
     *
     * @param array $array
     *            the array keys are listed in SubMerchant CLASS.
     * @return SoapVar representation of SubMerchant instance
     */
    protected function subMerchant(array $array)
    {
        return $this->buildSoapObject($array, new SubMerchant(), self::SOAP_SUBMERCHANT);
    }

    /**
     * build ThreeDSInfo instance froù $array and make SoapVar object for threeDSInfo
     *
     * @param array $array
     *      the array keys liste in ThreeDSInfo CLASS.
     * @param array $arrayBrowser
     *      the array keys liste in Browser CLASS.
     * @param array $arraySdk
     *      the array keys liste in Sdk CLASS.
     * @return  SoapVar representation of ThreeDSInfo instance
     */
    protected function threeDSInfo(array $array, array $arrayBrowser, array $arraySdk)
    {
        $threeDSInfo = $this->fillObject($array, new ThreeDSInfo());
        $threeDSInfo->sdk = $this->sdk($arraySdk);
        $threeDSInfo->browser = $this->browser($arrayBrowser);
        return new \SoapVar($threeDSInfo, SOAP_ENC_OBJECT, self::SOAP_THREEDSINFO, self::PAYLINE_NAMESPACE);
    }

    /**
     * build Browser instance from $array and make SoapVar object for browser
     *
     * @param array $array
     *      the array keys list in Browser CLASS.
     * @return  SoapVar representation of Browser instance
     */
    protected function browser(array $array) {
        return $this->buildSoapObject($array, new Browser(), self::SOAP_BROWSER);
    }

    /**
     * build MerchantAuthentication instance from $array and make SoapVar object for merchantAuthentication
     *
     * @param array $array
     *      the array keys list in MerchantAuthentication CLASS.
     * @return  SoapVar representation of MerchantAuthentication instance
     */
    protected function merchantAuthentication(array $array) {
        return $this->buildSoapObject($array, new MerchantAuthentication(), self::SOAP_MERCHANT_AUTHENTICATION);
    }



    /**
     * build Sdk instance from $array and make SoapVar object for sdk
     *
     * @param array $array
     *      the array keys list in Sdk CLASS.
     * @return  SoapVar representation of Sdk instance
     */
    protected function sdk(array $array) {
        return $this->buildSoapObject($array, new Sdk(), self::SOAP_SDK);
    }

    /**
     * Hide characters in a string
     *
     * @param String $inString
     *            the string to hide
     * @param int $n1
     *            number of characters shown at the begining of the string
     * @param int $n2
     *            number of characters shown at end begining of the string
     */
    protected function hideChars($inString, $n1, $n2)
    {
        $inStringLength = strlen($inString);
        if ($inStringLength < ($n1 + $n2)) {
            return $inString;
        }
        $outString = substr($inString, 0, $n1);
        $outString .= substr("********************", 0, $inStringLength - ($n1 + $n2));
        $outString .= substr($inString, - ($n2));
        return $outString;
    }

    /**
     *
     * @param String $nodeName name of a node in a web service response
     * @param String $parentName name of its parent
     * @return boolean whether $nodeName is child from a list or not
     */
    protected function isChildFromList($nodeName,$parentName){
        if(array_key_exists($nodeName, $this->parentNode)){
            if(strcmp($this->parentNode[$nodeName],$parentName) == 0){
                return true;
            }
        }
        return false;
    }

    /**
     * make an array from a payline server response object.
     *
     * @param object $node
     *            response node from payline web service
     * @param string $parent
     *            name of the node's parent
     * @return array representation of the object
     */
    protected function responseToArray($node, $parent = null)
    {
        $array = array();
        foreach ($node as $k => $v) {
            if ($this->isChildFromList($k, $parent)) { // current value is a list
                if ($v instanceof \Countable && count($v) == 1 && $k != '0') { // a list with 1 element. It's returned with a 0-index
                    $array[$k][0] = PaylineSDK::responseToArray($v, $k);
                } elseif (is_object($v) || is_array($v)) { // a list with more than 1 element
                    $array[$k] = PaylineSDK::responseToArray($v, $k);
                } else {
                    $array[$k] = $v;
                }
            } else {
                if (is_object($v) || is_array($v)) {
                    $array[$k] = PaylineSDK::responseToArray($v, $k);
                } else {
                    $array[$k] = $v;
                }
            }
        }
        return $array;
    }

    /**
     * Adds indexes with empty values to the web services request array, in order to prevent SOAP format exception
     *
     * @param array $array
     *            associative array containing web services parameters
     */
    private static function formatRequest(&$array)
    {
        if (!isset($array['buyer'])) {
            $array['buyer'] = array();
        }
        if (!isset($array['3DSecure'])) {
            $array['3DSecure'] = array();
        }
        if (!isset($array['bankAccountData'])) {
            $array['bankAccountData'] = array();
        }
        if (!isset($array['cancelURL']) || !strlen($array['cancelURL'])) {
            $array['cancelURL'] = null;
        }
        if (!isset($array['notificationURL']) || !strlen($array['notificationURL'])) {
            $array['notificationURL'] = null;
        }
        if (!isset($array['returnURL']) || !strlen($array['returnURL'])) {
            $array['returnURL'] = null;
        }
        if (!isset($array['languageCode']) || !strlen($array['languageCode'])) {
            $array['languageCode'] = null;
        }
        if (!isset($array['securityMode']) || !strlen($array['securityMode'])) {
            $array['securityMode'] = null;
        }
        if (!isset($array['billingAddress'])) {
            $array['billingAddress'] = array();
        }
        if (!isset($array['shippingAddress'])) {
            $array['shippingAddress'] = array();
        }
        if (!isset($array['owner'])) {
            $array['owner'] = array();
        }
        if (!isset($array['ownerAddress'])) {
            $array['ownerAddress'] = array();
        }
        if (!isset($array['contracts']) || !strlen($array['contracts'][0]) || !is_array($array['contracts'])) {
            $array['contracts'] = null;
        }
        if (!isset($array['secondContracts']) || !strlen($array['secondContracts'][0]) || !is_array($array['secondContracts'])) {
            $array['secondContracts'] = null;
        }
        if (!isset($array['walletContracts']) || !strlen($array['walletContracts'][0]) || !is_array($array['walletContracts'])) {
            $array['walletContracts'] = null;
        }
        if (!isset($array['customPaymentPageCode']) || !strlen($array['customPaymentPageCode'])) {
            $array['customPaymentPageCode'] = null;
        }
        if (!isset($array['customPaymentTemplateURL']) || !strlen($array['customPaymentTemplateURL'])) {
            $array['customPaymentTemplateURL'] = null;
        }
        if (!isset($array['recurring'])) {
            $array['recurring'] = null;
        }
        if (!isset($array['orderRef']) || !strlen($array['orderRef'])) {
            $array['orderRef'] = null;
        }
        if (!isset($array['orderDate']) || !strlen($array['orderDate'])) {
            $array['orderDate'] = null;
        }
        if (!isset($array['walletIds']) || !strlen($array['walletIds'][0] || !is_array($array['walletIds']))) {
            $array['walletIds'] = null;
        }
        if (!isset($array['merchantName'])) {
            $array['merchantName'] = null;
        }
        if (!isset($array['miscData'])) {
            $array['miscData'] = null;
        }
        if (!isset($array['subMerchant'])) {
            $array['subMerchant'] = array();
        }
        if (!isset($array['asynchronousRetryTimeout'])) {
            $array['asynchronousRetryTimeout'] = null;
        }
        if (!isset($array['merchantAuthentication'])) {
            $array['merchantAuthentication'] = array();
        }
        if (!isset($array['browser'])) {
            $array['browser'] = array();
        }
        if (!isset($array['sdk'])) {
            $array['sdk'] = array();
        }
        if (!isset($array['threeDSInfo'])) {
            $array['threeDSInfo'] = array();
        }
        if (!isset($array['updatePersonalDetails'])) {
            $array['updatePersonalDetails'] = null;
        }
        if (!isset($array['linkedTransactionId'])) {
            $array['linkedTransactionId'] = null;
        }
        if (!isset($array['transactionID'])) {
            $array['transactionID'] = null;
        }
        if (!isset($array['currency'])) {
            $array['currency'] = null;
        }
        if (!isset($array['amount'])) {
            $array['amount'] = null;
        }
        if (!isset($array['sequenceNumber'])) {
            $array['sequenceNumber'] = null;
        }
        if (!isset($array['merchantScore'])) {
            $array['merchantScore'] = null;
        }
        if (!isset($array['skipSmartDisplay'])) {
            $array['skipSmartDisplay'] = null;
        }

        if (!isset($array['walletId'])) {
            $array['walletId'] = null;
        }

        if (!isset($array['travelFileNumber'])) {
            $array['travelFileNumber'] = null;
        }



        //todo preparation of the key refacto
        $mappingKeys = array(
            '3DSecure' => 'authentication3DSecure',
            'contracts' => 'selectedContractList',
            'secondContracts' => 'secondSelectedContractList',
            'walletContracts' => 'contractNumberWalletList',
            'walletIds' => 'walletIdList',
        );
        foreach ($mappingKeys as $enterKey => $exitKey) {
            if(isset($array[$enterKey])) {
                $array[$exitKey] = $array[$enterKey];
            }
        }
    }


    /**
     * Complete $WSRequest according wsdl definition
     * (much more efficient than parsing with xpath)
     *
     * @param array $array
     * @param $WSRequest
     * @param $PaylineAPI
     * @param $Method
     * @return mixed
     */
    protected function completeWSRequest(array $array, $WSRequest, $PaylineAPI, $Method)
    {
        try {
            $client = new SoapClient(__DIR__ . '/wsdl/' . $PaylineAPI . '.wsdl');
            $types = $client->__getTypes();
            foreach ($types as $type) {
                if (strpos($type, 'struct ' . $Method . 'Request') === 0) {
                    if (preg_match_all('/ (\w+) (\w+)/', $type, $match)) {
                        foreach ($match[2] as $elementIndex => $elementKey) {
                            if (isset($WSRequest[$elementKey])) {
                                continue;
                            }
                            if ($match[1][$elementIndex] == "string") {
                                $elementValue = isset($array[$elementKey]) ? $array[$elementKey] : null;
                                $WSRequest[$elementKey] = $elementValue;
                            } elseif (method_exists($this, $elementKey)) {
                                $WSRequest[$elementKey] = $this->$elementKey($array);
                            }
                        }
                    }
                }
            }
        } catch (\SoapFault $fault) {
            $this->logger->error('Exception occured while completeWSRequest for ' . $Method, array(
                'code'     => $fault->getCode(),
                'message'  => $fault->getMessage()
            ));
        }

        return $WSRequest;
    }


    /**
     * Create the SoapClient instance and make the web service call
     *
     * @param array $array
     *            the associative array passed to the public function
     * @param array $WSRequest
     *            the SOAP-formated request
     * @param string $PaylineAPI
     *            the Payline API to be called
     * @param string $Method
     *            the name of the web service
     */
    protected function webServiceRequest(array $array, array $WSRequest, $PaylineAPI, $Method)
    {
        $WSRequest = $this->completeWSRequest($array, $WSRequest, $PaylineAPI, $Method);
        $logRequest = array();
        $logResponse = array(
            'result.code' => null
        );
        try {
            if(!$this->webServicesEndpoint){
                throw new \Exception('Endpoint error (check `environment` parameter of PaylineSDK constructor)');
            }

            $this->soapclientOptions['stream_context_to_create']['http'] = array(
                'user_agent' => "PHP",
                'header' => array('version' => $this->usedBy . ' - ' . self::SDK_RELEASE)
            );

            $client = new WebserviceClient(__DIR__ . '/wsdl/' . $PaylineAPI . '.wsdl', $this->soapclientOptions);

            $useFailover = empty($this->failoverOptions['disabled']);

            $failoverOptions = array_merge($this->failoverOptions, array('logger_path'=>$this->loggerPath));
            $client->setSdkDefaultLocation($this->webServicesEndpoint)
                ->setSdkAPI($PaylineAPI)
                ->setUseFailover($useFailover)
                ->setEndpointsDirectoryLocation($this->servicesEndpoint)
                ->setFailoverOptions($failoverOptions);

            $WSRequest['version'] = isset($array['version']) && strlen($array['version']) ? $array['version'] : '';
            $WSRequest['media'] = isset($array['media']) && strlen($array['media']) ? $array['media'] : '';

            switch ($Method) {
                case 'createWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['wallet']['walletId'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = self::responseToArray($client->createWallet($WSRequest));
                    break;
                case 'createWebWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['buyer']['walletId']
                    );
                    $response = self::responseToArray($client->createWebWallet($WSRequest));
                    if ($response['result']['code'] == '00000') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'updatePaymentRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId ' => $array['paymentRecordId']
                    );
                    $response = PaylineSDK::responseToArray($client->updatePaymentRecord($WSRequest));
                    break;
                case 'getBillingRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId' => $array['paymentRecordId'],
                        'billingRecordId' => $array['billingRecordId']
                    );
                    $response = self::responseToArray($client->getBillingRecord($WSRequest));
                    break;
                case 'updateBillingRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId' => $array['paymentRecordId'],
                        'billingRecordId' => $array['billingRecordId']
                    );
                    $response = self::responseToArray($client->updateBillingRecord($WSRequest));
                    break;
                case 'disablePaymentRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId' => $array['paymentRecordId']
                    );
                    $response = self::responseToArray($client->disablePaymentRecord($WSRequest));
                    break;
                case 'disableWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletIdList' => implode(';', $array['walletIds'])
                    );
                    $response = self::responseToArray($client->disableWallet($WSRequest));
                    break;
                case 'doAuthorization':
                    $logRequest = array(
                        'order.ref' => $array['order']['ref'],
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'payment.amount' => $array['payment']['amount']
                    );

                    $response = self::responseToArray($client->doAuthorization($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doCapture':
                    $logRequest = array(
                        'transactionID' => $array['transactionID'],
                        'payment.amount' => $array['payment']['amount']
                    );
                    $response = self::responseToArray($client->doCapture($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doCredit':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'order.ref' => $array['order']['ref'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = self::responseToArray($client->doCredit($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doDebit':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'order.ref' => $array['order']['ref'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = self::responseToArray($client->doDebit($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doImmediateWalletPayment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'walletId' => $array['walletId'],
                        'order.ref' => $array['order']['ref']
                    );
                    $response = self::responseToArray($client->doImmediateWalletPayment($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doReAuthorization':
                    $logRequest = array(
                        'transactionID' => $array['transactionID'],
                        'amount' => $array['payment']['amount']
                    );
                    $response = self::responseToArray($client->doReAuthorization($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doRecurrentWalletPayment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'walletId' => $array['walletId'],
                        'order.ref' => $array['order']['ref']
                    );
                    $response = self::responseToArray($client->doRecurrentWalletPayment($WSRequest));
                    if ($response['result']['code'] == '02500') {
                        $logResponse['paymentRecordId'] = $response['paymentRecordId'];
                    }
                    break;
                case 'doRefund':
                    $logRequest = array(
                        'transactionID' => $array['transactionID'],
                        'payment.amount' => $array['payment']['amount']
                    );
                    $response = self::responseToArray($client->doRefund($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doReset':
                    $logRequest = array(
                        'transactionID' => $array['transactionID']
                    );
                    $response = self::responseToArray($client->doReset($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'doScheduledWalletPayment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'walletId' => $array['walletId'],
                        'order.ref' => $array['order']['ref']
                    );
                    $response = self::responseToArray($client->doScheduledWalletPayment($WSRequest));
                    if ($response['result']['code'] == '02500') {
                        $logResponse['paymentRecordId'] = $response['paymentRecordId'];
                    }
                    break;
                case 'doScoringCheque':
                    $response = self::responseToArray($client->doScoringCheque($WSRequest));
                    break;
                case 'doWebPayment':
                    $logRequest = array(
                        'order.ref' => $array['order']['ref']
                    );
                    $response = self::responseToArray($client->doWebPayment($WSRequest));
                    if ($response['result']['code'] == '00000') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'enableWallet':
                    $logRequest = array(
                        'walletId' => $array['walletId']
                    );
                    $response = self::responseToArray($client->enableWallet($WSRequest));
                    break;
                case 'getAlertDetails':
                    $logRequest = array(
                        'alertId' => $array['AlertId'],
                        'transactionId' => $array['TransactionId']
                    );
                    $response = self::responseToArray($client->getAlertDetails($WSRequest));
                    break;
                case 'getBalance':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'cardID' => $this->hideChars($array['cardID'], 4, 4)
                    );
                    $response = self::responseToArray($client->getBalance($WSRequest));
                    break;
                case 'getCards':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['walletId'],
                        'cardInd' => $array['cardInd']
                    );
                    $response = self::responseToArray($client->getCards($WSRequest));
                    break;
                case 'getEncryptionKey':
                    $response = self::responseToArray($client->getEncryptionKey($WSRequest));
                    break;
                case 'getMerchantSettings':
                    $response = self::responseToArray($client->getMerchantSettings($WSRequest));
                    break;
                case 'getPaymentRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId' => $array['paymentRecordId']
                    );
                    $response = self::responseToArray($client->getPaymentRecord($WSRequest));
                    break;
                case 'getToken':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'cardNumber' => $this->hideChars($array['cardNumber'], 4, 4)
                    );
                    $response = self::responseToArray($client->getToken($WSRequest));
                    if ($response['result']['code'] == '02500') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'getTransactionDetails':
                    $logRequest = array(
                        'transactionId' => $array['transactionId']
                    );
                    $response = self::responseToArray($client->getTransactionDetails($WSRequest));
                    break;
                case 'getWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['walletId'],
                        'cardInd' => $array['cardInd']
                    );
                    $response = self::responseToArray($client->getWallet($WSRequest));
                    break;
                case 'getWebPaymentDetails':
                    $logRequest = array(
                        'token' => $array['token']
                    );
                    $response = self::responseToArray($client->getWebPaymentDetails($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'getWebWallet':
                    $logRequest = array(
                        'token' => $array['token']
                    );
                    $response = self::responseToArray($client->getWebWallet($WSRequest));
                    if (isset($response['wallet']['card'])) {
                        $logResponse['wallet.card.number'] = $this->hideChars($response['wallet']['card']['number'], 4, 4);
                    }
                    break;
                case 'manageWebWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'buyer.walletId' => $array['buyer']['walletId']
                    );
                    $response = self::responseToArray($client->manageWebWallet($WSRequest));
                    if ($response['result']['code'] == '00000') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'transactionsSearch':
                    $logRequest = array();
                    foreach ($array as $key => $value) {
                        if ($value != '') {
                            $logRequest[$key] = $value;
                        }
                    }
                    $response = self::responseToArray($client->transactionsSearch($WSRequest));
                    break;
                case 'unBlock':
                    $logRequest = array(
                        'transactionID' => $array['transactionID']
                    );
                    $response = self::responseToArray($client->unBlock($WSRequest));
                    break;
                case 'updateWallet':
                    $logRequest = array(
                        'walletId' => $array['wallet']['walletId']
                    );
                    $response = self::responseToArray($client->updateWallet($WSRequest));
                    break;
                case 'updateWebWallet':
                    $logRequest = array(
                        'walletId' => $array['walletId']
                    );
                    $response = self::responseToArray($client->updateWebWallet($WSRequest));
                    if ($response['result']['code'] == '00000') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'verifyAuthentication':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'card.number' => !empty($array['card']) && is_array($array['card']) ? $this->hideChars($array['card']['number'], 4, 4) : '?'
                    );
                    $response = self::responseToArray($client->verifyAuthentication($WSRequest));
                    break;
                case 'verifyEnrollment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = self::responseToArray($client->verifyEnrollment($WSRequest));
                    break;
                case 'doBankTransfer':
                    $logRequest = array(
                        'orderID' => $array['orderID'],
                        'creditor.bic' => $this->hideChars($array['creditor']['bic'], 4, 1),
                        'creditor.iban' => $this->hideChars($array['creditor']['iban'], 8, 1)
                    );
                    $response = self::responseToArray($client->doBankTransfer($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'isRegistered':
                    $logRequest = array(
                        'order.ref' => $array['order']['ref'],
                        'payment.contractNumber' => $array['payment']['contractNumber']
                    );
                    $response = self::responseToArray($client->isRegistered($WSRequest));
                    $logResponse['token'] = $response['token'];
                    break;
                case 'doAuthorizationRedirect':
                    $logRequest = array(
                        'order.ref' => $array['order']['ref'],
                        'payment.contractNumber' => $array['payment']['contractNumber']
                    );
                    $response = self::responseToArray($client->doAuthorizationRedirect($WSRequest));
                    break;
            }
            $logResponse['result.code'] = $response['result']['code'];
            $this->logger->info($Method . 'Request', $logRequest);
            $this->logger->info($Method . 'Response', $logResponse);
            if ($this->logLevel == \Monolog\Logger::DEBUG && $this->soapclientOptions['trace'] === true) {
                foreach ($this->getSoapLastContent(null, false) as $callNum=>$callData) {
                    foreach ($callData as $callKey => $callValue) {
                        $this->logger->debug($Method . ', Last' . $callKey . ': ' . $callValue);
                    }
                }
            }
            return $response;
        } catch (\Exception $e) {
            $this->logger->info($Method . 'Request', $logRequest);
            $this->logger->error('Exception occured at ' . $Method . ' call', array(
                'code'     => $e->getCode(),
                'message'  => $e->getMessage(),
                'endpoint' => $this->webServicesEndpoint . $PaylineAPI
            ));
            $ERROR                               = array();
            $ERROR['result']['code']             = self::ERR_CODE;
            $ERROR['result']['longMessage']      = $e->getMessage();
            $ERROR['result']['shortMessage']     = self::ERR_SHORT_MESSAGE;
            $ERROR['result']['partnerCode']      = null;
            $ERROR['result']['partnerCodeLabel'] = null;
            return $ERROR;
        } finally {
            $this->lastSoapCallData = $client->retrieveSoapLastContent();
        }
    }

    /**
     * Sets the name of the tool using this library (Magento for example...)
     * Info is sent in HTTP header, an retrieved in "Technical monitoring of web services" screen of administration center
     *
     * @param string $toolName
     *            name of the tool using this library
     */
    public function usedBy($toolName)
    {
        $this->usedBy = $toolName;
    }

    /**
     * @return Logger \Monolog\Logger instance
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Adds details about an order item
     *
     * @param array $newOrderDetail
     *            associative array containing details about an order item
     */
    public function addOrderDetail(array $newOrderDetail)
    {
        $this->orderDetails[] = $this->buildSoapObject($newOrderDetail, new OrderDetail(), self::SOAP_ORDERDETAIL);
    }

    /**
     * Adds a privateData element
     *
     * @param array $array
     *            an array containing two indexes : key and value
     *
     */
    public function addPrivateData(array $array)
    {
        $this->privateData[] = $this->buildSoapObject($array, new PrivateData());
    }

    /*
     * *************************************************************************
     *
     * DirectPaymentAPI
     *
     * *************************************************************************
     */

    /**
     * calls doAuthorization web service
     *
     * @param array $array
     *            associative array containing doAuthorization parameters
     */
    public function doAuthorization(array $array)
    {
        $this->formatRequest($array);
        $threeDSInfo = array_key_exists('threeDSInfo', $array) ? $this->threeDSInfo($array['threeDSInfo'], $array['browser'], $array['sdk']) : null;

        $WSRequest = array(
            'transient'                 => isset($array['transient']) ? $array['transient'] : null,
            'payment'                   => $this->payment($array['payment']),
            'bankAccountData'           => $this->bankAccountData($array['bankAccountData']),
            'card'                      => $this->card($array['card']),
            'order'                     => $this->order($array['order']),
            'buyer'                     => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                     => $this->owner($array['owner'], $array['ownerAddress']),
            'privateDataList'           => $this->privateData,
            'authentication3DSecure'    => $this->authentication3DSecure($array['3DSecure']),
            'subMerchant'               => $this->subMerchant($array['subMerchant']),
            'asynchronousRetryTimeout'  => $array['asynchronousRetryTimeout'],
            'linkedTransactionId'       => $array['linkedTransactionId'],
            'threeDSInfo'               => $threeDSInfo,
            'travelFileNumber'          => $array['travelFileNumber'],
            'recurring'                 => $array['recurring']
        );



        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }

        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doAuthorization');
    }


    /**
     * calls doCapture web service
     *
     * @param array $array
     *            associative array containing doCapture parameters
     */
    public function doCapture(array $array)
    {
        $WSRequest = array(
            'transactionID'   => $array['transactionID'],
            'payment'         => $this->payment($array['payment']),
            'privateDataList' => $this->privateData,
            'sequenceNumber'  => $array['sequenceNumber']
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doCapture');
    }

    /**
     * calls doReAuthorization web service
     *
     * @param array $array
     *            associative array containing doReAuthorization parameters
     */
    public function doReAuthorization(array $array)
    {
        $WSRequest = array(
            'transactionID'   => $array['transactionID'],
            'payment'         => $this->payment($array['payment']),
            'order'           => $this->order($array['order']),
            'privateDataList' => $this->privateData
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doReAuthorization');
    }

    /**
     * calls doDebit web service
     *
     * @param array $array
     *            associative array containing doDebit parameters
     */
    public function doDebit(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'                => $this->payment($array['payment']),
            'card'                   => $this->card($array['card']),
            'order'                  => $this->order($array['order']),
            'privateDataList'        => $this->privateData,
            'buyer'                  => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                  => $this->owner($array),
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
            'authorization'          => $this->authorization($array['authorization']),
            'subMerchant'            => $this->subMerchant($array['subMerchant'])
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doDebit');
    }

    /**
     * calls doRefund web service
     *
     * @param array $array
     *            associative array containing doRefund parameters
     */
    public function doRefund($array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'transactionID'   => $array['transactionID'],
            'payment'         => $this->payment($array['payment']),
            'comment'         => $array['comment'],
            'privateDataList' => $this->privateData,
            'details'         => $this->orderDetails,
            'sequenceNumber'  => $array['sequenceNumber'],
            'miscData'        => $array['miscData']
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doRefund');
    }

    /**
     * calls doReset web service
     *
     * @param array $array
     *            associative array containing doReset parameters
     */
    public function doReset(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'transactionID' => $array['transactionID'],
            'comment'       => $array['comment'],
            'amount'       => $array['amount'],
            'currency'       => $array['currency'],
            'privateDataList' => $this->privateData,
            'sequenceNumber'       => $array['sequenceNumber'],
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doReset');
    }





    /**
     * calls doCredit web service
     *
     * @param array $array
     *            associative array containing doCredit parameters
     */
    public function doCredit(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'         => $this->payment($array['payment']),
            'card'            => $this->card($array['card']),
            'buyer'           => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'           => $this->owner($array),
            'privateDataList' => $this->privateData,
            'order'           => $this->order($array['order']),
            'comment'         => $array['comment'],
            'subMerchant'     => $this->subMerchant($array['subMerchant'])
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doCredit');
    }

    /**
     * calls createWallet web service
     *
     * @param array $array
     *            associative array containing createWallet parameters
     */
    public function createWallet(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'contractNumber'           => $array['contractNumber'],
            'wallet'                   => $this->wallet($array['wallet'], $array['address'], $array['card']),
            'buyer'                    => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                    => $this->owner($array['owner'], $array['ownerAddress']),
            'privateDataList'          => $this->privateData,
            'authentication3DSecure'   => $this->authentication3DSecure($array['3DSecure']),
            'contractNumberWalletList' => $array['walletContracts'],
            'transactionID'            => $array['transactionID']
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'createWallet');
    }





    /**
     * calls updateWallet web service
     *
     * @param array $array
     *            associative array containing updateWallet parameters
     */
    public function updateWallet(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'contractNumber'           => $array['contractNumber'],
            'cardInd'                  => $array['cardInd'],
            'wallet'                   => $this->wallet($array['wallet'], $array['address'], $array['card']),
            'buyer'                    => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                    => $this->owner($array['owner'], $array['ownerAddress']),
            'privateDataList'          => $this->privateData,
            'authentication3DSecure'   => $this->authentication3DSecure($array['3DSecure']),
            'contractNumberWalletList' => $array['walletContracts']
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'updateWallet');
    }

    /**
     * calls getWallet web service
     *
     * @param array $array
     *            associative array containing getWallet parameters
     */
    public function getWallet(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'getWallet');
    }

    /**
     * calls getCards web service
     *
     * @param array $array
     *            associative array containing getCards parameters
     */
    public function getCards(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'getCards');
    }

    /**
     * calls disableWallet web service
     *
     * @param array $array
     *            associative array containing disableWallet parameters
     */
    public function disableWallet(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'walletIdList'   => $array['walletIds'],
            'cardInd'        => $array['cardInd']
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'disableWallet');
    }

    /**
     * calls enableWallet web service
     *
     * @param array $array
     *            associative array containing enableWallet parameters
     */
    public function enableWallet(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'enableWallet');
    }

    /**
     * calls doImmediateWalletPayment web service
     *
     * @param array $array
     *            associative array containing doImmediateWalletPayment parameters
     */
    public function doImmediateWalletPayment(array $array)
    {
        $this->formatRequest($array);
        $threeDSInfo = array_key_exists('threeDSInfo', $array) ? $this->threeDSInfo($array['threeDSInfo'], $array['browser'], $array['sdk']) : null;

        $WSRequest = array(
            'payment'                => $this->payment($array['payment']),
            'order'                  => $this->order($array['order']),
            'buyer'                  => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'walletId'               => isset($array['walletId']) ? $array['walletId'] : null,
            'cardInd'                => isset($array['cardInd']) ? $array['cardInd'] : null,
            'cvx'                    => isset($array['walletCvx']) ? $array['walletCvx'] : null,
            'privateDataList'        => $this->privateData,
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
            'subMerchant'            => $this->subMerchant($array['subMerchant']),
            'threeDSInfo'               => $threeDSInfo,
            'travelFileNumber'          => $array['travelFileNumber'],
        );

        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doImmediateWalletPayment');
    }

    /**
     * calls doScheduledWalletPayment web service
     *
     * @param array $array
     *            associative array containing doScheduledWalletPayment parameters
     */
    public function doScheduledWalletPayment(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'         => $this->payment($array['payment']),
            'orderRef'        => $array['orderRef'],
            'orderDate'       => $array['orderDate'],
            'scheduledDate'   => $array['scheduledDate'],
            'walletId'        => $array['walletId'],
            'cardInd'         => $array['cardInd'],
            'order'           => $this->order($array['order']),
            'privateDataList' => $this->privateData,
            'subMerchant'     => $this->subMerchant($array['subMerchant']),
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
        );
        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doScheduledWalletPayment');
    }

    /**
     * calls doRecurrentWalletPayment web service
     *
     * @param array $array
     *            associative array containing doRecurrentWalletPayment parameters
     */
    public function doRecurrentWalletPayment(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'         => $this->payment($array['payment']),
            'orderRef'        => $array['orderRef'],
            'orderDate'       => $array['orderDate'],
            'scheduledDate'   => $array['scheduledDate'],
            'walletId'        => $array['walletId'],
            'cardInd'         => $array['cardInd'],
            'privateDataList' => $this->privateData,
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
            'order'           => $this->order($array['order'])
        );
        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doRecurrentWalletPayment');
    }

    /**
     * calls getPaymentRecord web service
     *
     * @param array $array
     *            associative array containing getPaymentRecord parameters
     */
    public function getPaymentRecord(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'getPaymentRecord');
    }

    /**
     * calls disablePaymentRecord web service
     *
     * @param array $array
     *            associative array containing disablePaymentRecord parameters
     */
    public function disablePaymentRecord(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'disablePaymentRecord');
    }

    /**
     * calls verifyEnrollment web service
     *
     * @param array $array
     *            associative array containing verifyEnrollment parameters
     */
    public function verifyEnrollment(array $array)
    {
        $this->formatRequest($array);
        $order = array_key_exists('order', $array) ? $this->order($array['order']) : null;
        $buyer = array_key_exists('buyer', $array) ? $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']) : null;
        $subMerchant = array_key_exists('subMerchant', $array) ? $this->subMerchant($array['subMerchant']) : null;
        $threeDSInfo = array_key_exists('threeDSInfo', $array) ? $this->threeDSInfo($array['threeDSInfo'], $array['browser'], $array['sdk']) : null;
        $merchantScore = array_key_exists('merchantScore', $array) ? $array['merchantScore'] :  null;
        $merchantURL = array_key_exists('merchantURL', $array) ? $array['merchantURL'] :  null;
        $merchantCountryCode = array_key_exists('merchantCountryCode', $array) ? $array['merchantCountryCode'] :  null;
        $WSRequest = array(
            'card'              => $this->card($array['card']),
            'payment'           => $this->payment($array['payment']),
            'orderRef'          => $array['orderRef'],
            'mdFieldValue'      => isset($array['mdFieldValue']) ? $array['mdFieldValue'] : null,
            'userAgent'         => isset($array['userAgent']) ? $array['userAgent'] : null,
            'walletId'          => $array['walletId'],
            'walletCardInd'     => isset($array['walletCardInd']) ? $array['walletCardInd'] : null,
            'merchantName'      => $array['merchantName'],
            'merchantURL'         => $merchantURL,
            'merchantCountryCode' => $merchantCountryCode,
            'returnURL'         => $array['returnURL'],
            'order'             => $order,
            'buyer'             => $buyer,
            'subMerchant'       => $subMerchant,
            'threeDSInfo'       => $threeDSInfo,
            'merchantScore'     => $merchantScore,
            'privateDataList'   => $this->privateData
        );
        if (isset($array['generateVirtualCvx'])) {
            $WSRequest['generateVirtualCvx'] = $array['generateVirtualCvx'];
        }
        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'verifyEnrollment');
    }

    /**
     * calls verifyAuthentication web service
     *
     * @param array $array
     *            associative array containing verifyAuthentication parameters
     */
    public function verifyAuthentication(array $array)
    {
        $WSRequest = array(
            'contractNumber'    => $array['contractNumber'],
            'pares'             => $array['pares'],
            'md'                => $array['md'],
            'card'              => $this->card(!empty($array['card']) && is_array($array['card']) ? $array['card'] : array()),
            'privateDataList'   => $this->privateData
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'verifyAuthentication');
    }

    /**
     * calls doScoringCheque web service
     *
     * @param array $array
     *            associative array containing doScoringCheque parameters
     */
    public function doScoringCheque(array $array)
    {
        $WSRequest = array(
            'payment'         => $this->payment($array['payment']),
            'cheque'          => $this->cheque($array['cheque']),
            'order'           => $this->order($array['order']),
            'privateDataList' => $this->privateData
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doScoringCheque');
    }

    /**
     * calls getEncryptionKey web service
     *
     * @param array $array
     *            associative array containing getEncryptionKey parameters
     */
    public function getEncryptionKey(array $array)
    {
        $WSRequest = array();
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'getEncryptionKey');
    }

    /**
     * calls getMerchantSettings web service
     *
     * @param array $array
     *            associative array containing getMerchantSettings parameters
     */
    public function getMerchantSettings(array $array)
    {
        $WSRequest = array();
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'getMerchantSettings');
    }

    /**
     * calls getBalance web service
     *
     * @param array $array
     *            associative array containing getBalance parameters
     */
    public function getBalance(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'getBalance');
    }

    /**
     * calls getToken web service
     *
     * @param array $array
     *            associative array containing getToken parameters
     */
    public function getToken(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'getToken');
    }

    /**
     * calls unBlock web service
     *
     * @param array $array
     *            associative array containing getBalance parameters
     */
    public function unBlock(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'unBlock');
    }

    /**
     * calls updatePaymentRecord web service
     *
     * @param array $array
     *            associative array containing updatePaymentRecord parameters
     */
    public function updatePaymentRecord(array $array)
    {
        $WSRequest = array(
            'contractNumber'  => $array['contractNumber'],
            'paymentRecordId' => $array['paymentRecordId'],
            'recurring'       => $this->recurring($array['recurring'])
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'updatePaymentRecord');
    }

    /**
     * calls getBillingRecord web service
     *
     * @param array $array
     *            associative array containing getBillingRecord parameters
     */
    public function getBillingRecord(array $array)
    {
        return $this->webServiceRequest($array, $array, self::DIRECT_API, 'getBillingRecord');
    }

    /**
     * calls updateBillingRecord web service
     *
     * @param array $array
     *            associative array containing updateBillingRecord parameters
     */
    public function updateBillingRecord(array $array)
    {
        $WSRequest = array(
            'contractNumber'         => $array['contractNumber'],
            'paymentRecordId'        => $array['paymentRecordId'],
            'billingRecordId'        => $array['billingRecordId'],
            'billingRecordForUpdate' => $this->billingRecordForUpdate($array['billingRecordForUpdate'])
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'updateBillingRecord');
    }

    /**
     * calls doBankTransfer web service
     *
     * @param array $array
     *            associative array containing doBankTransfer parameters
     */
    public function doBankTransfer(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'       => $this->payment($array['payment']),
            'creditor'      => $this->creditor($array['creditor']),
            'comment'       => $array['comment'],
            'transactionID' => $array['transactionID'],
            'orderID'       => $array['orderID'],
            'privateDataList' => $this->privateData
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'doBankTransfer');
    }

    /**
     * calls isRegistered web service
     *
     * @param array $array
     *            associative array containing isRegistered parameters
     */
    public function isRegistered(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'           => $this->payment($array['payment']),
            'order'             => $this->order($array['order']),
            'privateDataList'   => $this->privateData,
            'buyer'             => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'miscData'          => $array['miscData']
        );
        return $this->webServiceRequest($array, $WSRequest, self::DIRECT_API, 'isRegistered');
    }

    /*
     * *************************************************************************
     *
     * WebPaymentAPI
     *
     * *************************************************************************
     */

    /**
     * calls doWebPayment web service
     *
     * @param array $array
     *            associative array containing doWebPayment parameters
     */
    public function doWebPayment(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'                    => $this->payment($array['payment']),
            'returnURL'                  => $array['returnURL'],
            'cancelURL'                  => $array['cancelURL'],
            'order'                      => $this->order($array['order']),
            'notificationURL'            => $array['notificationURL'],
            'selectedContractList'       => $array['contracts'],
            'secondSelectedContractList' => $array['secondContracts'],
            'privateDataList'            => $this->privateData,
            'languageCode'               => $array['languageCode'],
            'customPaymentPageCode'      => $array['customPaymentPageCode'],
            'buyer'                      => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                      => $this->owner($array['owner'], $array['ownerAddress']),
            'securityMode'               => $array['securityMode'],
            'recurring'                  => $array['recurring'],
            'customPaymentTemplateURL'   => $array['customPaymentTemplateURL'],
            'contractNumberWalletList'   => $array['walletContracts'],
            'merchantName'               => $array['merchantName'],
            'subMerchant'                => $this->subMerchant($array['subMerchant']),
            'miscData'                   => $array['miscData'],
            'asynchronousRetryTimeout'   => $array['asynchronousRetryTimeout'],
            'threeDSInfo'                => $this->threeDSInfo($array['threeDSInfo'], $array['browser'], $array['sdk']),
            'merchantScore'              => $array['merchantScore'],
            'skipSmartDisplay'           => $array['skipSmartDisplay']
        );


        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }
        return $this->webServiceRequest($array, $WSRequest, self::WEB_API, 'doWebPayment');
    }

    /**
     * calls doAuthorizationRedirectRequest web service
     *
     * @param array $array
     *            associative array containing doAuthorizationRedirectRequest parameters
     */
    public function doAuthorizationRedirect(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'payment'                    => $this->payment($array['payment']),
            'returnURL'                  => $array['returnURL'],
            'cancelURL'                  => $array['cancelURL'],
            'order'                      => $this->order($array['order']),
            'notificationURL'            => $array['notificationURL'],
            'privateDataList'            => $this->privateData,
            'languageCode'               => $array['languageCode'],
            'buyer'                      => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                      => $this->owner($array['owner'], $array['ownerAddress']),
            'securityMode'               => $array['securityMode'],
            'merchantName'               => $array['merchantName'],
            'subMerchant'                => $this->subMerchant($array['subMerchant']),
            'miscData'                   => $array['miscData'],
            'asynchronousRetryTimeout'   => $array['asynchronousRetryTimeout'],
            'authentication3DSecure'     => $this->authentication3DSecure($array['3DSecure'])
        );

        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }
        return $this->webServiceRequest($array, $WSRequest, self::WEB_API, 'doWebPayment');
    }




    /**
     * calls doWebPayment web service
     *
     * @param array $array
     *            associative array containing getWebPaymentDetails parameters
     */
    public function getWebPaymentDetails(array $array)
    {
        return $this->webServiceRequest($array, $array, self::WEB_API, 'getWebPaymentDetails');
    }

    /**
     * calls manageWebWallet web service
     *
     * @param array $array
     *            associative array containing manageWebWallet parameters
     */
    public function manageWebWallet(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'contractNumber'           => $array['contractNumber'],
            'selectedContractList'     => $array['contracts'],
            'updatePersonalDetails'    => $array['updatePersonalDetails'],
            'buyer'                    => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                    => $this->owner($array['owner'], $array['ownerAddress']),
            'languageCode'             => $array['languageCode'],
            'customPaymentPageCode'    => $array['customPaymentPageCode'],
            'securityMode'             => $array['securityMode'],
            'returnURL'                => $array['returnURL'],
            'cancelURL'                => $array['cancelURL'],
            'notificationURL'          => $array['notificationURL'],
            'privateDataList'          => $this->privateData,
            'customPaymentTemplateURL' => $array['customPaymentTemplateURL'],
            'contractNumberWalletList' => $array['walletContracts'],
            'merchantName'             => $array['merchantName'],
            'threeDSInfo'              => $this->threeDSInfo($array['threeDSInfo'], $array['browser'], $array['sdk'])
        );
        return $this->webServiceRequest($array, $WSRequest, self::WEB_API, 'manageWebWallet');
    }

    /**
     * calls createWebWallet web service
     *
     * @param array $array
     *            associative array containing createWebWallet parameters
     */
    public function createWebWallet(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'contractNumber'           => $array['contractNumber'],
            'selectedContractList'     => $array['contracts'],
            'updatePersonalDetails'    => $array['updatePersonalDetails'],
            'buyer'                    => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'owner'                    => $this->owner($array),
            'languageCode'             => $array['languageCode'],
            'customPaymentPageCode'    => $array['customPaymentPageCode'],
            'securityMode'             => $array['securityMode'],
            'returnURL'                => $array['returnURL'],
            'cancelURL'                => $array['cancelURL'],
            'notificationURL'          => $array['notificationURL'],
            'privateDataList'          => $this->privateData,
            'customPaymentTemplateURL' => $array['customPaymentTemplateURL'],
            'contractNumberWalletList' => $array['walletContracts']
        );
        return $this->webServiceRequest($array, $WSRequest, self::WEB_API, 'createWebWallet');
    }

    /**
     * calls updateWebWallet web service
     *
     * @param array $array
     *            associative array containing updateWebWallet parameters
     */
    public function updateWebWallet(array $array)
    {
        $this->formatRequest($array);
        $WSRequest = array(
            'contractNumber'           => $array['contractNumber'],
            'cardInd'                  => $array['cardInd'],
            'walletId'                 => $array['walletId'],
            'updatePersonalDetails'    => $array['updatePersonalDetails'],
            'updateOwnerDetails'       => $array['updateOwnerDetails'],
            'updatePaymentDetails'     => $array['updatePaymentDetails'],
            'buyer'                    => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress'], $array['merchantAuthentication']),
            'languageCode'             => $array['languageCode'],
            'customPaymentPageCode'    => $array['customPaymentPageCode'],
            'securityMode'             => $array['securityMode'],
            'returnURL'                => $array['returnURL'],
            'cancelURL'                => $array['cancelURL'],
            'notificationURL'          => $array['notificationURL'],
            'privateDataList'          => $this->privateData,
            'customPaymentTemplateURL' => $array['customPaymentTemplateURL'],
            'contractNumberWalletList' => $array['walletContracts']
        );
        return $this->webServiceRequest($array, $WSRequest, self::WEB_API, 'updateWebWallet');
    }

    /**
     * calls getWebWallet web service
     *
     * @param array $array
     *            associative array containing getWebWallet parameters
     */
    public function getWebWallet(array $array)
    {
        return $this->webServiceRequest($array, $array, self::WEB_API, 'getWebWallet');
    }

    /*
     * *************************************************************************
     *
     * ExtendedAPI
     *
     * *************************************************************************
     */

    /**
     * calls getTransactionDetails web service
     *
     * @param array $array
     *            associative array containing getWebWallet parameters
     */
    public function getTransactionDetails(array $array)
    {
        $this->formatRequest($array);
        return $this->webServiceRequest($array, $array, self::EXTENDED_API, 'getTransactionDetails');
    }

    /**
     * calls transactionsSearch web service
     *
     * @param array $array
     *            associative array containing getWebWallet parameters
     */
    public function transactionsSearch(array $array)
    {
        return $this->webServiceRequest($array, $array, self::EXTENDED_API, 'transactionsSearch');
    }

    /**
     * calls getAlertDetails web service
     *
     * @param array $array
     *            associative array containing getAlertDetails parameters
     */
    public function getAlertDetails(array $array) {
        return $this->webServiceRequest($array, $array, self::EXTENDED_API, 'getAlertDetails');
    }

    /*
     * ************************************************************************
     *
     * Ad hoc functions for AJAX API (getToken servlet)
     *
     * ************************************************************************
     */

    /**
     * Custom base64 url encoding.
     * Replace unsafe url chars
     *
     * @param string $input
     *            message to encode
     * @return string
     */
    public function base64_url_encode($input)
    {
        return strtr(base64_encode($input), '+/=', '-_,');
    }

    /**
     * Custom base64 url decode.
     * Replace custom url safe values with normal
     * base64 characters before decoding.
     *
     * @param string $input
     *            message to decode
     * @return string
     */
    public function base64_url_decode($input)
    {
        return base64_decode(strtr($input, '-_,', '+/='));
    }

    /**
     * AES compliant encryption (uses MCRYPT_RIJNDAEL_128).
     * This function is used to build authentication data for getToken servlet
     *
     * @param string $message
     *            message to encrypt
     * @param string $accessKey
     *            merchant access key (SHA256 encrypted)
     * @return string encrypted message
     */
    public function getEncrypt($message, $accessKey)
    {
        $cipher = "AES-256-ECB";
        $opts = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;

        $pad = 16;
        $len = strlen($message);

        $padlen = $len + $pad - $len % $pad;
        $message = str_pad(
            $message,
            $padlen,
            chr($padlen - $len)
        );

        $encrypted = openssl_encrypt($message, $cipher, $accessKey, $opts);

        return $this->base64_url_encode($encrypted);
    }

    /**
     * Decrypts message sent by getToken servlet
     *
     * @param string $message
     *            message to decrypt
     * @param string $accessKey
     *            merchant access key (SHA256 encrypted)
     */
    public function getDecrypt($message, $accessKey)
    {
        $cipher = "AES-256-ECB";
        $opts = OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING;

        $message = $this->base64_url_decode($message);
        $decrypted = openssl_decrypt($message, $cipher, $accessKey, $opts);
        $len = strlen($decrypted);
        $pad = ord($decrypted[$len - 1]);

        return substr($decrypted, 0, strlen($decrypted) - $pad);
    }

    /**
     * Unzip data
     *
     * @param string $data
     *            decrypted message sent by getToken servlet
     * @param string $filename
     * @param string $error
     * @param unknown $maxlength
     * @return NULL|boolean|string
     */
    public function gzdecode($data, &$filename = '', &$error = '', $maxlength = null)
    {
        $len = strlen($data);
        if ($len < 18 || strcmp(substr($data, 0, 2), "\x1f\x8b")) {
            $error = "Not in GZIP format.";
            return null; // Not GZIP format (See RFC 1952)
        }
        $method = ord(substr($data, 2, 1)); // Compression method
        $flags = ord(substr($data, 3, 1)); // Flags
        if ($flags & 31 != $flags) {
            $error = "Reserved bits not allowed.";
            return null;
        }
        // NOTE: $mtime may be negative (PHP integer limitations)
        $mtime = unpack("V", substr($data, 4, 4));
        $mtime = $mtime[1];
        $headerlen = 10;
        $extralen = 0;
        if ($flags & 4) {
            // 2-byte length prefixed EXTRA data in header
            if ($len - $headerlen - 2 < 8) {
                return false; // invalid
            }
            $extralen = unpack("v", substr($data, 8, 2));
            $extralen = $extralen[1];
            if ($len - $headerlen - 2 - $extralen < 8) {
                return false; // invalid
            }
            $headerlen += 2 + $extralen;
        }
        $filenamelen = 0;
        $filename = "";
        if ($flags & 8) {
            // C-style string
            if ($len - $headerlen - 1 < 8) {
                return false; // invalid
            }
            $filenamelen = strpos(substr($data, $headerlen), chr(0));
            if ($filenamelen === false || $len - $headerlen - $filenamelen - 1 < 8) {
                return false; // invalid
            }
            $filename = substr($data, $headerlen, $filenamelen);
            $headerlen += $filenamelen + 1;
        }
        $commentlen = 0;
        if ($flags & 16) {
            // C-style string COMMENT data in header
            if ($len - $headerlen - 1 < 8) {
                return false; // invalid
            }
            $commentlen = strpos(substr($data, $headerlen), chr(0));
            if ($commentlen === false || $len - $headerlen - $commentlen - 1 < 8) {
                return false; // Invalid header format
            }
            $headerlen += $commentlen + 1;
        }
        $headercrc = "";
        if ($flags & 2) {
            // 2-bytes (lowest order) of CRC32 on header present
            if ($len - $headerlen - 2 < 8) {
                return false; // invalid
            }
            $calccrc = crc32(substr($data, 0, $headerlen)) & 0xffff;
            $headercrc = unpack("v", substr($data, $headerlen, 2));
            $headercrc = $headercrc[1];
            if ($headercrc != $calccrc) {
                $error = "Header checksum failed.";
                return false; // Bad header CRC
            }
            $headerlen += 2;
        }
        // GZIP FOOTER
        $datacrc = unpack("V", substr($data, - 8, 4));
        $datacrc = sprintf('%u', $datacrc[1] & 0xFFFFFFFF);
        $isize = unpack("V", substr($data, - 4));
        $isize = $isize[1];
        // decompression:
        $bodylen = $len - $headerlen - 8;
        if ($bodylen < 1) {
            // IMPLEMENTATION BUG!
            return null;
        }
        $body = substr($data, $headerlen, $bodylen);
        $data = "";
        if ($bodylen > 0) {
            switch ($method) {
                case 8:
                    // Currently the only supported compression method:
                    $data = gzinflate($body, $maxlength);
                    break;
                default:
                    $error = "Unknown compression method.";
                    return false;
            }
        } // zero-byte body content is allowed
          // Verifiy CRC32
        $crc = sprintf("%u", crc32($data));
        $crcOK = $crc == $datacrc;
        $lenOK = $isize == strlen($data);
        if (! $lenOK || ! $crcOK) {
            $error = ($lenOK ? '' : 'LengthsetSoapOptions check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
            return false;
        }
        return $data;
    }

    /**
     * @param array $array
     * @param $object
     * @param $typeName
     * @return SoapVar
     */
    protected function buildSoapObject(array $array, $object, $typeName)
    {
        $object = $this->fillObject($array, $object);
        return new \SoapVar($object, SOAP_ENC_OBJECT, $typeName, self::PAYLINE_NAMESPACE);
    }


    /**
     * @param array $array
     * @param $object
     * @return mixed
     */
    protected function fillObject(array $array, $object)
    {
        if ($array) {
            foreach ($array as $k => $v) {
                if (property_exists($object, $k) && $this->userDataIsNotEmpty($v)) {
                    $object->$k = $v;
                }
            }
        }
        return $object;
    }


    /**
     * Test user data
     *
     * @param $data
     * @return bool
     */
    protected function userDataIsNotEmpty($data) {

        if($data instanceof \Countable ) {
            return (count($data)>0);
        }
        return !empty($data);
    }

    /**
     * Pretty print XML
     *
     * @param string $xml
     *            content of the xml file to make pretty
     * @return boolean|string
     */
    protected function beautifulerXML(&$xml, $key) {
        if(in_array($key, array('Request', 'Response')) && $xml) {
            $dom = new \DOMDocument('1.0');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            $dom->loadXML($xml);
            $xml = $dom->saveXML();
        }
    }


    /**
     * @param string $key
     * - Request
     * - RequestHeaders
     * - Response
     * - ResponseHeaders
     * - HttpHeaders
     * @param false $beautifuler
     * @return array
     */
    public function getSoapLastContent($key = '', $beautifuler = true)
    {
        $returnCall = array();
        if($key) {
            foreach ($this->lastSoapCallData as $callNum=>$callData) {
                if(isset($callData[$key])) {
                    $returnCall[$callNum][$key] = $callData[$key];
                }
            }
        } else {
            $returnCall = $this->lastSoapCallData;
        }

        if($beautifuler) {
            foreach ($returnCall as $callNum=>$callData) {
                 array_walk($returnCall[$callNum], array($this, 'beautifulerXML'));
            }
        }

        return $returnCall;
    }
}
