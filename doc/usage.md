Using Payline
=============

Installation
------------

To install Monolog, simply get the code (from github or through Composer) and
configure an autoloader for the Payline namespace.


Create a PaylineSDK instance
----------------------------

Here is a basic setup to log to a file and to firephp on the DEBUG level:

```php
<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

// Create the logger
$logger = new Logger('my_logger');
// Now add some handlers
$logger->pushHandler(new StreamHandler(__DIR__.'/my_app.log', Logger::DEBUG));
$logger->pushHandler(new FirePHPHandler());

// You can now use your logger
$logger->addInfo('My logger is now ready');
```

Let's explain it. The first step is to create the logger instance which will
be used in your code. The argument is a channel name, which is useful when
you use several loggers (see below for more details about it).

The logger itself does not know how to handle a record. It delegates it to
some handlers. The code above registers two handlers in the stack to allow
handling records in two different ways.

Note that the FirePHPHandler is called first as it is added on top of the
stack. This allows you to temporarily add a logger with bubbling disabled if
you want to override other configured loggers.

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
