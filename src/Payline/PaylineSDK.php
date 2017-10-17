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

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
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

class PaylineSDK
{

    /**
     * Payline release corresponding to this version of the package
     */
    const SDK_RELEASE = 'PHP SDK 4.52';

    /**
     * WSDL file name
     */
    const WSDL = 'v4.52.wsdl';

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
     * Monolog\Logger instance
     */
    private $logger;

    /**
     * tool / e-commerce module using this library
     */
    private $usedBy = null;

    /**
     * array containing order details
     */
    private $orderDetails;

    /**
     * array containing private data
     */
    private $privateData;
    
    /**
     * array containing parent-child nodes associations
     */
    private $parentNode = array(
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
     *            Monolog\Logger log level. Default : Logger::INFO
     * @param  Monolog\Logger $externalLogger
     *            Monolog\Logger instance, used by PaylineSDK but external to it 
     */
    public function __construct($merchant_id, $access_key, $proxy_host, $proxy_port, $proxy_login, $proxy_password, $environment, $pathLog = null, $logLevel = Logger::INFO, $externalLogger = null, $defaultTimezone = "Europe/Paris")
    {
        $merchant_id = $merchant_id+''; // prevent cast errors. Mechant ID has to be a string.
        date_default_timezone_set($defaultTimezone);
        if($externalLogger){
            $this->logger = $externalLogger;
        }else{
            $this->logger = new Logger('PaylineSDK');
        }        
        if (is_null($pathLog)) {
            $this->logger->pushHandler(new StreamHandler(realpath(dirname(dirname(dirname(__FILE__)))) . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . date('Y-m-d') . '.log', $logLevel)); // set default log folder
        } elseif (strlen($pathLog) > 0) {
            $this->logger->pushHandler(new StreamHandler($pathLog . date('Y-m-d') . '.log', $logLevel)); // set custom log folder
        }
        
        $this->logger->addInfo('__construct', array(
            'merchant_id' => $this->hideChars($merchant_id, 6, 1),
            'access_key' => $this->hideChars($access_key, 1, 3),
            'proxy_host' => $proxy_host,
            'proxy_port' => $proxy_port,
            'proxy_login' => $proxy_login,
            'proxy_password' => $this->hideChars($proxy_password, 1, 1),
            'environment' => $environment
        ));
        $this->soapclient_options = array();
        $this->soapclient_options['login'] = $merchant_id;
        $this->soapclient_options['password'] = $access_key;
        if ($proxy_host != '') {
            $this->soapclient_options['proxy_host'] = $proxy_host;
            $this->soapclient_options['proxy_port'] = $proxy_port;
            $this->soapclient_options['proxy_login'] = $proxy_login;
            $this->soapclient_options['proxy_password'] = $proxy_password;
        }
        $plnInternal = false;
        if (strcmp($environment, PaylineSDK::ENV_HOMO) == 0) {
            $this->webServicesEndpoint = PaylineSDK::HOMO_ENDPOINT;
        } elseif (strcmp($environment, PaylineSDK::ENV_HOMO_CC) == 0) {
            $this->webServicesEndpoint = PaylineSDK::HOMO_CC_ENDPOINT;
        } elseif (strcmp($environment, PaylineSDK::ENV_PROD) == 0) {
            $this->webServicesEndpoint = PaylineSDK::PROD_ENDPOINT;               
        } elseif (strcmp($environment, PaylineSDK::ENV_PROD_CC) == 0) {
            $this->webServicesEndpoint = PaylineSDK::PROD_CC_ENDPOINT;
        } elseif (strcmp($environment, PaylineSDK::ENV_DEV) == 0) {
            $this->webServicesEndpoint = PaylineSDK::DEV_ENDPOINT;
            $plnInternal = true;
        } elseif (strcmp($environment, PaylineSDK::ENV_INT) == 0) {
            $this->webServicesEndpoint = PaylineSDK::INT_ENDPOINT;
            $plnInternal = true;
        }else{
            $this->webServicesEndpoint = false; // Exception is raised in PaylineSDK::webServiceRequest
        }
        $this->soapclient_options['style'] = defined('SOAP_DOCUMENT') ? SOAP_DOCUMENT : 2;
        $this->soapclient_options['use'] = defined('SOAP_LITERAL') ? SOAP_LITERAL : 2;
        $this->soapclient_options['connection_timeout'] = 5;
        if($plnInternal){
            $this->soapclient_options['stream_context'] = stream_context_create(
                array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    )
                )
            );
        }
        $this->orderDetails = array();
        $this->privateData = array();
        
        ini_set('user_agent', "PHP\r\nversion: " . PaylineSDK::SDK_RELEASE);
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
        $payment = new Payment();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $payment) && (strlen($v))) {
                    $payment->$k = $v;
                }
            }
        }
        return new \SoapVar($payment, SOAP_ENC_OBJECT, PaylineSDK::SOAP_PAYMENT, PaylineSDK::PAYLINE_NAMESPACE);
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
        $paymentData = new PaymentData();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $paymentData) && (strlen($v))) {
                    $paymentData->$k = $v;
                }
            }
        }
        return new \SoapVar($paymentData, SOAP_ENC_OBJECT, PaylineSDK::SOAP_PAYMENT_DATA, PaylineSDK::PAYLINE_NAMESPACE);
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
        $order = new Order();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $order) && (strlen($v))) {
                    $order->$k = $v;
                }
            }
        }
        // insert orderDetails
        $order->details = $this->orderDetails;
        return new \SoapVar($order, SOAP_ENC_OBJECT, PaylineSDK::SOAP_ORDER, PaylineSDK::PAYLINE_NAMESPACE);
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
        $card = new Card();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $card) && (strlen($v))) {
                    $card->$k = $v;
                }
            }
        }
        $card->paymentData = null;
        if(isset($array['paymentData'])){
            $card->paymentData = $this->paymentData($array['paymentData']);
        }
        return new \SoapVar($card, SOAP_ENC_OBJECT, PaylineSDK::SOAP_CARD, PaylineSDK::PAYLINE_NAMESPACE);
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
     * @return SoapVar representation of Buyer instance
     */
    protected function buyer(array $array,array $shippingAdress,array $billingAddress)
    {
        $buyer = new Buyer();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $buyer) && (strlen($v)))
                    $buyer->$k = $v;
            }
        }
        $buyer->shippingAdress = $this->address($shippingAdress);
        $buyer->billingAddress = $this->address($billingAddress);
        return new \SoapVar($buyer, SOAP_ENC_OBJECT, PaylineSDK::SOAP_BUYER, PaylineSDK::PAYLINE_NAMESPACE);
    }

    /**
     * build Address instance from $array and make SoapVar object for address.
     *
     * @param array $address
     *            the array keys are listed in Address CLASS.
     * @return SoapVar representation of Address instance
     */
    protected function address(array $array)
    {
        $address = new Address();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $address) && (strlen($v)))
                    $address->$k = $v;
            }
        }
        return new \SoapVar($address, SOAP_ENC_OBJECT, PaylineSDK::SOAP_ADDRESS, PaylineSDK::PAYLINE_NAMESPACE);
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
        $addressOwner = new AddressOwner();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $addressOwner) && (strlen($v)))
                    $addressOwner->$k = $v;
            }
        }
        return new \SoapVar($addressOwner, SOAP_ENC_OBJECT, PaylineSDK::SOAP_ADDRESS_OWNER, PaylineSDK::PAYLINE_NAMESPACE);
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
    protected function owner(array $array, array $addressOwner)
    {
        if ($array != null) {
            $owner = new Owner();
            if ($array) {
                foreach ($array as $k => $v) {
                    if (array_key_exists($k, $owner) && (strlen($v)))
                        $owner->$k = $v;
                }
            }
            $owner->billingAddress = $this->addressOwner($addressOwner);
            return new \SoapVar($owner, SOAP_ENC_OBJECT, PaylineSDK::SOAP_OWNER, PaylineSDK::PAYLINE_NAMESPACE);
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
        $authentication3DSecure = new Authentication3DSecure();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $authentication3DSecure) && (strlen($v))) {
                    $authentication3DSecure->$k = $v;
                }
            }
        }
        return new \SoapVar($authentication3DSecure, SOAP_ENC_OBJECT, PaylineSDK::SOAP_AUTHENTICATION_3DSECURE, PaylineSDK::PAYLINE_NAMESPACE);
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
        $bankAccountData = new BankAccountData($array);
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $bankAccountData) && (strlen($v))) {
                    $bankAccountData->$k = $v;
                }
            }
        }
        return new \SoapVar($bankAccountData, SOAP_ENC_OBJECT, PaylineSDK::SOAP_BANK_ACCOUNT_DATA, PaylineSDK::PAYLINE_NAMESPACE);
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
        $billingRecordForUpdate = new BillingRecordForUpdate();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $billingRecordForUpdate) && (strlen($v)))
                    $billingRecordForUpdate->$k = $v;
            }
        }
        return new \SoapVar($billingRecordForUpdate, SOAP_ENC_OBJECT, PaylineSDK::SOAP_BILLING_RECORD_FOR_UPDATE, PaylineSDK::PAYLINE_NAMESPACE);
    }

    /**
     * build Wallet instance from $array and make SoapVar object for wallet
     *
     * @param array $inWallet
     *            the array keys are listed in Wallet CLASS.
     * @param array $address
     *            the array keys are listed in Address CLASS.
     * @param array $card
     *            the array keys are listed in Card CLASS.
     * @return SoapVar representation of Wallet instance
     */
    protected function wallet(array $inWallet, array $address, array $card)
    {
        $wallet = new Wallet();
        if ($inWallet) {
            foreach ($inWallet as $k => $v) {
                if (array_key_exists($k, $wallet) && (strlen($v)))
                    $wallet->$k = $v;
            }
        }
        
        $wallet->shippingAddress = $this->address($address);
        $wallet->card = $this->card($card);
        
        return $wallet;
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
        $authorization = new Authorization($array);
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $authorization) && (strlen($v))) {
                    $authorization->$k = $v;
                }
            }
        }
        return new \SoapVar($authorization, SOAP_ENC_OBJECT, PaylineSDK::SOAP_AUTHORIZATION, PaylineSDK::PAYLINE_NAMESPACE);
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
        $creditor = new Creditor();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $creditor) && (strlen($v)))
                    $creditor->$k = $v;
            }
        }
        return new \SoapVar($creditor, SOAP_ENC_OBJECT, PaylineSDK::SOAP_CREDITOR, PaylineSDK::PAYLINE_NAMESPACE);
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
        $cheque = new Cheque($array);
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $cheque) && (strlen($v))) {
                    $cheque->$k = $v;
                }
            }
        }
        return new \SoapVar($cheque, SOAP_ENC_OBJECT, PaylineSDK::SOAP_CHEQUE, PaylineSDK::PAYLINE_NAMESPACE);
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
        $recurring = new Recurring();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $recurring) && (strlen($v))) {
                    $recurring->$k = $v;
                }
            }
        }
        return new \SoapVar($recurring, SOAP_ENC_OBJECT, PaylineSDK::SOAP_RECURRING, PaylineSDK::PAYLINE_NAMESPACE);
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
    private function hideChars($inString, $n1, $n2)
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
    private function isChildFromList($nodeName,$parentName){
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
    private function responseToArray($node, $parent = null)
    {
        $array = array();
        foreach ($node as $k => $v) {
            if ($this->isChildFromList($k, $parent)) { // current value is a list
                if(count($v) == 1 && $k != '0'){ // a list with 1 element. It's returned with a 0-index
                    $array[$k][0] = PaylineSDK::responseToArray($v,$k);
                }elseif(is_object($v) || is_array($v)){ // a list with more than 1 element
                    $array[$k] = PaylineSDK::responseToArray($v,$k);
                }else{
                    $array[$k] = $v;
                }
            } else {
                if(is_object($v) || is_array($v)){
                    $array[$k] = PaylineSDK::responseToArray($v,$k);
                }else{
                    $array[$k] = $v;
                }
            }
        }
        return $array;
    }
        
    /**
     * Adds indexes with null values to the web services request array, in order to prevent SOAP format exception
     *
     * @param array $array
     *            associative array containing web services parameters
     */
    private static function formatRequest(&$array)
    {
        if (! isset($array['buyer']))
            $array['buyer'] = null;
        if (! isset($array['ownerAddress']))
            $array['ownerAddress'] = null;
        if (! isset($array['3DSecure']))
            $array['3DSecure'] = null;
        if (! isset($array['bankAccountData']))
            $array['bankAccountData'] = null;
        if (! isset($array['cancelURL']) || ! strlen($array['cancelURL']))
            $array['cancelURL'] = null;
        if (! isset($array['notificationURL']) || ! strlen($array['notificationURL']))
            $array['notificationURL'] = null;
        if (! isset($array['returnURL']) || ! strlen($array['returnURL']))
            $array['returnURL'] = null;
        if (! isset($array['languageCode']) || ! strlen($array['languageCode']))
            $array['languageCode'] = null;
        if (! isset($array['securityMode']) || ! strlen($array['securityMode']))
            $array['securityMode'] = null;
        if (! isset($array['buyer']))
            $array['buyer'] = null;
        if (! isset($array['billingAddress']))
            $array['billingAddress'] = null;
        if (! isset($array['shippingAddress']))
            $array['shippingAddress'] = null;
        if (! isset($array['owner']))
            $array['owner'] = null;
        if (! isset($array['ownerAddress']))
            $array['ownerAddress'] = null;
        if (! isset($array['contracts']) || ! strlen($array['contracts'][0]) || ! is_array($array['contracts']))
            $array['contracts'] = null;
        if (! isset($array['secondContracts']) || ! strlen($array['secondContracts'][0]) || ! is_array($array['secondContracts']))
            $array['secondContracts'] = null;
        if (! isset($array['walletContracts']) || ! strlen($array['walletContracts'][0]) || ! is_array($array['walletContracts']))
            $array['walletContracts'] = null;
        if (! isset($array['customPaymentPageCode']) || ! strlen($array['customPaymentPageCode']))
            $array['customPaymentPageCode'] = null;
        if (! isset($array['customPaymentTemplateURL']) || ! strlen($array['customPaymentTemplateURL']))
            $array['customPaymentTemplateURL'] = null;
        if (! isset($array['recurring']))
            $array['recurring'] = null;
        if (! isset($array['orderRef']) || ! strlen($array['orderRef']))
            $array['orderRef'] = null;
        if (! isset($array['orderDate']) || ! strlen($array['orderDate']))
            $array['orderDate'] = null;
        if (! isset($array['walletIds']) || ! strlen($array['walletIds'][0] || ! is_array($array['walletIds'])))
            $array['walletIds'] = null;
        if (! isset($array['merchantName']))
            $array['merchantName'] = null;
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
    private function webServiceRequest(array $array, array $WSRequest, $PaylineAPI, $Method)
    {
        $logRequest = array();
        $logResponse = array(
            'result.code' => null
        );
        try {
            if(!$this->webServicesEndpoint){
                throw new \Exception('Endpoint error (check `environment` parameter of PaylineSDK constructor)');
            }
            $client = new SoapClient(dirname(__FILE__) . '/' . PaylineSDK::WSDL, $this->soapclient_options);
            $client->__setLocation($this->webServicesEndpoint . $PaylineAPI);
            
            $WSRequest['version'] = isset($array['version']) && strlen($array['version']) ? $array['version'] : '';
            $WSRequest['media'] = isset($array['media']) && strlen($array['media']) ? $array['media'] : '';
            
            switch ($Method) {
                case 'createMerchant':
                    $response = PaylineSDK::responseToArray($client->createMerchant($WSRequest));
                    break;
                case 'createWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['wallet']['walletId'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = PaylineSDK::responseToArray($client->createWallet($WSRequest));
                    break;
                case 'createWebWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['buyer']['walletId']
                    );
                    $response = PaylineSDK::responseToArray($client->createWebWallet($WSRequest));
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
                    $response = PaylineSDK::responseToArray($client->getBillingRecord($WSRequest));
                    break;
                case 'updateBillingRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId' => $array['paymentRecordId'],
                        'billingRecordId' => $array['billingRecordId']
                    );
                    $response = PaylineSDK::responseToArray($client->updateBillingRecord($WSRequest));
                    break;
                case 'disablePaymentRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId' => $array['paymentRecordId']
                    );
                    $response = PaylineSDK::responseToArray($client->disablePaymentRecord($WSRequest));
                    break;
                case 'disableWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletIdList' => implode(';', $array['walletIds'])
                    );
                    $response = PaylineSDK::responseToArray($client->disableWallet($WSRequest));
                    break;
                case 'doAuthorization':
                    $logRequest = array(
                        'order.ref' => $array['order']['ref'],
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'payment.amount' => $array['payment']['amount']
                    );
                    $response = PaylineSDK::responseToArray($client->doAuthorization($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doCapture':
                    $logRequest = array(
                        'transactionID' => $array['transactionID'],
                        'payment.amount' => $array['payment']['amount']
                    );
                    $response = PaylineSDK::responseToArray($client->doCapture($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doCredit':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'order.ref' => $array['order']['ref'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = PaylineSDK::responseToArray($client->doCredit($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doDebit':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'order.ref' => $array['order']['ref'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = PaylineSDK::responseToArray($client->doDebit($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doImmediateWalletPayment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'walletId' => $array['walletId'],
                        'order.ref' => $array['order']['ref']
                    );
                    $response = PaylineSDK::responseToArray($client->doImmediateWalletPayment($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doReAuthorization':
                    $logRequest = array(
                        'transactionID' => $array['transactionID'],
                        'amount' => $array['payment']['amount']
                    );
                    $response = PaylineSDK::responseToArray($client->doReAuthorization($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doRecurrentWalletPayment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'walletId' => $array['walletId'],
                        'order.ref' => $array['order']['ref']
                    );
                    $response = PaylineSDK::responseToArray($client->doRecurrentWalletPayment($WSRequest));
                    if ($response['result']['code'] == '02500') {
                        $logResponse['paymentRecordId'] = $response['paymentRecordId'];
                    }
                    break;
                case 'doRefund':
                    $logRequest = array(
                        'transactionID' => $array['transactionID'],
                        'payment.amount' => $array['payment']['amount']
                    );
                    $response = PaylineSDK::responseToArray($client->doRefund($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doReset':
                    $logRequest = array(
                        'transactionID' => $array['transactionID']
                    );
                    $response = PaylineSDK::responseToArray($client->doReset($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
                case 'doScheduledWalletPayment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'walletId' => $array['walletId'],
                        'order.ref' => $array['order']['ref']
                    );
                    $response = PaylineSDK::responseToArray($client->doScheduledWalletPayment($WSRequest));
                    if ($response['result']['code'] == '02500') {
                        $logResponse['paymentRecordId'] = $response['paymentRecordId'];
                    }
                    break;
                case 'doScoringCheque':
                    $response = PaylineSDK::responseToArray($client->doScoringCheque($WSRequest));
                    break;
                case 'doWebPayment':
                    $logRequest = array(
                        'order.ref' => $array['order']['ref']
                    );
                    $response = PaylineSDK::responseToArray($client->doWebPayment($WSRequest));
                    if ($response['result']['code'] == '00000') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'enableWallet':
                    $logRequest = array(
                        'walletId' => $array['walletId']
                    );
                    $response = PaylineSDK::responseToArray($client->enableWallet($WSRequest));
                    break;
                case 'getAlertDetails':
                    $logRequest = array(
                        'alertId' => $array['AlertId'],
                        'transactionId' => $array['TransactionId']
                    );
                    $response = PaylineSDK::responseToArray($client->getAlertDetails($WSRequest));
                    break;
                case 'getBalance':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'cardID' => $this->hideChars($array['cardID'], 4, 4)
                    );
                    $response = PaylineSDK::responseToArray($client->getBalance($WSRequest));
                    break;
                case 'getCards':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['walletId'],
                        'cardInd' => $array['cardInd']
                    );
                    $response = PaylineSDK::responseToArray($client->getCards($WSRequest));
                    break;
                case 'getEncryptionKey':
                    $response = PaylineSDK::responseToArray($client->getEncryptionKey($WSRequest));
                    break;
                case 'getMerchantSettings':
                    $response = PaylineSDK::responseToArray($client->getMerchantSettings($WSRequest));
                    break;
                case 'getPaymentRecord':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'paymentRecordId' => $array['paymentRecordId']
                    );
                    $response = PaylineSDK::responseToArray($client->getPaymentRecord($WSRequest));
                    break;
                case 'getToken':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'cardNumber' => $this->hideChars($array['cardNumber'], 4, 4)
                    );
                    $response = PaylineSDK::responseToArray($client->getToken($WSRequest));
                    if ($response['result']['code'] == '02500') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'getTransactionDetails':
                    $logRequest = array(
                        'transactionId' => $array['transactionId']
                    );
                    $response = PaylineSDK::responseToArray($client->getTransactionDetails($WSRequest));
                    break;
                case 'getWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'walletId' => $array['walletId'],
                        'cardInd' => $array['cardInd']
                    );
                    $response = PaylineSDK::responseToArray($client->getWallet($WSRequest));
                    break;
                case 'getWebPaymentDetails':
                    $logRequest = array(
                        'token' => $array['token']
                    );
                    $response = PaylineSDK::responseToArray($client->getWebPaymentDetails($WSRequest));
                    if (isset($response['transaction']['id'])) {
                        $logResponse['transaction.id'] = $response['transaction']['id'];
                    }
                    break;
                case 'getWebWallet':
                    $logRequest = array(
                        'token' => $array['token']
                    );
                    $response = PaylineSDK::responseToArray($client->getWebWallet($WSRequest));
                    if (isset($response['wallet']['card'])) {
                        $logResponse['wallet.card.number'] = $this->hideChars($response['wallet']['card']['number'], 4, 4);
                    }
                    break;
                case 'manageWebWallet':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'buyer.walletId' => $array['buyer']['walletId']
                    );
                    $response = PaylineSDK::responseToArray($client->manageWebWallet($WSRequest));
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
                    $response = PaylineSDK::responseToArray($client->transactionsSearch($WSRequest));
                    break;
                case 'unBlock':
                    $logRequest = array(
                        'transactionID' => $array['transactionID']
                    );
                    $response = PaylineSDK::responseToArray($client->unBlock($WSRequest));
                    break;
                case 'updateWallet':
                    $logRequest = array(
                        'walletId' => $array['wallet']['walletId']
                    );
                    $response = PaylineSDK::responseToArray($client->updateWallet($WSRequest));
                    break;
                case 'updateWebWallet':
                    $logRequest = array(
                        'walletId' => $array['walletId']
                    );
                    $response = PaylineSDK::responseToArray($client->updateWebWallet($WSRequest));
                    if ($response['result']['code'] == '00000') {
                        $logResponse['token'] = $response['token'];
                    }
                    break;
                case 'verifyAuthentication':
                    $logRequest = array(
                        'contractNumber' => $array['contractNumber'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = PaylineSDK::responseToArray($client->verifyAuthentication($WSRequest));
                    break;
                case 'verifyEnrollment':
                    $logRequest = array(
                        'payment.contractNumber' => $array['payment']['contractNumber'],
                        'card.number' => $this->hideChars($array['card']['number'], 4, 4)
                    );
                    $response = PaylineSDK::responseToArray($client->verifyEnrollment($WSRequest));
                    break;
                case 'doBankTransfer':
                    $logRequest = array(
                        'orderID' => $array['orderID'],
                        'creditor.bic' => $this->hideChars($array['creditor']['bic'], 4, 1),
                        'creditor.iban' => $this->hideChars($array['creditor']['iban'], 8, 1)
                    );
                    $response = PaylineSDK::responseToArray($client->doBankTransfer($WSRequest));
                    $logResponse['transaction.id'] = $response['transaction']['id'];
                    break;
            }
            $logResponse['result.code'] = $response['result']['code'];
            $this->logger->addInfo($Method . 'Request', $logRequest);
            $this->logger->addInfo($Method . 'Response', $logResponse);
            return $response;
        } catch (\Exception $e) {
            $this->logger->addInfo($Method . 'Request', $logRequest);
            $this->logger->addError('Exception occured at ' . $Method . ' call', array(
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'endpoint' => $this->webServicesEndpoint . $PaylineAPI
            )); 
            $ERROR = array();
            $ERROR['result']['code'] = PaylineSDK::ERR_CODE;
            $ERROR['result']['longMessage'] = $e->getMessage();
            $ERROR['result']['shortMessage'] = PaylineSDK::ERR_SHORT_MESSAGE;
            $ERROR['result']['partnerCode'] = null;
            $ERROR['result']['partnerCodeLabel'] = null;
            return $ERROR;
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
        ini_set('user_agent', "PHP\r\nversion: " . $toolName . ' - ' . PaylineSDK::SDK_RELEASE);
    }

    /**
     * returns Monolog\Logger instance
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
        $orderDetail = new OrderDetail();
        if ($newOrderDetail) {
            foreach ($newOrderDetail as $k => $v) {
                if (array_key_exists($k, $orderDetail) && (strlen($v)))
                    $orderDetail->$k = $v;
            }
        }
        $this->orderDetails[] = new \SoapVar($orderDetail, SOAP_ENC_OBJECT, PaylineSDK::SOAP_ORDERDETAIL, PaylineSDK::PAYLINE_NAMESPACE);
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
        $private = new PrivateData();
        if ($array) {
            foreach ($array as $k => $v) {
                if (array_key_exists($k, $private) && (strlen($v)))
                    $private->$k = $v;
            }
        }
        $this->privateData[] = new \SoapVar($private, SOAP_ENC_OBJECT, PaylineSDK::SOAP_PRIVATE_DATA, PaylineSDK::PAYLINE_NAMESPACE);
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
        $WSRequest = array(
            'payment' => $this->payment($array['payment']),
            'card' => $this->card($array['card']),
            'order' => $this->order($array['order']),
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'owner' => $this->owner($array['owner'], $array['ownerAddress']),
            'privateDataList' => $this->privateData,
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
            'bankAccountData' => $this->bankAccountData($array['bankAccountData'])
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doAuthorization');
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
            'transactionID' => $array['transactionID'],
            'payment' => $this->payment($array['payment']),
            'privateDataList' => $this->privateData,
            'sequenceNumber' => $array['sequenceNumber']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doCapture');
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
            'transactionID' => $array['transactionID'],
            'payment' => $this->payment($array['payment']),
            'order' => $this->order($array['order']),
            'privateDataList' => $this->privateData
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doReAuthorization');
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
            'payment' => $this->payment($array['payment']),
            'card' => $this->card($array['card']),
            'order' => $this->order($array['order']),
            'privateDataList' => $this->privateData,
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
            'authorization' => $this->authorization($array['authorization'])
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doDebit');
    }

    /**
     * calls doRefund web service
     *
     * @param array $array
     *            associative array containing doRefund parameters
     */
    public function doRefund($array)
    {
        $WSRequest = array(
            'transactionID' => $array['transactionID'],
            'payment' => $this->payment($array['payment']),
            'comment' => $array['comment'],
            'privateDataList' => $this->privateData,
            'details' => $this->orderDetails,
            'sequenceNumber' => $array['sequenceNumber']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doRefund');
    }

    /**
     * calls doReset web service
     *
     * @param array $array
     *            associative array containing doReset parameters
     */
    public function doReset(array $array)
    {
        $WSRequest = array(
            'transactionID' => $array['transactionID'],
            'comment' => $array['comment']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doReset');
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
            'payment' => $this->payment($array['payment']),
            'card' => $this->card($array['card']),
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'privateDataList' => $this->privateData,
            'order' => $this->order($array['order']),
            'comment' => $array['comment']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doCredit');
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
            'contractNumber' => $array['contractNumber'],
            'wallet' => $this->wallet($array['wallet'], $array['address'], $array['card']),
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'owner' => $this->owner($array['owner'], $array['ownerAddress']),
            'privateDataList' => $this->privateData,
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
            'contractNumberWalletList' => $array['walletContracts']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'createWallet');
    }

    /**
     * calls updateWallet web service
     *
     * @param array $array
     *            associative array containing updateWallet parameters
     */
    public function updateWallet(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'cardInd' => $array['cardInd'],
            'wallet' => $this->wallet($array['wallet'], $array['address'], $array['card']),
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'owner' => $this->owner($array['owner'], $array['ownerAddress']),
            'privateDataList' => $this->privateData,
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure']),
            'contractNumberWalletList' => $array['walletContracts']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'updateWallet');
    }

    /**
     * calls getWallet web service
     *
     * @param array $array
     *            associative array containing getWallet parameters
     */
    public function getWallet(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'walletId' => $array['walletId'],
            'cardInd' => $array['cardInd']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getWallet');
    }

    /**
     * calls getCards web service
     *
     * @param array $array
     *            associative array containing getCards parameters
     */
    public function getCards(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'walletId' => $array['walletId'],
            'cardInd' => $array['cardInd']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getCards');
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
            'walletIdList' => $array['walletIds'],
            'cardInd' => $array['cardInd']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'disableWallet');
    }

    /**
     * calls enableWallet web service
     *
     * @param array $array
     *            associative array containing enableWallet parameters
     */
    public function enableWallet(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'walletId' => $array['walletId'],
            'cardInd' => $array['cardInd']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'enableWallet');
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
        $WSRequest = array(
            'payment' => $this->payment($array['payment']),
            'order' => $this->order($array['order']),
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'walletId' => $array['walletId'],
            'cardInd' => $array['cardInd'],
            'cvx' => $array['walletCvx'],
            'privateDataList' => $this->privateData,
            'authentication3DSecure' => $this->authentication3DSecure($array['3DSecure'])
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doImmediateWalletPayment');
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
            'payment' => $this->payment($array['payment']),
            'orderRef' => $array['orderRef'],
            'orderDate' => $array['orderDate'],
            'scheduledDate' => $array['scheduledDate'],
            'walletId' => $array['walletId'],
            'cardInd' => $array['cardInd'],
            'order' => $this->order($array['order']),
            'privateDataList' => $this->privateData
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doScheduledWalletPayment');
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
            'payment' => $this->payment($array['payment']),
            'orderRef' => $array['orderRef'],
            'orderDate' => $array['orderDate'],
            'scheduledDate' => $array['scheduledDate'],
            'walletId' => $array['walletId'],
            'cardInd' => $array['cardInd'],
            'recurring' => $this->recurring($array['recurring']),
            'privateDataList' => $this->privateData,
            'order' => $this->order($array['order'])
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doRecurrentWalletPayment');
    }

    /**
     * calls getPaymentRecord web service
     *
     * @param array $array
     *            associative array containing getPaymentRecord parameters
     */
    public function getPaymentRecord(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'paymentRecordId' => $array['paymentRecordId']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getPaymentRecord');
    }

    /**
     * calls disablePaymentRecord web service
     *
     * @param array $array
     *            associative array containing disablePaymentRecord parameters
     */
    public function disablePaymentRecord(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'paymentRecordId' => $array['paymentRecordId']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'disablePaymentRecord');
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
        $WSRequest = array(
            'payment' => $this->payment($array['payment']),
            'card' => $this->card($array['card']),
            'orderRef' => $array['orderRef'],
            'userAgent' => $array['userAgent'],
            'mdFieldValue' => $array['mdFieldValue'],
            'walletId' => $array['walletId'],
            'walletCardInd' => $array['walletCardInd'],
            'merchantName' => $array['merchantName']
        );
        if (isset($array['generateVirtualCvx'])) {
            $WSRequest['generateVirtualCvx'] = $array['generateVirtualCvx'];
        }
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'verifyEnrollment');
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
            'contractNumber' => $array['contractNumber'],
            'pares' => $array['pares'],
            'md' => $array['md'],
            'card' => $this->card($array['card'])
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'verifyAuthentication');
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
            'payment' => $this->payment($array['payment']),
            'cheque' => $this->cheque($array['cheque']),
            'order' => $this->order($array['order']),
            'privateDataList' => $this->privateData
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doScoringCheque');
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
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getEncryptionKey');
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
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getMerchantSettings');
    }

    /**
     * calls getBalance web service
     *
     * @param array $array
     *            associative array containing getBalance parameters
     */
    public function getBalance(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'cardID' => $array['cardID']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getBalance');
    }

    /**
     * calls getToken web service
     *
     * @param array $array
     *            associative array containing getToken parameters
     */
    public function getToken(array $array)
    {
        $WSRequest = array(
            'cardNumber' => $array['cardNumber'],
            'expirationDate' => $array['expirationDate'],
            'contractNumber' => $array['contractNumber']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getToken');
    }

    /**
     * calls unBlock web service
     *
     * @param array $array
     *            associative array containing getBalance parameters
     */
    public function unBlock(array $array)
    {
        $WSRequest = array(
            'transactionID' => $array['transactionID'],
            'transactionDate' => $array['transactionDate']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'unBlock');
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
            'contractNumber' => $array['contractNumber'],
            'paymentRecordId' => $array['paymentRecordId'],
            'recurring' => $this->recurring($array['recurring'])
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'updatePaymentRecord');
    }

    /**
     * calls getBillingRecord web service
     *
     * @param array $array
     *            associative array containing getBillingRecord parameters
     */
    public function getBillingRecord(array $array)
    {
        $WSRequest = array(
            'contractNumber' => $array['contractNumber'],
            'paymentRecordId' => $array['paymentRecordId'],
            'billingRecordId' => $array['billingRecordId']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'getBillingRecord');
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
            'contractNumber' => $array['contractNumber'],
            'paymentRecordId' => $array['paymentRecordId'],
            'billingRecordId' => $array['billingRecordId'],
            'billingRecordForUpdate' => $this->billingRecordForUpdate($array['billingRecordForUpdate'])
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'updateBillingRecord');
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
            'payment' => $this->payment($array['payment']),
            'creditor' => $this->creditor($array['creditor']),
            'comment' => $array['comment'],
            'transactionID' => $array['transactionID'],
            'orderID' => $array['orderID']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::DIRECT_API, 'doBankTransfer');
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
            'payment' => $this->payment($array['payment']),
            'returnURL' => $array['returnURL'],
            'cancelURL' => $array['cancelURL'],
            'order' => $this->order($array['order']),
            'notificationURL' => $array['notificationURL'],
            'customPaymentTemplateURL' => $array['customPaymentTemplateURL'],
            'selectedContractList' => $array['contracts'],
            'secondSelectedContractList' => $array['secondContracts'],
            'privateDataList' => $this->privateData,
            'languageCode' => $array['languageCode'],
            'customPaymentPageCode' => $array['customPaymentPageCode'],
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'owner' => $this->owner($array['owner'], $array['ownerAddress']),
            'securityMode' => $array['securityMode'],
            'contractNumberWalletList' => $array['walletContracts'],
            'merchantName' => $array['merchantName']
        );
        
        if (isset($array['payment']['mode'])) {
            if (($array['payment']['mode'] == "REC") || ($array['payment']['mode'] == "NX")) {
                $WSRequest['recurring'] = $this->recurring($array['recurring']);
            }
        }
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::WEB_API, 'doWebPayment');
    }

    /**
     * calls doWebPayment web service
     *
     * @param array $array
     *            associative array containing getWebPaymentDetails parameters
     */
    public function getWebPaymentDetails(array $array)
    {
        return $this->webServiceRequest($array, $array, PaylineSDK::WEB_API, 'getWebPaymentDetails');
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
            'contractNumber' => $array['contractNumber'],
            'selectedContractList' => $array['contracts'],
            'updatePersonalDetails' => $array['updatePersonalDetails'],
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'owner' => $this->owner($array['owner'], $array['ownerAddress']),
            'languageCode' => $array['languageCode'],
            'customPaymentPageCode' => $array['customPaymentPageCode'],
            'securityMode' => $array['securityMode'],
            'returnURL' => $array['returnURL'],
            'cancelURL' => $array['cancelURL'],
            'notificationURL' => $array['notificationURL'],
            'privateDataList' => $this->privateData,
            'customPaymentTemplateURL' => $array['customPaymentTemplateURL'],
            'contractNumberWalletList' => $array['walletContracts'],
            'merchantName' => $array['merchantName']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::WEB_API, 'manageWebWallet');
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
            'contractNumber' => $array['contractNumber'],
            'selectedContractList' => $array['contracts'],
            'updatePersonalDetails' => $array['updatePersonalDetails'],
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'languageCode' => $array['languageCode'],
            'customPaymentPageCode' => $array['customPaymentPageCode'],
            'securityMode' => $array['securityMode'],
            'returnURL' => $array['returnURL'],
            'cancelURL' => $array['cancelURL'],
            'notificationURL' => $array['notificationURL'],
            'privateDataList' => $this->privateData,
            'customPaymentTemplateURL' => $array['customPaymentTemplateURL'],
            'contractNumberWalletList' => $array['walletContracts']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::WEB_API, 'createWebWallet');
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
            'contractNumber' => $array['contractNumber'],
            'cardInd' => $array['cardInd'],
            'walletId' => $array['walletId'],
            'updatePersonalDetails' => $array['updatePersonalDetails'],
            'updateOwnerDetails' => $array['updateOwnerDetails'],
            'updatePaymentDetails' => $array['updatePaymentDetails'],
            'buyer' => $this->buyer($array['buyer'], $array['shippingAddress'], $array['billingAddress']),
            'languageCode' => $array['languageCode'],
            'customPaymentPageCode' => $array['customPaymentPageCode'],
            'securityMode' => $array['securityMode'],
            'returnURL' => $array['returnURL'],
            'cancelURL' => $array['cancelURL'],
            'notificationURL' => $array['notificationURL'],
            'privateDataList' => $this->privateData,
            'customPaymentTemplateURL' => $array['customPaymentTemplateURL'],
            'contractNumberWalletList' => $array['walletContracts']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::WEB_API, 'updateWebWallet');
    }

    /**
     * calls getWebWallet web service
     *
     * @param array $array
     *            associative array containing getWebWallet parameters
     */
    public function getWebWallet(array $array)
    {
        return $this->webServiceRequest($array, $array, PaylineSDK::WEB_API, 'getWebWallet');
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
        $WSRequest = array(
            'transactionId' => $array['transactionId'],
            'orderRef' => $array['orderRef'],
            'startDate' => $array['startDate'],
            'endDate' => $array['endDate'],
            'transactionHistory' => $array['transactionHistory'],
            'archiveSearch' => $array['archiveSearch']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::EXTENDED_API, 'getTransactionDetails');
    }

    /**
     * calls transactionsSearch web service
     *
     * @param array $array
     *            associative array containing getWebWallet parameters
     */
    public function transactionsSearch(array $array)
    {
        $WSRequest = array(
            'transactionId' => $array['transactionId'],
            'orderRef' => $array['orderRef'],
            'startDate' => $array['startDate'],
            'endDate' => $array['endDate'],
            'contractNumber' => $array['contractNumber'],
            'authorizationNumber' => $array['authorizationNumber'],
            'returnCode' => $array['returnCode'],
            'paymentMean' => $array['paymentMean'],
            'transactionType' => $array['transactionType'],
            'name' => $array['name'],
            'firstName' => $array['firstName'],
            'email' => $array['email'],
            'cardNumber' => $array['cardNumber'],
            'currency' => $array['currency'],
            'minAmount' => $array['minAmount'],
            'maxAmount' => $array['maxAmount'],
            'walletId' => $array['walletId'],
            'sequenceNumber' => $array['sequenceNumber'],
            'token' => $array['token']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::EXTENDED_API, 'transactionsSearch');
    }

    /**
     * calls getAlertDetails web service
     *
     * @param array $array
     *            associative array containing getAlertDetails parameters
     */
    public function getAlertDetails(array $array)
    {
        $WSRequest = array(
            'AlertId' => $array['AlertId'],
            'TransactionId' => $array['TransactionId'],
            'TransactionDate' => $array['TransactionDate']
        );
        return $this->webServiceRequest($array, $WSRequest, PaylineSDK::EXTENDED_API, 'getAlertDetails');
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
        $block = mcrypt_get_block_size('rijndael_128', 'ecb');
        $pad = $block - (strlen($message) % $block);
        $message .= str_repeat(chr($pad), $pad);
        return $this->base64_url_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $accessKey, $message, MCRYPT_MODE_ECB));
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
        $message = $this->base64_url_decode($message);
        $message = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $accessKey, $message, MCRYPT_MODE_ECB);
        $pad = ord($message[($len = strlen($message)) - 1]);
        $len = strlen($message);
        $pad = ord($message[$len - 1]);
        $return = substr($message, 0, strlen($message) - $pad);
        return $return;
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
            $error = ($lenOK ? '' : 'Length check FAILED. ') . ($crcOK ? '' : 'Checksum FAILED.');
            return false;
        }
        return $data;
    }
}
