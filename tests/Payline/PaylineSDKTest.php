<?php

namespace test\Payline;
use Exception;
use Monolog\Logger;
use Payline\PaylineSDK;
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
    const EXTENDED_API = 'ExtendedAPI';
    const WEB_API = 'WebPaymentAPI';
    const PHP_PROJECT_CURRENT_VERSION = '- PHP SDK 4.76';

    /**
     * @var WireMock
     */
    private static $wireMock;


    /**
     * Test du constructeur  avec valeurs minimales et ENV_HOMO
     * @throws ReflectionException
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
        $this->assertEmpty($soapClientOptions['stream_context_to_create']);
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'orderDetails'));
        $this->assertNotNull($this->getProtectedProperty($paylineSDK, 'privateData'));
    }

    /**
     * Test du constructeur sur environnement ENV_HOMO_CC
     * @throws ReflectionException
     */
    public function testNewPaylineSDKInstance_withENV_HOMO_CC()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_HOMO_CC, null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://homologation-cc.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertEmpty($soapClientOptions['stream_context_to_create']);
    }

    /**
     * Test du constructeur sur environnement ENV_PROD
     * @throws ReflectionException
     */
    public function testNewPaylineSDKInstance_withENV_PROD()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_PROD, null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://services.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertEquals('https://payment.payline.com/services/servicesendpoints/SOAP/1111111', $this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertEmpty($soapClientOptions['stream_context_to_create']);
    }

    /**
     * Test du constructeur sur environnement ENV_PROD_CC
     * @throws ReflectionException
     */
    public function testNewPaylineSDKInstance_withENV_PROD_CC()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_PROD_CC, null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://services-cc.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertEmpty($soapClientOptions['stream_context_to_create']);
    }

    /**
     * Test du constructeur sur environnement ENV_DEV
     * @throws ReflectionException
     */
    public function testNewPaylineSDKInstance_withENV_DEV()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_DEV, null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://ws.dev.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertNotEmpty($soapClientOptions['stream_context_to_create']);
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer']);
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer_name']);
    }

    /**
     * Test du constructeur sur environnement ENV_INT
     * @throws ReflectionException
     */
    public function testNewPaylineSDKInstance_withENV_INT()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            PaylineSDK::ENV_INT, null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
        $this->assertEquals('https://ws.int.payline.com/V4/services/', $this->getProtectedProperty($paylineSDK, 'webServicesEndpoint'));
        $this->assertNull($this->getProtectedProperty($paylineSDK, 'servicesEndpoint'));
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertIsArray($soapClientOptions['stream_context_to_create']);
        $this->assertNotEmpty($soapClientOptions['stream_context_to_create']);
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer']);
        $this->assertFalse($soapClientOptions['stream_context_to_create']['ssl']['verify_peer_name']);
    }

    /**
     * Test du constructeur avec paramétrage proxy
     * @throws ReflectionException
     */
    public function testConstructWithAllParams()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);

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
     * @throws Exception
     */
    public function testSetSoapOptions()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertNull($soapClientOptions['KeyAAA']);


        // Test
        $paylineSDK = $paylineSDK->setSoapOptions('KeyAAA', 'ValueBBB');

        // Test
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertEquals('ValueBBB', $soapClientOptions['KeyAAA']);
    }

    /**
     * Test de la fonction {@link  PaylineSDK#setSoapOptions}
     * @throws Exception
     */
    public function test_SetSoapOptions_shouldReturnException()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot set Soap option');

        // Test
        $paylineSDK->setSoapOptions(array());
    }

    /**
     * Test de la fonction {@link  PaylineSDK#setFailoverOptions}
     * @throws Exception
     */
    public function test_setFailoverOptions_shouldReturnException()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot set Failover option');

        // Test
        $paylineSDK->setFailoverOptions(array());
    }

    /**
     * Test de la fonction {@link  PaylineSDK#getSoapOptions}
     * @throws Exception
     */
    public function test_GetSoapOptions()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);
        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');
        $this->assertNull($soapClientOptions['KeyAAA']);

        // ----
        // Test - with Key = NULL
        $soapOptions = $paylineSDK->getSoapOptions();
        // Verif
        $this->assertNotNull($soapOptions);
        $this->assertIsArray($soapOptions);

        // ----
        // Test - with Key but without value
        $soapOptions = $paylineSDK->getSoapOptions('KeyAAA');
        // Verif
        $this->assertNull($soapOptions);

        // ----
        // Test - with Key AND value
        $paylineSDK = $paylineSDK->setSoapOptions('KeyAAA', 'ValueBBB');
        $soapOptions = $paylineSDK->getSoapOptions('KeyAAA');
        // Verif
        $this->assertEquals('ValueBBB', $soapOptions);
    }

    /**
     * Test de la fonction {@link  PaylineSDK#getDefaultWSRequest}
     * @throws Exception
     */
    public function testGetDefaultWSRequest()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);

        // Test - Withunknown method
        $defaultWSRequest = $paylineSDK->getDefaultWSRequest('KeyAAA');
        $this->assertNotNull($defaultWSRequest);
        $this->assertEmpty($defaultWSRequest);

        // Test
        $defaultWSRequest = $paylineSDK->getDefaultWSRequest('manageWebWallet');
        $this->assertNotNull($defaultWSRequest);
        $this->assertNotEmpty($defaultWSRequest);
    }
    /**
     * Test de la fonction {@link  PaylineSDK#getLogger}
     * @throws Exception
     */
    public function testGetLogger()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);

        // Test
        $logger = $paylineSDK->getLogger();
        $this->assertNotNull($logger);
    }

    /**
     * Test de la fonction {@link  PaylineSDK#setFailoverOptions}
     * @throws Exception
     */
    public function testSetFailoverOptionsAndReset()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);
        $failoverOptions = $paylineSDK->getFailoverOptions();
        $this->assertNull($failoverOptions['KeyAAA']);


        // Test
        $paylineSDK->setFailoverOptions('KeyAAA', 'ValueBBB');

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
     * @throws ReflectionException
     */
    public function testReset()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, 'http://localhost:9999', '55', 'proxy login', 'proxy password',
            self::environment, null, self::logLvl);
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
        $this->assertEmpty($orderDetails);
        $this->assertEmpty($lastSoapCallData);
        $this->assertEmpty($paylineSDK->privateDataList());
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
        $doAuthorizationRequest['paymentData']['transactionID'] = 'xxxtrsId';
        $doAuthorizationRequest['paymentData']['network'] = 'VISA';
        $doAuthorizationRequest['paymentData']['tokenData'] = 'txby12345ooo';

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
        $this->assertCount(3, $billingRecordList['billingRecord']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doRecurrentWalletPayment');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::getPaymentRecord()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testGetPaymentRecord()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/getPaymentRecord/getPaymentRecordResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/getPaymentRecord/getPaymentRecordRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request['version'] = '90';
        $request['contractNumber'] = 'CB_2';
        $request['paymentRecordId'] = 'paymentRecordId 5';

        // Test
        $response = $paylineSDK->getPaymentRecord($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);
        $this->checkResponseRecurring($response['recurring'], '4000', '4000', '40', '2', '7', '07/12/2023', '07/01/2024');
        $this->assertEquals('2TNNN5334ukgfghgGHFF1967784072', $response['walletId']);
        $this->assertEquals('0', $response['isDisabled']);
        $billingRecordList = $response['billingRecordList'];
        $this->assertNotEmpty($billingRecordList['billingRecord']);
        $this->assertIsArray($billingRecordList['billingRecord']);
        $this->assertCount(2, $billingRecordList['billingRecord']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'getPaymentRecord');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::disablePaymentRecord()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDisablePaymentRecord()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/disablePaymentRecord/disablePaymentRecordResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/disablePaymentRecord/disablePaymentRecordRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request['version'] = '90';
        $request['contractNumber'] = 'CB_2';
        $request['paymentRecordId'] = 'paymentRecordId 5';

        // Test
        $response = $paylineSDK->disablePaymentRecord($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'disablePaymentRecord');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::verifyEnrollment()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testVerifyEnrollment()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/verifyEnrollment/verifyEnrollmentResponse03102.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/verifyEnrollment/verifyEnrollmentRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addCardData($request);
        $request = $this->addPaymentData($request);
        $request = $this->addOrderData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addSubMerchantData($request);
        $request = $this->addRecurringData($request);
        $request = $this->addthreeDSInfoData($request);
        $request['version'] = '90';
        $request['transient'] = 'transientParam';
        $request['merchantScore'] = 'merchantScore';
        $request['orderRef'] = 'orderRef XXX';
        $request['mdFieldValue'] = 'mdFieldValue XXX';
        $request['userAgent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';
        $request['generateVirtualCvx'] = 'generateVirtualCvx XXX';
        $request['merchantName'] = 'merchantName XXX';
        $request['merchantURL'] = 'merchantURL XXX';
        $request['merchantCountryCode'] = 'merchantCountryCode XXX';
        $request['returnURL'] = 'returnURL XXX';

        // Test
        $response = $paylineSDK->verifyEnrollment($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult($response['result'], '03102', 'ACCEPTED', 'Transaction accepted - Cardholder authenticated');

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'verifyEnrollment');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::verifyAuthentication()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testVerifyAuthentication()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/verifyAuthentication/verifyAuthenticationResponse03000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/verifyAuthentication/verifyAuthenticationRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);

        // Test
        $request = array();
        $request = $this->addCardData($request);
        $request['version'] = '90';
        $request['transient'] = 'transientParam';
        $request['merchantScore'] = 'merchantScore';
        $request['orderRef'] = 'orderRef XXX';
        $request['mdFieldValue'] = 'mdFieldValue XXX';
        $request['userAgent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36';
        $request['generateVirtualCvx'] = 'generateVirtualCvx XXX';
        $request['merchantName'] = 'merchantName XXX';
        $request['merchantURL'] = 'merchantURL XXX';
        $request['merchantCountryCode'] = 'merchantCountryCode XXX';
        $request['returnURL'] = 'returnURL XXX';

        // Test
        $response = $paylineSDK->verifyAuthentication($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult($response['result'], '03000', 'ACCEPTED', 'Transaction accepted');
        $this->assertEquals('Y', $response['mpiResult']);
        $this->assertEquals('19sdfsdfsdfsdfsdfuwzzzzzzzz', $response['authentication3DSecure']['md']);
        $this->assertEquals('resultContainer sdjfsdfhshfsqkgfsqgdf54h3d54h3gf4h35fg4h35fg4h35f4', $response['authentication3DSecure']['resultContainer']);

        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'verifyAuthentication');
    }
    /*
     * *************************************************************************
     * WebPaymentAPI
     * *************************************************************************
     */
    /**
     * Test de l'appel du {@link PaylineSDK::doWebPayment()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testDoWebPayment()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('webpayment/doWebPayment/doWebPaymentResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('webpayment/doWebPayment/doWebPaymentRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::WEB_API);

        // Test
        $request = array();
        $request = $this->addPaymentData($request);
        $request = $this->addOrderData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addOwnerData($request);
        $request = $this->addRecurringData($request);
        $request = $this->addSubMerchantData($request);
        $request = $this->addthreeDSInfoData($request);
        $request['version'] = '90';
        $request['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php';
        $request['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
        $request['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';
        $request['languageCode'] = 'FR_fr';
        $request['customPaymentPageCode'] = 'PloufXX';
        $request['securityMode'] = 'XX';
        $request['customPaymentTemplateURL'] = 'PloufXXUrl';
        $request['contractNumberWalletList']['contractNumberWallet'] = 'CB';
        $request['merchantName'] = 'Balthazar Picsou';
        $request['miscData'] = 'miscData value';
        $request['asynchronousRetryTimeout'] = 'async 100';
        $request['merchantScore'] = 'merchantScore 0';
        $request['skipSmartDisplay'] = 'true';

        // Test
        $response = $paylineSDK->doWebPayment($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->assertEquals('16RjWdtpDkFVfZkGV4031701964772640', $response['token']);
        $this->assertEquals('https://develop-staging-webpayment2.int.dev.payline.com/1mRmYi5lShI=/#16RjWdtpDkFVfZkGV4031701964772640', $response['redirectURL']);

        // Verify a request
        $this->verifyCallRequest(self::WEB_API, $xmlExpectedRequest, 'doWebPayment');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::getWebPaymentDetails()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testGetWebPaymentDetails()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('webpayment/getWebPaymentDetails/getWebPaymentDetailsResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('webpayment/getWebPaymentDetails/getWebPaymentDetailsRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::WEB_API);

        // Test
        $request = array();
        $request['version'] = '90';
        $request['token'] = 'xxxxxtokenxxxxxx';

        // Test
        $response = $paylineSDK->getWebPaymentDetails($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->checkResponseTransaction($response['transaction'], 'PV4280223147576302', 'C', '0', '0', null, null, 'N','0', null, null, null);
        $this->checkResponsePayment($response['payment'], '90000', '978', '101', 'CPT', 'CB_MULTI_4', null, 'CB', null);
        $this->checkResponseAuthorization($response['authorization'], null, '01/03/2023 00:12:51');
        $this->checkResponseOrder($response['order'], 'cb_a_plusieurs_20230228153137', null, null, null, '190000', '978', '26/05/2008 17:30:00', '1', '4', '31/12/2023', '66');
        $this->checkResponseCard($response['card'], '4XXXXXXXXXXXXXXX83', 'CB', '1223', null, '49fghfghfghfgh83');
        $this->checkResponseExtendedCard($response['extendedCard'], 'FRA', '20041 - LA POSTE', 'CB', 'CB', 'Carte nationale de paiement');
        $this->checkResponseAuthentication3DSecure($response['authentication3DSecure'], null, null);

        $this->assertNotNull($response['buyer']);
        $this->assertNotNull($response['buyer']['shippingAdress']);
        $this->assertNotNull($response['buyer']['billingAddress']);

        $this->assertEquals('CB_MULTI_4', $response['contractNumber']);
        $this->assertEquals('Computer', $response['media']);
        $this->assertNull($response['linkedTransactionId']);


        // Verify a request
        $this->verifyCallRequest(self::WEB_API, $xmlExpectedRequest, 'getWebPaymentDetails');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::manageWebWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testManageWebWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('webpayment/manageWebWallet/manageWebWalletResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('webpayment/manageWebWallet/manageWebWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::WEB_API);

        // Test
        $request = array();
        $selectedContractList = array();
        $selectedContractList['contracts'][0] = 'CB wallet';
        $selectedContractList['contracts'][1] = 'APPLE_PAY wallet';
        $privateDataList = $this->addPrivateData(array());
        $request['version'] = '90';
        $request = $this->addBuyerData($request);
        $request = $this->addBuyerData($request);
        $request = $this->addthreeDSInfoData($request);
        $request['contractNumber'] = 'contractNumber';
        $request['selectedContractList'] = $selectedContractList;
        $request['updatePersonalDetails'] = 'updatePersonalDetails';
        $request['privateDataList'] = $privateDataList;
        $request['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php';
        $request['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
        $request['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';
        $request['languageCode'] = 'FR_fr';
        $request['customPaymentPageCode'] = 'PloufXX';
        $request['securityMode'] = 'XX';
        $request['customPaymentTemplateURL'] = 'PloufXXUrl';
        $request['contractNumberWalletList']['walletContracts'] = 'CB';
        $request['merchantName'] = 'Balthazar Picsou';

        // Test
        $response = $paylineSDK->manageWebWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->assertEquals('1tYZOjJvky65IbxH0C6D1702009846955', $response['token']);
        $this->assertEquals('https://develop-staging-webpayment2.int.dev.payline.com/1mRmYi5lShI=/#1tYZOjJvky65IbxH0C6D1702009846955', $response['redirectURL']);



        // Verify a request
        $this->verifyCallRequest(self::WEB_API, $xmlExpectedRequest, 'manageWebWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::createWebWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testCreateWebWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('webpayment/createWebWallet/createWebWalletResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('webpayment/createWebWallet/createWebWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::WEB_API);

        // Test
        $request = array();
        $selectedContractList = array();
        $selectedContractList['contracts'][0] = 'CB wallet';
        $selectedContractList['contracts'][1] = 'APPLE_PAY wallet';
        $privateDataList = $this->addPrivateData(array());
        $request['version'] = '90';
        $request = $this->addBuyerData($request);
        $request = $this->addOwnerData($request);
        $request['contractNumber'] = 'contractNumber';
        $request['selectedContractList'] = $selectedContractList;
        $request['updatePersonalDetails'] = 'updatePersonalDetails';
        $request['privateDataList'] = $privateDataList;
        $request['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php';
        $request['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
        $request['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';
        $request['languageCode'] = 'FR_fr';
        $request['customPaymentPageCode'] = 'PloufXX';
        $request['securityMode'] = 'XX';
        $request['customPaymentTemplateURL'] = 'PloufXXUrl';
        $request['contractNumberWalletList']['walletContracts'] = 'CB';
        $request['merchantName'] = 'Balthazar Picsou';

        // Test
        $response = $paylineSDK->createWebWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->assertEquals('241JjGeRUHeYI54fgh132fgh1564fgJ4GWCIr1701970106166', $response['token']);
        $this->assertEquals('https://develop-staging-webpayment2.int.dev.payline.com/1mRmYi5lShI=/#241JjGeRUHeYI54fgh132fgh1564fgJ4GWCIr1701970106166', $response['redirectURL']);

        // Verify a request
        $this->verifyCallRequest(self::WEB_API, $xmlExpectedRequest, 'createWebWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::updateWebWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testUpdateWebWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('webpayment/updateWebWallet/updateWebWalletResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('webpayment/updateWebWallet/updateWebWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::WEB_API);

        // Test
        $request = array();
        $selectedContractList = array();
        $selectedContractList['contracts'][0] = 'CB wallet';
        $selectedContractList['contracts'][1] = 'APPLE_PAY wallet';
        $privateDataList = $this->addPrivateData(array());
        $request['version'] = '90';
        $request = $this->addBuyerData($request);
        $request['contractNumber'] = 'contractNumber';
        $request['selectedContractList'] = $selectedContractList;
        $request['privateDataList'] = $privateDataList;
        $request['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php';
        $request['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
        $request['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';
        $request['languageCode'] = 'FR_fr';
        $request['customPaymentPageCode'] = 'PloufXX';
        $request['securityMode'] = 'XX';
        $request['customPaymentTemplateURL'] = 'PloufXXUrl';
        $request['contractNumberWalletList']['walletContracts'] = 'CB';
        $request['merchantName'] = 'Balthazar Picsou';
        $request['cardInd'] = '1';
        $request['walletId'] = '5';
        $request['updatePersonalDetails'] = 'updatePersonalDetails 789';
        $request['updateOwnerDetails'] = 'updateOwnerDetails 789';
        $request['updatePaymentDetails'] = 'updatePaymentDetails 789';

        // Test
        $response = $paylineSDK->updateWebWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->assertEquals('2Lg7xxNi7yCX2654dfg23jjL1lNH1701fghfgh32132970349229', $response['token']);
        $this->assertEquals('https://develop-staging-webpayment2.int.dev.payline.com/1mRmYi5lShI=/#2Lg7xxNi7yCX2654dfg23jjL1lNH1701fghfgh32132970349229', $response['redirectURL']);

        // Verify a request
        $this->verifyCallRequest(self::WEB_API, $xmlExpectedRequest, 'updateWebWallet');
    }

    /**
     * Test de l'appel du {@link PaylineSDK::getWebWallet()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testGetWebWallet()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('webpayment/getWebWallet/getWebWalletResponse02500.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('webpayment/getWebWallet/getWebWalletRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::WEB_API);

        // Test
        $request = array();
        $request['version'] = '90';
        $request['token'] = 'xxxx-zzzz-yyyy';

        // Test
        $response = $paylineSDK->getWebWallet($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResult02500($response['result']);
        $this->checkResponseWallet($response['wallet'], 'T55.S1_cW_20231208035804_2', 'OOOOOOOO', 'Delphine', 'testauto@monext.net', null, null, null, 'VISA');
        $this->checkResponseCard($response['wallet']['card'], '4XXXXXXXXXXXXX36', 'CB', '1230', null, '4970tHpGDTqb1236');
        $this->checkResponseAddress($response['wallet']['shippingAddress']);
        $this->checkResponseExtendedCard($response['extendedCard'], 'FRA', '20041 - LA POSTE', 'VISA', 'VISA', 'Visa Gold');

        // Verify a request
        $this->verifyCallRequest(self::WEB_API, $xmlExpectedRequest, 'getWebWallet');

        $soapLastContent = $paylineSDK->getSoapLastContent();
        $this->assertNotNull($soapLastContent);
        $this->assertNotNull($soapLastContent[1]['Request']);
        $this->assertNotNull($soapLastContent[1]['RequestHeaders']);
        $this->assertNotNull($soapLastContent[1]['HttpHeaders']);
        $this->assertNotNull($soapLastContent[1]['Response']);
        $this->assertNotNull($soapLastContent[1]['ResponseHeaders']);
        $soapLastContent = $paylineSDK->getSoapLastContent('HttpHeaders');
        $this->assertNotNull($soapLastContent[1]['HttpHeaders']);
    }

    /*
     * *************************************************************************
     * ExtendedPaymentAPI
     * *************************************************************************
     */
    /**
     * Test de l'appel du {@link PaylineSDK::getTransactionDetails()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testGetTransactionDetails()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('extended/getTransactionDetails/getTransactionDetailsResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('extended/getTransactionDetails/getTransactionDetailsRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::EXTENDED_API);

        // Test
        $request = array();
        $request = $this->addCardData($request);
        $request['version'] = '90';
        $request['transactionId'] = 'TrsIdyyyy';
        $request['orderRef'] = 'OrderRefbbbb';
        $request['startDate'] = 'startDate XXX';
        $request['endDate'] = 'endDate XXX';
        $request['transactionHistory'] = 'Y';
        $request['archiveSearch'] = 'archiveSearch';

        // Test
        $response = $paylineSDK->getTransactionDetails($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->checkResponseTransaction($response['transaction'], '24342fgdgdf019422375225', '07/12/2023 16:20:17', '0', '0', null, null, 'N',null, '4', null, 'AUTHOR+CAP');
        $this->checkResponsePayment($response['payment'], '190', '978', '129', 'CPT', '299124', null, 'CB', 'CB');
        $this->checkResponseAuthorization($response['authorization'], '662662', '07/12/23 16:20:17');
        $this->checkResponseOrder($response['order'], '5e0ddbf19bssssssssssssssss0538aabfef', null, null, null, '190', '978', '07/12/2023 16:20:00');
        $this->checkResponseCard($response['card'], '5XXXXXXXXXXXXXX0', 'CB', '1111', null, '5xxxxxxxbxxx0', 'CARD PAN');
        $this->checkResponseExtendedCard($response['extendedCard'], 'FRA', '10278 - CAISSE FEDERALE DE C', 'CB', 'CB', 'MCC - MASTERCARD CREDIT (MIXED BIN) CARD');
        $this->checkResponseAuthentication3DSecure($response['authentication3DSecure'], '2cdc2425-ggggggggggggggg-a854d1baf3a2', 'eyJjb250YWluZXJWZXJzhjghj45654654jkjhVBdXRoVHlwZSI6IkNIIiwiYWNzT3BlcmF0b3JJRCI6IkFDUy0wMDA5UC1DTS1DSUMtRUkiLCJ0aHJlZURTUmVx88jmFuc1N0YXR1cyI6IlkiLCJ0cmFuc1kR1c1JlYyvbiI6IjAwIiwiY2hhbGxlbmdlQ2FuY2VsSW5kIjoiMDAiLCJuZXR3b3JrU2NvcmUiOiI0IiwiZHNUcmFuc0lEIjoiMDY1NjJiNTUtMDljZC00NjFiLWFmYTUtODliMGJmNjAxNzY1IiwiYWNzVHJhbnNJRCI6IjIyMDIyMDUxLWQwYjYtNDNkMC1hYmQ5LTM0MzY2M2Q1OGM3NSIsIm1lc3NhZ2VWZXJzaW9uIjoiMi4xLjAiLCJtZXJjaGFudE5hbWUiOiJNaWpvdCIsInB1cmNoYXNlRGF0ZSI6IjIwMjIwNTE2MTAxODI3IiwicHVyY2hhc2VBbW91bnQiOiIwIiwiY2FyZEJyYW5kIjoiQ0IiLCJicm93c2VySVAiOiI5Mi4xODQuOTguNjAiLCJhY3F1aXJlckJJTiI6IjQ1MzMwMDEzMTA2IiwiYWNxdWlyZXJNZXJjaGFudElEIjoiMjg3MDEyNSAgICAgICAgLTAxICAgICAgIiwidGhyZWVEU1JlcXVlc3Rvck5hbWUiOiJNaWpvdCIsInRocmVlRFNSZXF1ZXN0b3JJRCI6Ijg5ODg4MDk4NDAwMDE1IiwibWVyY2hhbnRDb3VudHJ5Q29kZSI6IjI1MCJ9');
        $this->checkResponsePointOfSell($response['pointOfSell'], '13411111109', 'POS 89');

        $this->assertNotNull($response['buyer']);
        $this->assertNotNull($response['buyer']['shippingAdress']);
        $this->assertNotNull($response['buyer']['billingAddress']);

        $this->assertEquals('2870125', $response['contractNumber']);
        $this->assertEquals('1I2F65B757036906', $response['linkedTransactionId']);

        // Verify a request
        $this->verifyCallRequest(self::EXTENDED_API, $xmlExpectedRequest, 'getTransactionDetails');
    }


    /**
     * Test de l'appel du {@link PaylineSDK::transactionsSearch()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testTransactionsSearch()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('extended/transactionsSearch/transactionsSearchResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('extended/transactionsSearch/transactionsSearchRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::EXTENDED_API);

        // Test
        $request = array();
        $request = $this->addCardData($request);
        $request['version'] = '90';
        $request['transactionId'] = 'TrsIdyyyy';
        $request['merchantScore'] = 'merchantScore';
        $request['orderRef'] = 'OrderRefbbbb';
        $request['startDate'] = 'startDate XXX';
        $request['endDate'] = 'endDate XXX';
        $request['contractNumber'] = 'CB';
        $request['authorizationNumber'] = 'authorizationNumber 123';
        $request['returnCode'] = 'returnCode 888';
        $request['paymentMean'] = 'paymentMean XXX';
        $request['transactionType'] = 'transactionType XXX';
        $request['merchantCountryCode'] = 'merchantCountryCode XXX';
        $request['name'] = 'name 6';
        $request['firstName'] = 'name 7';
        $request['email'] = 'email 8';
        $request['cardNumber'] = '4970100000000';
        $request['currency'] = '978';
        $request['minAmount'] = '200';
        $request['maxAmount'] = '4000';
        $request['walletId'] = 'walIdp';
        $request['sequenceNumber'] = 'seqNum';
        $request['token'] = 'token1234567890';
        $request['pointOfSellId'] = 'pos44';
        $request['cardNetwork'] = 'CB';
        $request['threeDSecured'] = '3DS';
        $request['customerMediaId'] = 'media8';

        // Test
        $response = $paylineSDK->transactionsSearch($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);

        $this->assertEquals('PV40712888923932479707', $response['transactionList']['transaction'][0]['id']);
        $this->assertEquals('07/12/2023 09:41:59', $response['transactionList']['transaction'][0]['date']);
        $this->assertEquals('0', $response['transactionList']['transaction'][0]['isDuplicated']);
        $this->assertEquals('0', $response['transactionList']['transaction'][0]['isPossibleFraud']);

        $this->assertEquals('1493420654738976', $response['transactionList']['transaction'][1]['id']);
        $this->assertEquals('07/12/2023 07:54:38', $response['transactionList']['transaction'][1]['date']);
        $this->assertEquals('0', $response['transactionList']['transaction'][1]['isDuplicated']);
        $this->assertEquals('0', $response['transactionList']['transaction'][1]['isPossibleFraud']);

        // Verify a request
        $this->verifyCallRequest(self::EXTENDED_API, $xmlExpectedRequest, 'transactionsSearch');
    }


    /**
     * Test de l'appel du {@link PaylineSDK::getAlertDetails()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testGetAlertDetails()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('extended/getAlertDetails/getAlertDetailsResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('extended/getAlertDetails/getAlertDetailsRequest.xml');

        // Given
        // Create instance
        $paylineSDK = $this->createDefaultPaylineSDK();
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::EXTENDED_API);

        // Test
        $request = array();
        $request = $this->addCardData($request);
        $request['version'] = '90';
        $request['AlertId'] = 'AlertId 123';
        $request['TransactionId'] = 'TransactionId 456';
        $request['TransactionDate'] = '01/01/1920 23:59';

        // Test
        $response = $paylineSDK->getAlertDetails($request);

        // Then
        $this->assertNotNull($response);
        // Should get ResultCode OK
        $this->checkResponseResultOK($response['result']);
        $this->assertEquals('998587002', $response['AlertId']);
        $this->assertEquals('Forbidden_Card', $response['ExplanationCode']);
        $this->assertEquals('La carte saisie ne peut donner lieu à un rechargement', $response['ExplanationLabel']);
        $this->assertEquals('REFUSE', $response['TransactionStatus']);
        $this->assertEquals('FINANCIERE TRUC BIDULE', $response['MerchantLabel']);
        $this->assertEquals('NICKEL', $response['PosLabel']);
        $this->assertEquals('LfgdfF0gh0dsfgdfkklgdsfgjkd08', $response['TransactionId']);
        $this->assertEquals('CVV+3DS', $response['SecurityLevel']);
        $this->assertEquals('07/12/23 07:52', $response['TransactionDate']);
        $this->assertEquals('1000', $response['TransactionAmount']);
        $this->assertEquals('978', $response['TransactionCurrency']);
        $this->assertEquals('CB', $response['PaymentType']);
        $this->assertEquals('5XXXXXXXXXXXXXX9', $response['PaymentData']);
        $this->assertEquals('HolderName YYY', $response['HolderName']);
        $this->assertEquals('fee0cfc2-sdfsdfsdfsdfsdfsd-97591ee1f2', $response['ReferenceData']);
        $this->assertEquals('40000259651', $response['CustomerId']);
        $this->assertEquals('Raymond', $response['BuyerFirstName']);
        $this->assertEquals('Pichon', $response['BuyerLastName']);

        $this->assertEquals('0', $response['CustomerTransHist']['CustomerTrans']['IsLCLFAlerted']);
        $this->assertEquals('LF00241808565102', $response['CustomerTransHist']['CustomerTrans']['ExternalTransactionId']);
        $this->assertEquals('fee0cfc2-aaaaaaaaaaaaaaaaaaaaa-9e97591ee1f2', $response['CustomerTransHist']['CustomerTrans']['ReferenceOrder']);
        $this->assertEquals('CB 529097XXXXXXXX17', $response['CustomerTransHist']['CustomerTrans']['CardCode']);
        $this->assertEquals('07/12/23 07:52', $response['CustomerTransHist']['CustomerTrans']['TransactionDate']);
        $this->assertEquals('20.40 EUR', $response['CustomerTransHist']['CustomerTrans']['Amount']);
        $this->assertEquals('REFUSE', $response['CustomerTransHist']['CustomerTrans']['Status']);
        $this->assertEquals('NICKEL', $response['CustomerTransHist']['CustomerTrans']['PosLabel']);

        $this->assertEquals('0', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['IsLCLFAlerted']);
        $this->assertEquals('LF00241808565102', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['ExternalTransactionId']);
        $this->assertEquals('fee0cfc2-ssssssssssssssss-9e97591ee1f2', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['ReferenceOrder']);
        $this->assertEquals('Marcel Patulacci - 40000259651', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['CustomerData']);
        $this->assertEquals('07/12/23 07:52', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['TransactionDate']);
        $this->assertEquals('20.40 EUR', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['Amount']);
        $this->assertEquals('REFUSE', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['Status']);
        $this->assertEquals('NICKEL', $response['PaymentMeansTransHist']['PaymentMeansTrans'][0]['PosLabel']);

        // Verify a request
        $this->verifyCallRequest(self::EXTENDED_API, $xmlExpectedRequest, 'getAlertDetails');
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
            $this->assertTrue(self::$wireMock->isAlive(5));

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
     * @param $soapApiPath
     * @param $xmlExpectedRequest
     * @param $soapAction
     * @return void
     * @throws Exception
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
            ->withRequestBody(WireMock::equalToXml($xmlExpectedRequest))
        ;

        try {
            self::$wireMock->verify($requestPatternBuilder);
        } catch (Exception $e) {
            // Si on a une exception dans le verify, on fait appel a Wiremock pour avoir le stub le plus proche (https://wiremock.org/docs/verifying/#near-misses)
            // Afin d'afficher les infos pour débugguage
            $findNearMissesResult = self::$wireMock->findNearMissesFor($requestPatternBuilder);
            echo $e->getMessage();
            $this->fail('Wiremock verifiy in error. Near misses : ' . serialize($findNearMissesResult->getNearMisses()[0]));
        }
    }

    /**
     * @param $filePath
     * @return string
     */
    public function getFullFilePath($filePath): string
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
     * @param $resultCode
     * @param $resultShortMessage
     * @param $resultLongMessage
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
     * @param null $fraudResult
     * @param null $explanation
     * @param null $threeDs
     * @param null $score
     * @param null $avsResult
     * @param null $avsResultAcq
     * @param null $type
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
                                             $avsResultAcq=null,
                                             $type = null): void
    {
        $this->assertEquals($trsId, $transaction['id']);
        $this->assertEquals($trsDate, $transaction['date']);
        $this->assertEquals($idDuplicated, $transaction['isDuplicated']);
        $this->assertEquals($isPossibleFraud, $transaction['isPossibleFraud']);
        $this->assertEquals($type, $transaction['type']);

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

    public function checkResponseAuthentication3DSecure($authentication,
                                                        $md,
                                                        $resultContainer): void
    {
        $this->assertEquals($md, $authentication['md']);
        $this->assertEquals($resultContainer, $authentication['resultContainer']);
    }
    public function checkResponseAuthorization($authorization,
                                               $number,
                                               $date): void
    {
        $this->assertEquals($number, $authorization['number']);
        $this->assertEquals($date, $authorization['date']);
    }

    public function checkResponsePointOfSell($pos,
                                             $id,
                                             $label): void
    {
        $this->assertEquals($id, $pos['id']);
        $this->assertEquals($label, $pos['label']);
    }

    public function checkResponseOrder($order,
                                       $ref,
                                       $origin,
                                       $country,
                                       $taxes,
                                       $amount,
                                       $currency,
                                       $date,
                                       $deliveryTime = null,
                                       $deliveryMode = null,
                                       $deliveryExpectedDate = null,
                                       $deliveryExpectedDelay = null,
                                       $discountAmount = null,
                                       $otaPackageType = null,
                                       $otaDestinationCountry = null,
                                       $bookingReference = null,
                                       $orderExtended = null,
                                       $orderOTA = null): void
    {
        $this->assertEquals($ref, $order['ref']);
        $this->assertEquals($origin, $order['origin']);
        $this->assertEquals($country, $order['country']);
        $this->assertEquals($taxes, $order['taxes']);
        $this->assertEquals($amount, $order['amount']);
        $this->assertEquals($currency, $order['currency']);
        $this->assertEquals($date, $order['date']);
        $this->assertEquals($deliveryTime, $order['deliveryTime']);
        $this->assertEquals($deliveryMode, $order['deliveryMode']);
        $this->assertEquals($deliveryExpectedDate, $order['deliveryExpectedDate']);
        $this->assertEquals($deliveryExpectedDelay, $order['deliveryExpectedDelay']);
        $this->assertEquals($discountAmount, $order['discountAmount']);
        $this->assertEquals($otaPackageType, $order['otaPackageType']);
        $this->assertEquals($otaDestinationCountry, $order['otaDestinationCountry']);
        $this->assertEquals($bookingReference, $order['bookingReference']);
        $this->assertEquals($orderExtended, $order['orderExtended']);
        $this->assertEquals($orderOTA, $order['orderOTA']);
    }

    public function checkResponsePayment($payment,
                                         $amount,
                                         $currency,
                                         $action,
                                         $mode,
                                         $contractNumber,
                                         $differedActionDate,
                                         $method,
                                         $cardBrand): void
    {
        $this->assertEquals($amount, $payment['amount']);
        $this->assertEquals($currency, $payment['currency']);
        $this->assertEquals($action, $payment['action']);
        $this->assertEquals($mode, $payment['mode']);
        $this->assertEquals($contractNumber, $payment['contractNumber']);
        $this->assertEquals($differedActionDate, $payment['differedActionDate']);
        $this->assertEquals($method, $payment['method']);
        $this->assertEquals($cardBrand, $payment['cardBrand']);
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
    public function checkResponseRecurring($recurring,
                                           $firstAmount,
                                           $amount,
                                           $billingCycle,
                                           $billingLeft,
                                           $billingDay,
                                           $startDate,
                                           $endDate,
                                           $newAmount = null,
                                           $amountModificationDate = null): void
    {
        $this->assertEquals($firstAmount, $recurring['firstAmount']);
        $this->assertEquals($amount, $recurring['amount']);
        $this->assertEquals($billingCycle, $recurring['billingCycle']);
        $this->assertEquals($billingLeft, $recurring['billingLeft']);
        $this->assertEquals($billingDay, $recurring['billingDay']);
        $this->assertEquals($startDate, $recurring['startDate']);
        $this->assertEquals($endDate, $recurring['endDate']);
        $this->assertEquals($newAmount, $recurring['newAmount']);
        $this->assertEquals($amountModificationDate, $recurring['amountModificationDate']);
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
            self::environment, null, self::logLvl);
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
     * @param null $suffix
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
        $request['recurring']['amountModificationDate'] = '02/01/2020 10:00';
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

    /**
     * @param array $privateDataList
     * @return array
     */
    public function addPrivateData(array $privateDataList): array
    {
        $privateDataList['privateData'][0]['key'] = 'pvDataKey 0';
        $privateDataList['privateData'][0]['value'] = 'pvDataValue 0';
        $privateDataList['privateData'][1]['key'] = 'pvDataKey 1';
        $privateDataList['privateData'][1]['value'] = 'pvDataValue 1';
        return $privateDataList;
    }

}
