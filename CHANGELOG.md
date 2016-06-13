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
