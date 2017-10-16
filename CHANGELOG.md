* 4.52 (2017-10-16)
  * new avs child node in transaction object
  
* 4.51 (2017-08-11)
  * no structural changes
  
* 4.50.2 (2017-06-30)
  * new details child node in doRefundRequest object

* 4.50.1 (2017-05-09)
  * add of certificate-based authentication endpoints  

* 4.50 (2017-04-13)
  * new merchantName parameter for doWebPayment, manageWebWallet and verifyEnrollment (name displayed on ACS)
  * new attribute paymentData for object Card (used by Apple Pay only)

* 4.49 (2017-01-05)
  * removal of autoload inclusion in main PaylineSDK.php file
  * fix of `SOAP_DOCUMENT` and `SOAP_LITERAL` constants use in main PaylineSDK.php file
  * new optional parameters defaultTimezone and externalLogger for PaylineSDK class constructor

* 4.48 (2016-09-13)
  * new properties version and TransactionDate for getAlertDetailsRequest
    
* 4.47.1 (2016-08-04)
  * new property generateVirtualCvx for verifyEnrollmentRequest 
  
* 4.47 (2016-06-16)
  * new buyer properties :
  	- deviceFingerprint
  	- isBot
  	- isIncognito
  	- isBehindProxy
  	- isFromTor
  	- isEmulator
  	- isRooted
  	- hasTimezoneMismatch
  * new property cardBrand for payment and wallet objets
  * new property version for getCardsRequest

* 4.46.1 (2016-06-06)
  * contractNumberWalletList can have 99 contractNumberWallet elements

* 4.46 (2016-05-10)

  * add of PaResStatus and VeResStatus (Authentication3DSecure class)
  * response format : child nodes of cardinality higher to 1 are sent in an integer-indexed array, in any cases (1 element of index 0 if node has only 1 child).
  This applies to nodes :
	- cards (son of cardsList)
	- billingRecord (son of billingRecordList)
	- walletId (son of walletIdList)
	- transaction (son of transactionList)
	- pointOfSell (son of listPointOfSell)
	- contract (son of contracts)
	- customPaymentPageCode (son of customPaymentPageCodeList)
	- function (son of functions)
	- details (son of details)
	- privateData (son of privateDataList)
	- associatedTransactions (son of associatedTransactionsList)
	- statusHistory (son of statusHistoryList)
	- paymentAdditional (son of paymentAdditionalList)
	- CustomerTrans (son of CustomerTransHist)
	- PaymentMeansTrans (son of PaymentMeansTransHist)
	- AlertsTrans (son of AlertsTransHist) 

* 4.45.1 (2016-03-10)

  * add of Recurring class
  * require any 1.* monolog/monolog version
  * date_default_timezone_set("Europe/Paris") in PaylineSDK class constructor
  * add widget related constants (js and css url path)

* 4.45 (2016-01-22)

  * add of softDescriptor payment attribute (Payment class)

* 4.44.1 (2016-01-20)

  * Fisrt Payline release deployed on Composer
