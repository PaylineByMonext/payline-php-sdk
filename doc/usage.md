Using Payline
=============

Installation
------------

To install PaylineSDK, simply get the code (from github or through Composer) and
configure an autoloader for the Payline namespace.


Create a PaylineSDK instance
----------------------------

Here is a basic setup to create a PaylineSDK instance

```php
 use Payline\PaylineSDK;

    // create an instance
    $paylineSDK = new PaylineSDK($merchant_id, $access_key, $proxy_host, $proxy_port, $proxy_login, $proxy_password, $environment[, $pathLog= null[, $logLevel = Logger::INFO]]);
    /*
    $environment determines in which Payline environment your request are targeted.
    It should be filled with either PaylineSDK::ENV_HOMO (for testing purpose) or PaylineSDK::ENV_PROD (real life)
    If $pathLog is null, log files will be written under default logs directory. Fill with your custom log files path
    */
```

Call a Payline web service
--------------------------

All Payline web services are available through a PaylineSDK instance. Here are two dummy examples :

### doWebPayment

This web service returns a secure URL to which the customer has to be redirected in order to enter his payment information.

```php
<?php
$doWebPaymentRequest = array();
    
// PAYMENT
$doWebPaymentRequest['payment']['amount'] = 1000; // this value has to be an integer amount is sent in cents
$doWebPaymentRequest['payment']['currency'] = 978; // ISO 4217 code for euro
$doWebPaymentRequest['payment']['action'] = 101; // 101 stand for "authorization+capture"
$doWebPaymentRequest['payment']['mode'] = 'CPT'; // one shot payment

// ORDER
$doWebPaymentRequest['order']['ref'] = 'myOrderRef_35656'; // the reference of your order
$doWebPaymentRequest['order']['amount'] = 1000; // may differ from payment.amount if currency is different
$doWebPaymentRequest['order']['currency'] = 978; // ISO 4217 code for euro

// CONTRACT NUMBERS
$doWebPaymentRequest['payment']['contractNumber'] = '1234567';
	
$doWebPaymentResponse = $paylineSDK->doWebPayment($doWebPaymentRequest);

// $doWebPaymentResponse['result']['code'] contains the call result code.
// in case of success (code 00000) :
// - $doWebPaymentResponse['redirectURL'] contains the secure payment page URL
// - $doWebPaymentResponse['token'] contains the web payment session unique identifier 

```

### getWebPaymentDetails

This web service returns the result of a web payment session, given its token.

```php
<?php
$getWebPaymentDetailsRequest = array();
$getWebPaymentDetailsRequest['token'] = $_GET['token']; // web payment session unique identifier

$getWebPaymentDetailsResponse = $paylineSDK->getWebPaymentDetails($getWebPaymentDetailsRequest);

```
