[![Latest Stable Version](https://poser.pugx.org/monext/payline-sdk/v/stable)](https://packagist.org/packages/monext/payline-sdk)
[![Total Downloads](https://poser.pugx.org/monext/payline-sdk/downloads)](https://packagist.org/packages/monext/payline-sdk)
[![License](https://poser.pugx.org/monext/payline-sdk/license)](https://packagist.org/packages/monext/payline-sdk)

PaylineSDK - Payline library for PHP
====================================

Usage
-----
```php
    use Payline\PaylineSDK;

    // create an instance
    $paylineSDK = new PaylineSDK($merchant_id,$access_key, $proxy_host, $proxy_port, $proxy_login, $proxy_password, $environment[, $pathLog= null[, $logLevel = Logger::INFO[, $externalLogger = null[, $defaultTimezone = "Europe/Paris"]]]]);
    /*
    $merchant_id, the merchant identifier, has to be a string.
    $environment determines in which Payline environment your request are targeted.
    It should be filled with either PaylineSDK::ENV_HOMO (for testing purpose) or PaylineSDK::ENV_PROD (real life)
    If $pathLog is null, log files will be written under default logs directory. Fill with your custom log files path
    */

    // call a web service, for example doWebPayment
    $doWebPaymentRequest = array();

    $doWebPaymentRequest['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php'; 
    $doWebPaymentRequest['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
    $doWebPaymentRequest['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';

    
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
```    

Docs
====

See the doc/ directory for more detailed documentation. More information available on http://support.payline.com.


About
=====

Requirements
------------

Compliant with PHP 5.3 and over
Requires monolog/monolog, just let Composer do the job


Author
------

Payline support - <support@payline.com>

License
-------

Payline is licensed under the LGPL-3.0+ License - see the LICENSE file for details
