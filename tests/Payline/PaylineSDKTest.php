<?php

namespace test\Payline;
use Monolog\Logger;
use Payline\PaylineSDK;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use WireMock\Client\WireMock;

class PaylineSDKTest extends TestCase
{
    // Given
    const DEFAULT_HOST_URL = 'localhost';
    const DEFAULT_HOST_PORT = '8443'; // Port déclaré dans le Docker

    const merchant_id = '1111111';
    const access_key = 'xxxyyy222';
    const encoded_authorization_header = "Basic MTExMTExMTp4eHh5eXkyMjI=";
    const environment = PaylineSDK::ENV_HOMO;
    const logLvl = Logger::API;

    const DIRECT_PAYMENT_API = 'DirectPaymentAPI';
    const PHP_PROJECT_CURRENT_VERSION = '- PHP SDK 4.76';

    /**
     * @var WireMock
     */
    private static $wireMock;


    /**
     * Test du constructeur  avec valeurs minimales et ENV_HOMO
     */
    public function testNewPaylineSDKInstance()
    {
        // Test - Try to create an instance
        $paylineSDK = $this->createDefaultPaylineSDK();

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals(self::logLvl, $this->getProtectedProperty($paylineSDK, 'logLevel'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'loggerPath'));
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'logger'));

        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertEquals(self::merchant_id, $soapClientOptions['login']);
        $this->assertEquals(self::access_key, $soapClientOptions['password']);
        $this->assertNull($soapClientOptions['proxy_host']);
        $this->assertNull($soapClientOptions['proxy_port']);
        $this->assertNull($soapClientOptions['proxy_login']);
        $this->assertNull($soapClientOptions['proxy_password']);
        $this->assertTrue($soapClientOptions['trace']);
        $this->assertEquals('https://homologation.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertEquals('https://homologation-payment.payline.com/services/servicesendpoints/SOAP/1111111', $this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertTrue(empty($soapClientOptions['stream_context_to_create']));
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'orderDetails'));
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'privateData'));
    }

    /**
     * Test du constructeur sur environnement ENV_HOMO_CC
     */
    public function testNewPaylineSDKInstance_withENV_HOMO_CC()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_HOMO_CC, $pathLog= null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://homologation-cc.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertTrue(empty($soapClientOptions['stream_context_to_create']));
    }

    /**
     * Test du constructeur sur environnement ENV_PROD
     */
    public function testNewPaylineSDKInstance_withENV_PROD()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_PROD, $pathLog= null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://services.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertEquals('https://payment.payline.com/services/servicesendpoints/SOAP/1111111', $this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertTrue(empty($soapClientOptions['stream_context_to_create']));
    }

    /**
     * Test du constructeur sur environnement ENV_PROD_CC
     */
    public function testNewPaylineSDKInstance_withENV_PROD_CC()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_PROD_CC, $pathLog= null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://services-cc.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertTrue(empty($soapClientOptions['stream_context_to_create']));
    }

    /**
     * Test du constructeur sur environnement ENV_DEV
     */
    public function testNewPaylineSDKInstance_withENV_DEV()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_DEV, $pathLog= null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://ws.dev.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertFalse(empty($soapClientOptions['stream_context_to_create']));
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer']);
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer_name']);
    }

    /**
     * Test du constructeur sur environnement ENV_INT
     */
    public function testNewPaylineSDKInstance_withENV_INT()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_INT, $pathLog= null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://ws.int.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertFalse(empty($soapClientOptions['stream_context_to_create']));
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer']);
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer_name']);
    }

    /**
     * Test du constructeur avec paramétrage proxy
     */
    public function testConstructWithAllParams()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, $pathLog= null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals(self::logLvl, $this->getProtectedProperty($paylineSDK, 'logLevel'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'loggerPath'));
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'logger'));

        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertEquals(self::merchant_id, $soapClientOptions['login']);
        $this->assertEquals(self::access_key, $soapClientOptions['password']);
        $this->assertEquals('http://localhost:9999', $soapClientOptions['proxy_host']);
        $this->assertEquals('55', $soapClientOptions['proxy_port']);
        $this->assertEquals('proxy login', $soapClientOptions['proxy_login']);
        $this->assertEquals('proxy password', $soapClientOptions['proxy_password']);
        $this->assertTrue($soapClientOptions['trace']);
        $this->assertEquals('https://homologation.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertEquals('https://homologation-payment.payline.com/services/servicesendpoints/SOAP/1111111', $this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'orderDetails'));
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'privateData'));
    }

    /**
     * Test de la fonction {@link  PaylineSDK#setSoapOptions}
     * @throws \Exception
     */
    public function testSetSoapOptions()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, $pathLog= null, self::logLvl);
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertNull($soapClientOptions['KeyAAA']);


        // Test
        $paylineSDK = $paylineSDK->setSoapOptions('KeyAAA', 'ValueBBB');

        // Test
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertEquals('ValueBBB', $soapClientOptions['KeyAAA']);

    }

    /**
     * Test de la fonction {@link  PaylineSDK#setFailoverOptions}
     * @throws \Exception
     */
    public function testSetFailoverOptionsAndReset()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, $pathLog= null, self::logLvl);
        $failoverOptions = $paylineSDK->getFailoverOptions();
        $this->assertNull($failoverOptions['KeyAAA']);


        // Test
        $paylineSDKNew = $paylineSDK->setFailoverOptions('KeyAAA', 'ValueBBB');

        // Test
        $failoverOptions = $paylineSDK->getFailoverOptions();
        $this->assertEquals('ValueBBB', $failoverOptions['KeyAAA']);


        // Test du reset
        $paylineSDK->resetFailoverOptions();
        $failoverOptions = $paylineSDK->getFailoverOptions();
        $this->assertNull($failoverOptions['KeyAAA']);
    }

    /**
     * Test de la fonction {@link  PaylineSDK#setFailoverOptions}
     */
    public function testReset()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, $pathLog= null, self::logLvl);
        $privateData = array();
        $privateData['privKey'] = 'privValue';
        $paylineSDK->addPrivateData($privateData);
        $orderDetails = array();
        $orderDetails['orderKey'] = 'orderValue';
        $paylineSDK->addOrderDetail($orderDetails);


        // Test
        $paylineSDK = $paylineSDK->reset();

        // Test
        $lastSoapCallData = (array) $this->getProtectedProperty($paylineSDK, 'lastSoapCallData');
        $orderDetails = (array) $this->getProtectedProperty($paylineSDK, 'orderDetails');
        $this->assertTrue(empty($orderDetails));
        $this->assertTrue(empty($lastSoapCallData));
        $this->assertTrue(empty($paylineSDK->privateDataList()));
    }


    /**
     * Test de l'appel du  {@link PaylineSDK::doAuthorization()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoAuthorization()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doAuthor/doAuthorizationResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doAuthor/doAuthorizationRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test - Call doAuthorization
        $doAuthorizationRequest = array();
        $doAuthorizationRequest['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php';
        $doAuthorizationRequest['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
        $doAuthorizationRequest['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';
        // PAYMENT
        $doAuthorizationRequest = $this->addPaymentData($doAuthorizationRequest); // this value has to be an integer amount is sent in cents
        // ORDER
        $doAuthorizationRequest = $this->addOrderData($doAuthorizationRequest);
        // CARD
        $doAuthorizationRequest = $this->addCardData($doAuthorizationRequest);

        // Test
        $doAuthorizationResponse = $paylineSDK->doAuthorization($doAuthorizationRequest);

        // Then
        $this->assertNotNull($doAuthorizationResponse);
        // Should get ResultCode OK
        $this->checkResponseResultOK($doAuthorizationResponse['result']);

        $this->checkResponseTransaction($doAuthorizationResponse['transaction'], '14340105742592', '05/12/23 20:27:42',
            '0', '0', '', '', 'N', null, '4', '');
        $this->checkResponseCard($doAuthorizationResponse['card'], '444433XXXXXXXX11', 'CB', '1224', 'JEAN CLAUDE', '444433LfGjXu1111', null);
        $this->assertEquals('CB', $doAuthorizationResponse['contractNumber']);
        $this->assertEquals('000001387237050', $doAuthorizationResponse['linkedTransactionId']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doAuthorization');
    }

    /**
     * Test de l'appel du  {@link PaylineSDK::doAuthorization()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoCapture()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doCapture/doCaptureResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doCapture/doCaptureRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        $doCaptureRequest = array();
        $doCaptureRequest = $this->addPaymentData($doCaptureRequest);
        $doCaptureRequest['transactionID'] = 'TrsId_35656';

        // Test
        $response = $paylineSDK->doCapture($doCaptureRequest);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);

        $this->checkResponseTransaction($response['transaction'], '14341164420893', '06/12/23 16:44:20');
        $this->assertEquals('0', $response['reAuthorization']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doCapture');
    }

    /**
     * Test de l'appel du  {@link PaylineSDK::doAuthorization()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoReAuthor()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doReAuthor/doReAuthorizationResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doReAuthor/doReAuthorizationRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addOrderData($request);
        $request['media'] = 'media pc';

        // Test
        $response = $paylineSDK->doReAuthorization($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);

        $this->checkResponseTransaction($response['transaction'], '1434297987545643131272', '07/12/23 13:21:01');
        $this->checkResponseCard($response['card'], '4XXXXXXXXXXXXXX7', 'CB', '0420');
        $this->checkResponseExtendedCard($response['extendedCard'], 'FRA', '30002 - CREDIT LYONNAIS', 'CB', 'CB', 'Visa +++');

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doReAuthorization');
    }


    /**
     * Test de l'appel du  {@link PaylineSDK::doDebit()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoDebit()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doDebit/doDebitResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doDebit/doDebitRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addCardData($request);
        $request = $this->addOrderData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $request = $this->addAuthorizationData($request);
        $request = $this->addSubMerchantData($request);
        // CARD
        $request['media'] = 'media pc';

        // Test
        $response = $paylineSDK->doDebit($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);

        $this->checkResponseTransaction($response['transaction'], '14341052242963', '06/12/2023 06:22');
        $this->checkResponseCard($response['card'], '497010XXXXXXXX69', 'CB', '1220', null, '4970toarLkqb0469');
        $this->checkResponseExtendedCard($response['extendedCard'], 'FRA', '20041 - LA POSTE', 'CB', 'CB');

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doDebit');
    }

    /**
     * Test de l'appel du  {@link PaylineSDK::doRefund()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoRefund()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doRefund/doRefundResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doRefund/doRefundRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addCardData($request);
        $request = $this->addOrderData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $request = $this->addAuthorizationData($request);
        $request = $this->addSubMerchantData($request);
        $request['comment'] = 'comment cccc';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';
        $request['sequenceNumber'] = 'sequenceNumber value';

        // Test
        $response = $paylineSDK->doRefund($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);

        $this->checkResponseTransaction($response['transaction'], '14341170746002', '06/12/23 17:07:46');

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doRefund');
    }


    /**
     * Test de l'appel du  {@link PaylineSDK::doReset()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoReset()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doReset/doResetResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doReset/doResetRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        $request = array();
        $request['version'] = '99';
        $request['transactionID'] = 'TrsIdyyyy';
        $request['comment'] = 'comment cccc';
        $request['media'] = 'media pc';
        $request['amount'] = '1000';
        $request['currency'] = '948';

        // Test
        $response = $paylineSDK->doReset($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->checkResponseTransaction($response['transaction'], '14342095504451', '07/12/23 09:55:04');

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doReset');
    }

    /**
     * Test de l'appel du  {@link PaylineSDK::doCredit()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoCredit()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doCredit/doCreditResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doCredit/doCreditRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addCardData($request);
        $request = $this->addOrderData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $request = $this->addAuthorizationData($request);
        $request = $this->addSubMerchantData($request);
        $request['comment'] = 'comment cccc';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->doCredit($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);

        $this->checkResponseTransaction($response['transaction'], '14341172113059', '06/12/2023 18:21');
        $this->checkResponseCard($response['card'], '472686XXXXXXXX50', 'CB', '1230', null, '472686NRXkjN0150');
        $this->checkResponseExtendedCard($response['extendedCard'], 'FRA', '18029 - BNP Paribas Personal Finance', 'CB', 'CB');

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doCredit');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::createWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testCreateWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/createWallet/createWalletResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/createWallet/createWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        $request = array();
        $request = $this->addWalletData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addOwnerData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $request['comment'] = 'comment cccc';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->createWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);
        $this->checkResponseCard($response['card'], '111122XXXXXXXX44', 'CB', '0628', 'TEST');
        $this->checkResponseExtendedCard($response['extendedCard'], null, null, 'CB', null);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'createWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::updateWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testUpdateWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/updateWallet/updateWalletResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/updateWallet/updateWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addWalletData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addOwnerData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $contractNumberWalletList = array();
        $contractNumberWalletList['contractNumberWallet'] = 'APPLE_PAY';
        $request['contractNumberWalletList'] = $contractNumberWalletList;
        $request['transactionID'] = 'TrsIdyyyy';
        $request['comment'] = 'comment cccc';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->updateWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'updateWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::getWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testGetWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/getWallet/getWalletResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/getWallet/getWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request['version'] = '90';
        $request['contractNumber'] = 'CB';
        $request['walletId'] = 'walletIdzzzz';
        $request['cardInd'] = '2';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->getWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);
        $this->checkResponsePrivateDataListContains($response['privateDataList']);
        $this->checkResponseWallet($response['wallet'], 'NR_RDC_WALLET_059095650', 'butet', 'mickael', null, 'Y');
        $this->checkResponseCard($response['wallet']['card'], '1XXXXXXXXXXXX4444', 'CB', '0628', 'TEST', '11sdfsdfggghgfh44', null, '091080');
        $this->checkResponseAddress($response['wallet']['shippingAddress']);
        $this->checkResponseExtendedCard($response['extendedCard'], null, null, 'CB', 'CB');
        $this->assertEquals('CB', $response['contractNumberWalletList']['contractNumberWallet']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'getWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::getCards()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testGetCards()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/getCards/getCardsResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/getCards/getCardsRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request['version'] = '90';
        $request['contractNumber'] = 'CB';
        $request['walletId'] = 'walletIdzzzz';
        $request['cardInd'] = '2';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->getCards($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);

        foreach ($response['cardsList'] as $cards) {
            $this->checkResponseWallet($cards, '1208052947517', 'Jean Marie', 'lecom', 'test.auto@monext.net', 'Y', '1');
            $this->checkResponseCard($cards['card'], '4XXXXXXXXXXXXX83', 'CB', '1131');
            $this->checkResponseAddress($cards['shippingAddress'], null, 'yolande', null, null, null, '123 rue dici', null,
                null, 'Aix en provence', '13290', 'FR', '0611223344');
            $this->checkResponseExtendedCard($cards['extendedCard'], 'FRA', null, 'VISA', 'CB');
        }

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'getCards');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::disableWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDisableWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/disableWallet/disableWalletResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/disableWallet/disableWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addWalletData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addOwnerData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $contractNumberWalletList = array();
        $contractNumberWalletList['contractNumberWallet'] = 'APPLE_PAY';
        $request['contractNumberWalletList'] = $contractNumberWalletList;
        $request['transactionID'] = 'TrsIdyyyy';
        $request['comment'] = 'comment cccc';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->disableWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'disableWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::enableWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testEnableWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/enableWallet/enableWalletResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/enableWallet/enableWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addWalletData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addOwnerData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $contractNumberWalletList = array();
        $contractNumberWalletList['contractNumberWallet'] = 'APPLE_PAY';
        $request['contractNumberWalletList'] = $contractNumberWalletList;
        $request['transactionID'] = 'TrsIdyyyy';
        $request['comment'] = 'comment cccc';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->enableWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'enableWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::doImmediateWalletPayment()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoImmediateWalletPayment()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doImmediateWalletPayment/doImmediateWalletPaymentResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doImmediateWalletPayment/doImmediateWalletPaymentRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addOrderData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addRecurringData($request);
        $request = $this->addthreeDSInfoData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $request = $this->addSubMerchantData($request);
        $request['version'] = '90';
        $request['cvx'] = '123';
        $request['walletId'] = 'walletIdzzzz';
        $request['cardInd'] = '2';
        $request['travelFileNumber'] = 'TravelNumberyyyy';
        $request['linkedTransactionId'] = 'LinkedTrsIdyyyy';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';

        // Test
        $response = $paylineSDK->doImmediateWalletPayment($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->checkResponseTransaction($response['transaction'], 'PPL231143555582253', '07/12/23 17:49:43', null, null);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doImmediateWalletPayment');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::doScheduledWalletPayment()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoScheduledWalletPayment()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doScheduledWalletPayment/doScheduledWalletPaymentResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doScheduledWalletPayment/doScheduledWalletPaymentRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addOrderData($request);
        $request = $this->addRecurringData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $request = $this->addSubMerchantData($request);
        $request['version'] = '90';
        $request['cvx'] = '123';
        $request['walletId'] = 'walletIdzzzz';
        $request['cardInd'] = '2';
        $request['travelFileNumber'] = 'TravelNumberyyyy';
        $request['linkedTransactionId'] = 'LinkedTrsIdyyyy';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';
        $request['orderRef'] = 'OrderRefbbbb';
        $request['orderDate'] = '15/01/2020 10:00';
        $request['scheduledDate'] = '15/01/1990';

        // Test
        $response = $paylineSDK->doScheduledWalletPayment($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);
        $this->assertEquals('190946158', $response['paymentRecordId']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doScheduledWalletPayment');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::doRecurrentWalletPayment()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoRecurrentWalletPayment()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doRecurrentWalletPayment/doRecurrentWalletPaymentResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doRecurrentWalletPayment/doRecurrentWalletPaymentRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addOrderData($request);
        $request = $this->addRecurringData($request);
        $request = $this->addAuthentication3DSecureData($request);
        $request = $this->addSubMerchantData($request);
        $request['version'] = '90';
        $request['cvx'] = '123';
        $request['walletId'] = 'walletIdzzzz';
        $request['cardInd'] = '2';
        $request['travelFileNumber'] = 'TravelNumberyyyy';
        $request['linkedTransactionId'] = 'LinkedTrsIdyyyy';
        $request['media'] = 'media pc';
        $request['miscData'] = 'miscData value';
        $request['orderRef'] = 'OrderRefbbbb';
        $request['orderDate'] = '15/01/2020 10:00';
        $request['scheduledDate'] = '15/01/1990';

        // Test
        $response = $paylineSDK->doRecurrentWalletPayment($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);
        $this->assertEquals('1904932999222051', $response['paymentRecordId']);
        $billingRecordList = $response['billingRecordList'];
        $this->assertNotEmpty($billingRecordList['billingRecord']);
        $this->assertIsArray($billingRecordList['billingRecord']);
        $this->assertEquals(3, count($billingRecordList['billingRecord']));

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doRecurrentWalletPayment');
    }

    /**
     * Fonction utilisée pour modifier la valeur d'une propriété par réfléxion
     * @throws ReflectionException
     */
    function setProtectedProperty($obj, $property, $value) {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    /**
     * Fonction utilisée pour récupérer la valeur d'une propriété par réfléxion
     * @throws ReflectionException
     */
    function getProtectedProperty($obj, $property) {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * @param PaylineSDK $paylineSDK
     * @param $xmlResponse
     * @param string $soapAction
     * @return void
     * @throws ReflectionException
     */
    function createWiremock(PaylineSDK $paylineSDK, $xmlResponse, string $soapAction)
    {
        // Update du enpoint SOAP
        $endpointUrl = 'http://' . self::DEFAULT_HOST_URL . ':' . self::DEFAULT_HOST_PORT . '/V4/services/';
        $this->setProtectedProperty($paylineSDK, 'webServicesEndpoint', $endpointUrl);

        // on check si le serveur est démarré
        if (!isset(self::$wireMock)) {
            // Create Wiremock Serveur
            self::$wireMock = WireMock::create(self::DEFAULT_HOST_URL, self::DEFAULT_HOST_PORT);
            $this->assertTrue(self::$wireMock->isAlive(5, true));

        }

        // Avant le stub, on delete tous les autres stubs
        self::$wireMock->reset();

        // Stub out a request
        self::$wireMock ->stubFor(WireMock::post(WireMock::urlEqualTo('/V4/services/'. $soapAction))
            ->willReturn(WireMock::aResponse()
                ->withBody($xmlResponse)));
    }


    /**
     * Fonction qui permet de vérifi l'appel HTTP avec tous les éléments :
     * - Headers
     * - Action
     * - Xml body
     * - ...
     *
     * Si le test échoue il faut comparer manuellement les XMLs qui sont recording dans le docker : http://localhost:8443/__admin/requests
     *
     * @return void
     * @throws \WireMock\Client\ClientException
     */
    public function verifyCallRequest($soapApiPath, $xmlExpectedRequest, $soapAction): void
    {
        // L'appel doit etre un post à la bonne URL.
        $requestPatternBuilder = WireMock::postRequestedFor(WireMock::urlEqualTo('/V4/services/' . $soapApiPath))
            ->withHeader('User-Agent', WireMock::equalTo('PHP'))
            ->withHeader('Content-Type', WireMock::equalTo('text/xml; charset=UTF-8'))
            ->withHeader('SOAPAction', WireMock::containing($soapAction))
            ->withHeader('Authorization', WireMock::equalTo(self::encoded_authorization_header))
            ->withHeader('version', WireMock::equalTo(self::PHP_PROJECT_CURRENT_VERSION))
            ->withRequestBody(WireMock::equalToXml($xmlExpectedRequest, false))
        ;

        try {
            self::$wireMock->verify($requestPatternBuilder);
        } catch (\Exception $e) {
            // Si on a une exception dans le verify, on fait appel a Wiremock pour avoir le stub le plus proche (https://wiremock.org/docs/verifying/#near-misses)
            // Afin d'afficher les infos pour débugguage
            $findNearMissesResult = self::$wireMock->findNearMissesFor($requestPatternBuilder);
            echo $e->getMessage();
            $this->fail('Wiremock verifiy in error. Near misses : ' . serialize($findNearMissesResult->getNearMisses()[0]));
        }
    }

    /**
     * @param $filePath
     * @return false|string
     */
    public function getFullFilePath($filePath)
    {
        return __DIR__ . '/../resources/' . $filePath;
    }


    /**
     * @param $result
     * @return void
     */
    public function checkResponseResultOK($result): void
    {
        $this->checkResponseResult($result, '00000', 'ACCEPTED', 'Transaction approved');
    }

    /**
     * @param $result
     * @return void
     */
    public function checkResponseResult02500($result): void
    {
        $this->checkResponseResult($result, '02500', 'ACCEPTED', 'Operation Successfull');
    }

    /**
     * @param $result
     * @return void
     */
    public function checkResponseResult($result, $resultCode, $resultShortMessage, $resultLongMessage): void
    {
        $this->assertEquals($resultCode, $result['code']);
        $this->assertEquals($resultShortMessage, $result['shortMessage']);
        $this->assertEquals($resultLongMessage, $result['longMessage']);
    }

    /**
     * Fonction qui permet de charger un fichier
     * @return false|string
     */
    public function loadXmlResponseFromFile($resourceFilePath)
    {
        $xmlResponseFilePath = $this->getFullFilePath($resourceFilePath);
        return file_get_contents($xmlResponseFilePath);
    }

    /**
     * @param $transaction
     * @param $trsId
     * @param $trsDate
     * @param string $idDuplicated
     * @param string $isPossibleFraud
     * @param string $fraudResult
     * @param null $explanation
     * @param null $threeDs
     * @param null $score
     * @param null $avsResult
     * @param null $avsResultAcq
     * @return void
     */
    public function checkResponseTransaction($transaction,
                                             $trsId,
                                             $trsDate,
                                             $idDuplicated='0',
                                             $isPossibleFraud='0',
                                             $fraudResult=null,
                                             $explanation=null,
                                             $threeDs=null,
                                             $score=null,
                                             $avsResult=null,
                                             $avsResultAcq=null): void
    {
        $this->assertEquals($trsId, $transaction['id']);
        $this->assertEquals($trsDate, $transaction['date']);
        $this->assertEquals($idDuplicated, $transaction['isDuplicated']);
        $this->assertEquals($isPossibleFraud, $transaction['isPossibleFraud']);

        if (isset($fraudResult)) {
            $this->assertEquals($fraudResult, $transaction['fraudResult']);
        }
        if (isset($explanation)) {
            $this->assertEquals($explanation, $transaction['explanation']);
        }
        if (isset($threeDs)) {
            $this->assertEquals($threeDs, $transaction['threeDSecure']);
        }
        if (isset($score)) {
            $this->assertEquals($score, $transaction['score']);
        }
        if (isset($avsResult)) {
            $this->assertEquals($avsResult, $transaction['avs']['result']);
        }
        if (isset($avsResultAcq)) {
            $this->assertEquals($avsResultAcq, $transaction['avs']['resultFromAcquirer']);
        }
    }

    public function checkResponseCard($card,
                                      $number,
                                      $type,
                                      $expiDate,
                                      $cardholder=null,
                                      $token=null,
                                      $panType=null,
                                      $ownerBirthday=null): void
    {
        $this->assertEquals($number, $card['number']);
        $this->assertEquals($type, $card['type']);
        $this->assertEquals($expiDate, $card['expirationDate']);
        $this->assertEquals($ownerBirthday, $card['ownerBirthdayDate']);

        if (isset($cardholder)) {
            $this->assertEquals($cardholder, $card['cardholder']);
        }
        if (isset($token)) {
            $this->assertEquals($token, $card['token']);
        }
        if (isset($panType)) {
            $this->assertEquals($panType, $card['panType']);
        }
    }

    public function checkResponseExtendedCard($extendedCard,
                                              $country,
                                              $bank,
                                              $type,
                                              $network,
                                              $product = null): void
    {
        $this->assertEquals($country, $extendedCard['country']);
        $this->assertEquals($bank, $extendedCard['bank']);
        $this->assertEquals($type, $extendedCard['type']);
        $this->assertEquals($network, $extendedCard['network']);
        $this->assertEquals($product, $extendedCard['product']);
    }

    public function checkResponsePrivateDataListContains($privateDataList,
                                                         $key = null,
                                                         $value = null): void
    {
        if ($key == null) {
            $this->assertEmpty($privateDataList);
        } else {
            $this->assertNotEmpty($privateDataList);
            $this->assertIsArray($privateDataList);
            $found = false;
            foreach ($privateDataList as $privateData) {

                if ($privateData[$key] == $value) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                self::fail("PrivateData not found in privateDataList : key=" . $key . ' - value=' . $value);
            }
        }
    }

    public function checkResponseWallet($wallet,
                                        $walletId = null,
                                        $lastName = null,
                                        $firstName = null,
                                        $email = null,
                                        $default = null,
                                        $cardInd = null,
                                        $comment = null,
                                        $cardBrand = null): void
    {

            $this->assertEquals($walletId, $wallet['walletId']);
            $this->assertEquals($lastName, $wallet['lastName']);
            $this->assertEquals($firstName, $wallet['firstName']);
            $this->assertEquals($email, $wallet['email']);
            $this->assertEquals($default, $wallet['default']);
            $this->assertEquals($cardInd, $wallet['cardInd']);
            $this->assertEquals($comment, $wallet['comment']);
            $this->assertEquals($cardBrand, $wallet['cardBrand']);
    }

    public function checkResponseAddress($address,
                                        $title = null,
                                        $name = null,
                                        $lastName = null,
                                        $firstName = null,
                                        $email = null,
                                        $street1 = null,
                                        $street2 = null,
                                        $streetNumber = null,
                                        $cityName = null,
                                        $zipCode = null,
                                        $country = null,
                                        $phone = null): void
    {

            $this->assertEquals($title, $address['title']);
            $this->assertEquals($name, $address['name']);
            $this->assertEquals($lastName, $address['lastName']);
            $this->assertEquals($firstName, $address['firstName']);
            $this->assertEquals($email, $address['email']);
            $this->assertEquals($street1, $address['street1']);
            $this->assertEquals($street2, $address['street2']);
            $this->assertEquals($streetNumber, $address['streetNumber']);
            $this->assertEquals($cityName, $address['cityName']);
            $this->assertEquals($zipCode, $address['zipCode']);
            $this->assertEquals($country, $address['country']);
            $this->assertEquals($phone, $address['phone']);
    }




    /**
     * @param array $request
     * @return array
     */
    public function addPaymentData(array $request): array
    {
        $request['payment']['amount'] = 1000; // this value has to be an integer amount is sent in cents
        $request['payment']['currency'] = 978; // ISO 4217 code for euro
        $request['payment']['action'] = 100; // 101 stand for "authorization+capture"
        $request['payment']['mode'] = 'CPT'; // one shot payment
        $request['payment']['contractNumber'] = 'CB';
        $request['payment']['differedActionDate'] = 'DiffActDate 11/11/2011';
        $request['payment']['method'] = 'Method';
        $request['payment']['cardBrand'] = 'VISA';
        return $request;
    }

    /**
     * @return PaylineSDK
     */
    public function createDefaultPaylineSDK(): PaylineSDK
    {
        return new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            self::environment, $pathLog = null, self::logLvl);
    }

    /**
     * Add Order info to request
     * @param array $request
     * @return array
     */
    public function addOrderData(array $request): array
    {
        $request['order']['ref'] = 'myOrderRef_35656'; // the reference of your order
        $request['order']['amount'] = 1000; // may differ from payment.amount if currency is different
        $request['order']['currency'] = 978;
        $request['order']['origin'] = 'BIGBANG';
        $request['order']['country'] = 'FRANCE';
        $request['order']['taxes'] = '50%';
        $request['order']['date'] = '15/12/2014 12:20';
        $request['order']['deliveryTime'] = 'entre 6h et 22h';
        $request['order']['deliveryMode'] = 'Par camion';
        $request['order']['deliveryExpectedDate'] = 'Demain';
        $request['order']['deliveryExpectedDelay'] = '200';
        $request['order']['deliveryCharge'] = 'DeliveryChareg 8';
        $request['order']['bookingReference'] = 'BookingRef ppp';
        $request['order']['orderDetail'] = 'OrderDetails 9999';
        $request['order']['orderOTA'] = 'OrderOTA supp';
        return $request;
    }

    /**
     * Add Adress info to requestElement
     * @param array $requestElement
     * @return array
     */
    public function addAddressData(array $requestElement, $suffix = null): array
    {
        $requestElement['title'] = 'Maitre de lunivers';
        $requestElement['name'] = 'name ' . $suffix;
        $requestElement['firstName'] = 'firstname '  . $suffix;
        $requestElement['lastName'] = 'lastname' . $suffix;
        $requestElement['street1'] = '123 rue de nowhere';
        $requestElement['street2'] = 'Street 2 nowhere';
        $requestElement['streetNumber'] = '999';
        $requestElement['cityName'] = 'city ' . $suffix;
        $requestElement['zipCode'] = '11000';
        $requestElement['country'] = 'FR';
        $requestElement['phone'] = '000000000';
        $requestElement['state'] = 'LA';
        $requestElement['county'] = 'FR';
        $requestElement['phoneType'] = 'Nokia3310 ' . $suffix;
        $requestElement['addressCreateDate'] = '00/00/0000';
        $requestElement['email'] = 'toto@fait.dubateau ' . $suffix;

        return $requestElement;
    }

    /**
     * Add Buyer info to requestElement
     * @param array $request
     * @return array
     */
    public function addBuyerData(array $request): array
    {
        $request['buyer']['title'] = 'Maitre de lunivers';
        $request['buyer']['lastName'] = 'lastname';
        $request['buyer']['firstName'] = 'firstname ';
        $request['buyer']['email'] = 'toto@fait.dubateau ';
        $request['buyer']['accountCreateDate'] = '';
        $request['buyer']['accountAverageAmount'] = '';
        $request['buyer']['accountOrderCount'] = '';
        $request['buyer']['walletId'] = 'xxx222';
        $request['buyer']['walletDisplayed'] = 'Wallet XX';
        $request['buyer']['walletCardInd'] = '2';
        $request['buyer']['ip'] = '127.0.0.69';
        $request['buyer']['mobilePhone'] = '060000000';
        $request['buyer']['customerId'] = 'customerId';
        $request['buyer']['legalStatus'] = 'pacs';
        $request['buyer']['legalDocumentType'] = 'papier';
        $request['buyer']['legalDocument'] = 'le papier';
        $request['buyer']['birthDate'] = '01/01/1900';
        $request['buyer']['fingerprintID'] = 'aaa-aaa-aaa';
        $request['buyer']['deviceFingerprint'] = 'deviceFingerprint';
        $request['buyer']['isBot'] = 'true';
        $request['buyer']['isIncognito'] = 'true';
        $request['buyer']['isBehindProxy'] = 'true';
        $request['buyer']['isFromTor'] = 'true';
        $request['buyer']['isEmulator'] = 'true';
        $request['buyer']['isRooted'] = 'true';
        $request['buyer']['hasTimezoneMismatch'] = 'jesaispas';
        $request['buyer']['loyaltyMemberType'] = 'true';
        $request['buyer']['buyerExtended'] = 'buyerExt';
        $request['buyer']['merchantAuthentication'] = 'merchantAuthen1111';
        $request['buyer']['shippingAdress'] = $this->addAddressData(array(), 'shipping');
        $request['buyer']['billingAddress'] = $this->addAddressData(array(), 'billing');
        return $request;
    }

    /**
     * Add Wallet info to requestElement
     * @param array $request
     * @return array
     */
    public function addWalletData(array $request): array
    {
        $request['wallet']['walletId'] = '2';
        $request['wallet']['lastName'] = 'lastname';
        $request['wallet']['firstName'] = 'firstname ';
        $request['wallet']['email'] = 'toto@fait.dubateau ';
        $request['card'] = array();
        $request = $this->addCardData($request);
        $request['address'] = $this->addAddressData(array(), 'shipping');
        return $request;
    }

    /**
     * Add Buyer authorization to requestElement
     * @param array $request
     * @return array
     */
    public function addAuthorizationData(array $request): array
    {
        $request['authorization']['number'] = 'lastname';
        $request['authorization']['date'] = '15/12/2014 12:20';
        $request['authorization']['authorizedAmount'] = '100';
        $request['authorization']['authorizedCurrency'] = '977';
        $request['authorization']['reattempt'] = 'reattempt';
        return $request;
    }

    /**
     * Add Buyer authentication3DSecure to requestElement
     * @param array $request
     * @return array
     */
    public function addAuthentication3DSecureData(array $request): array
    {
        $request['authentication3DSecure']['md'] = 'Md2123454657897';
        $request['authentication3DSecure']['pares'] = 'pares';
        $request['authentication3DSecure']['xid'] = 'xidzzzzz';
        $request['authentication3DSecure']['eci'] = '05';
        $request['authentication3DSecure']['cavv'] = 'aaaaaaaaaaaaaaa';
        $request['authentication3DSecure']['cavvAlgorithm'] = 'cavvAlgorithm2222';
        $request['authentication3DSecure']['vadsResult'] = 'vadsResult3333';
        $request['authentication3DSecure']['typeSecurisation'] = 'very secure';
        $request['authentication3DSecure']['PaResStatus'] = 'ParesStatus9';
        $request['authentication3DSecure']['VeResStatus'] = 'VeResStatus8';
        $request['authentication3DSecure']['resultContainer'] = 'resultContainer xxxxxxxxxxxx';
        $request['authentication3DSecure']['authenticationResult'] = 'Failed';
        return $request;
    }

    /**
     * Add Buyer subMerchant to requestElement
     * @param array $request
     * @return array
     */
    public function addSubMerchantData(array $request): array
    {
        $request['subMerchant']['subMerchantName'] = 'subMerchantName 1';
        $request['subMerchant']['subMerchantSIRET'] = 'subMerchantSIRET 2';
        $request['subMerchant']['subMerchantTaxCode'] = 'subMerchantTaxCode 3';
        $request['subMerchant']['subMerchantStreet'] = 'subMerchantStreet 4 nowhere';
        $request['subMerchant']['subMerchantCity'] = 'subMerchantCity 5';
        $request['subMerchant']['subMerchantZipCode'] = 'subMerchantZipCode 6';
        $request['subMerchant']['subMerchantCountry'] = 'subMerchantCountry 7 FR';
        $request['subMerchant']['subMerchantState'] = 'subMerchantState 8';
        $request['subMerchant']['subMerchantEmailAddress'] = 'subMerchantEmailAddress 9';
        $request['subMerchant']['subMerchantPhoneNumber'] = 'subMerchantPhoneNumber 10';
        return $request;
    }

    /**
     * Add Buyer info to requestElement
     * @param array $request
     * @return array
     */
    public function addOwnerData(array $request): array
    {
        $request['owner']['lastName'] = 'lastname owner';
        $request['owner']['firstName'] = 'firstname owner';
        $request['owner']['issueCardDate'] = '15/12/2020 10:00';
        $request['owner']['billingAddress'] = $this->addAddressData(array(), 'billing');
        return $request;
    }

    /**
     * Add Card info to request
     * @param array $request
     * @return array
     */
    public function addCardData(array $request): array
    {
        $request['card']['number'] = '4444333322221111';
        $request['card']['type'] = 'CB';
        $request['card']['expirationDate'] = '1235';
        $request['card']['cvx'] = '123';
        $request['card']['cardholder'] = 'Marcel Patoulatchi';
        return $request;
    }

    /**
     * Add recurring info to request
     * @param array $request
     * @return array
     */
    public function addRecurringData(array $request): array
    {
        $request['recurring']['firstAmount'] = '30';
        $request['recurring']['amount'] = '100';
        $request['recurring']['billingCycle'] = '12';
        $request['recurring']['billingLeft'] = '20';
        $request['recurring']['billingDay'] = '5';
        $request['recurring']['startDate'] = '01/01/2020 10:00';
        $request['recurring']['endDate'] = '01/01/2122 10:00';
        $request['recurring']['newAmount'] = '40000';
        $request['recurring']['amountModificationDate'] = '02/01/2020 10:00';;
        $request['recurring']['billingRank'] = '12';
        return $request;
    }

    /**
     * Add threeDSInfo info to request
     * @param array $request
     * @return array
     */
    public function addthreeDSInfoData(array $request): array
    {
        $request['threeDSInfo']['challengeInd'] = '1';
        $request['threeDSInfo']['threeDSReqPriorAuthData'] = 'ThreeDSReqPriorAuthData';
        $request['threeDSInfo']['threeDSReqPriorAuthMethod'] = 'threeDSReqPriorAuthMethod';
        $request['threeDSInfo']['threeDSReqPriorAuthTimestamp'] = 'threeDSReqPriorAuthTimestamp';
        $request['threeDSInfo']['threeDSMethodNotificationURL'] = 'threeDSMethodNotificationURL';
        $request['threeDSInfo']['threeDSMethodResult'] = 'threeDSMethodResult';
        $request['threeDSInfo']['challengeWindowSize'] = '500px';
        $request['threeDSInfo']['browser']['acceptHeader'] = 'header';
        $request['threeDSInfo']['browser']['javaEnabled'] = 'javaTrus';
        $request['threeDSInfo']['browser']['javascriptEnabled'] = 'jsTrue';
        $request['threeDSInfo']['browser']['language'] = 'Fr';
        $request['threeDSInfo']['browser']['colorDepth'] = 'colorDepth';
        $request['threeDSInfo']['browser']['screenHeight'] = '500';
        $request['threeDSInfo']['browser']['screenWidth'] = '400';
        $request['threeDSInfo']['browser']['timeZoneOffset'] = 'GMT+5';
        $request['threeDSInfo']['browser']['userAgent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';
        $request['threeDSInfo']['sdk']['deviceRenderingOptionsIF'] = 'deviceRenderingOptionsIF9';
        $request['threeDSInfo']['sdk']['deviceRenderOptionsUI'] = 'deviceRenderOptionsUI8';
        $request['threeDSInfo']['sdk']['appID'] = 'appID7';
        $request['threeDSInfo']['sdk']['ephemPubKey'] = 'ephemPubKey6';
        $request['threeDSInfo']['sdk']['maxTimeout'] = '10';
        $request['threeDSInfo']['sdk']['referenceNumber'] = 'referenceNumber5';
        $request['threeDSInfo']['sdk']['transID'] = 'transID4';
        $request['threeDSInfo']['sdk']['encData'] = 'encData3';
        return $request;
    }

}
