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
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            self::environment, $pathLog= null, self::logLvl);

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

//    public static function setUpBeforeClass(): void
//    {
////        static::setUpHttpMockBeforeClass('8082', 'localhost');
//    }
//
//    public static function tearDownAfterClass(): void
//    {
////        static::tearDownHttpMockAfterClass();
//    }
//
//    public function setUp(): void
//    {
////        $this->setUpHttpMock();
//    }
//
//    public function tearDown(): void
//    {
////        $this->tearDownHttpMock();
//    }

    /**
     * Test de l'appel du  {@link PaylineSDK::doAuthorization()}
     * @throws ReflectionException
     * @throws Exception
     */
    public function testCallDoAuthorization()
    {
        // Chargement du fichier xml Reponse
        $xmlResponseContent = $this->loadXmlResponseFromFile('directpayment/doAuthor/doAuthorizationResponse00000.xml');
        // Chargement du fichier xml expected request
        $xmlExpectedRequest = $this->loadXmlResponseFromFile('directpayment/doAuthor/doAuthorizationRequest.xml');

        // Given
        // Create instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            self::environment, $pathLog= null, self::logLvl);
        // Create mock for Wiremock (standalone running in Docker => https://wiremock.org/docs/standalone/docker/)
        $this->createWiremock($paylineSDK, $xmlResponseContent, self::DIRECT_PAYMENT_API);


        // Create Call
//        // Mock SOAP API
//        $soapClientOptions = (array) $this->getProtectedProperty($paylineSDK, 'soapclientOptions');

        // Test - Call doAuthorization
        $doAuthorizationRequest = array();

        $doAuthorizationRequest['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php';
        $doAuthorizationRequest['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
        $doAuthorizationRequest['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';
        // PAYMENT
        $doAuthorizationRequest['payment']['amount'] = 1000; // this value has to be an integer amount is sent in cents
        $doAuthorizationRequest['payment']['currency'] = 978; // ISO 4217 code for euro
        $doAuthorizationRequest['payment']['action'] = 100; // 101 stand for "authorization+capture"
        $doAuthorizationRequest['payment']['mode'] = 'CPT'; // one shot payment
        $doAuthorizationRequest['payment']['contractNumber'] = 'CB';
        // ORDER
        $doAuthorizationRequest['order']['ref'] = 'myOrderRef_35656'; // the reference of your order
        $doAuthorizationRequest['order']['amount'] = 1000; // may differ from payment.amount if currency is different
        $doAuthorizationRequest['order']['currency'] = 978; // ISO 4217 code for euro
        // CARD
        $doAuthorizationRequest['card']['number'] = '4444333322221111';
        $doAuthorizationRequest['card']['type'] = 'CB';
        $doAuthorizationRequest['card']['expirationDate'] = '1235';
        $doAuthorizationRequest['card']['cvx'] = '123';
        $doAuthorizationRequest['card']['cardholder'] = 'Marcel Patoulatchi';


        $doAuthorizationResponse = $paylineSDK->doAuthorization($doAuthorizationRequest);

        // Then
        $this->assertNotNull($doAuthorizationResponse);
        // Should get ResultCode OK
        $this->checkResponseResultOK($doAuthorizationResponse['result']);

        $this->checkResponseTransaction($doAuthorizationResponse['transaction'], '14340105742592', '05/12/23 20:27:42',
            '0', '0', '', '', 'N', null, '4', '');


        // TODO : Voir autres check des objets

        // TODO : Check de la request
        // Verify a request
        $this->verifyCallRequest(self::DIRECT_PAYMENT_API, $xmlExpectedRequest, 'doAuthorization');
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

        } else {
            self::$wireMock->reset();
        }

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
     * @param $trsData
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
                                             $trsData,
                                             $idDuplicated='0',
                                             $isPossibleFraud='0',
                                             $fraudResult='',
                                             $explanation=null,
                                             $threeDs=null,
                                             $score=null,
                                             $avsResult=null,
                                             $avsResultAcq=null): void
    {
        $this->assertEquals($trsId, $transaction['id']);
        $this->assertEquals($trsData, $transaction['date']);
        $this->assertEquals($idDuplicated, $transaction['isDuplicated']);
        $this->assertEquals($isPossibleFraud, $transaction['isPossibleFraud']);

        if (isset($fraudResult)) {
            $this->assertEquals('', $transaction['fraudResult']);
        }
        if (isset($explanation)) {
            $this->assertEquals('', $transaction['explanation']);
        }
        if (isset($threeDs)) {
            $this->assertEquals('N', $transaction['threeDSecure']);
        }
        if (isset($score)) {
            $this->assertEquals(null, $transaction['score']);
        }
        if (isset($avsResult)) {
            $this->assertEquals($avsResult, $transaction['avs']['result']);
        }
        if (isset($avsResultAcq)) {
            $this->assertEquals($avsResultAcq, $transaction['avs']['resultFromAcquirer']);
        }
    }

}
