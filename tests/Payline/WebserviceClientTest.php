<?php


namespace test\Payline;

use Exception;
use Monolog\Logger;
use Payline\Cache\Apc;
use Payline\WebserviceClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use SoapClient;
use SoapFault;

class WebserviceClientTest extends TestCase
{

    /**
     * @throws ReflectionException
     * @throws SoapFault
     */
    public function test__construct()
    {

        $mockSoapClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__getLastRequest', '__getLastRequestHeaders', '__getLastResponseHeaders'])
            ->getMock();

        $params = array();
        $params['logger_path'] = 'logger_path';
        $soapOptions = array();
        $soapOptions['soap_client'] = $mockSoapClient;

        // Test
        $webserviceClient = new WebserviceClient(null, null, null, $soapOptions, $params);

        // Verif
        $failoverProperty = $this->getProtectedProperty($webserviceClient, 'useFailvover');
        $loggerProperty = $this->getProtectedProperty($webserviceClient, 'logger');
        $this->assertFalse($failoverProperty);
        $this->assertNotNull($loggerProperty);
    }

    /**
     * @throws ReflectionException
     * @throws SoapFault
     */
    public function test__log()
    {
        $webserviceClient = new WebserviceClient(null, null, null, array(), array());

        $mockLogger = $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['log'])
            ->getMock();

        $this->setProtectedProperty($webserviceClient, 'logger', $mockLogger);

        $mockLogger->expects($this->atLeastOnce())
            ->method('log');

        // Test
        $void = $webserviceClient->log(9, 'message', []);

        // Verif
        $this->assertNull($void );

    }

    /**
     * @throws ReflectionException
     * @throws SoapFault
     */
    public function test__switchSoapContextFailoverOnPaylineError()
    {
        $soapOptions = array();
        $soapOptions['proxy_host'] = 'localhost.nowhere.com';
        $soapOptions['proxy_port'] = '999';
        $soapOptions['proxy_login'] = 'login';
        $soapOptions['proxy_password'] = 'password';
        $constructorArgsArray = [null, null, 'endpointsDirectoryLocation', $soapOptions, array()];

        $mockWebserviceClient = $this->getMockBuilder(WebserviceClient::class)
            ->setConstructorArgs($constructorArgsArray)
            ->onlyMethods(['getFileContentWrapper', 'getCachePool'])
            ->getMock();
        $mockCacheInterface = $this->getMockBuilder(Apc::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasServicesEndpoints', 'saveServicesEndpoints'])
            ->getMock();

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getFileContentWrapper')
            ->willReturn('{"urls":["https://homologation-1.payline.com/V4", "https://homologation-2.payline.com/V4"]}');

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getCachePool')
            ->willReturn($mockCacheInterface);

        $mockCacheInterface->expects($this->atLeastOnce())
            ->method('hasServicesEndpoints')
            ->willReturn(false);

        $mockWebserviceClient->setUseFailover(true);
        $methodUnderTest = $this->getMethod($mockWebserviceClient, 'switchSoapContextFailoverOnPaylineError');
        $methodParam = 'doAuthorization';
        $nextTryNumParam = 2;
        $callStartParam = 1;

        $responseParam = new \stdClass();
        $responseParam->result = new \stdClass();
        $responseParam->result->code = '04901';

        // Test
        $result = $methodUnderTest->invokeArgs($mockWebserviceClient, array($methodParam, $nextTryNumParam, $callStartParam, $responseParam));

        // Verif
        $this->assertTrue($result);

        $headers = $this->getPropertyForMock($mockWebserviceClient, 'sdkFailoverCurrentHeaders');

        $this->assertNotEmpty($headers);
        $this->assertEquals("APP_04901", $headers['x-failover-cause']);
        $this->assertNotNull($headers['x-failover-duration']);
        $this->assertEquals(null, $headers['x-failover-origin']);
        $this->assertEquals("1", $headers['x-failover-index']);
    }

    /**
     * @throws ReflectionException
     * @throws SoapFault
     */
    public function test__switchSoapContextFailoverOnFault()
    {
        $soapOptions = array();
        $soapOptions['proxy_host'] = 'localhost.nowhere.com';
        $soapOptions['proxy_port'] = '999';
        $soapOptions['proxy_login'] = 'login';
        $soapOptions['proxy_password'] = 'password';
        $constructorArgsArray = [null, null, 'endpointsDirectoryLocation', $soapOptions, array()];

        $mockWebserviceClient = $this->getMockBuilder(WebserviceClient::class)
            ->setConstructorArgs($constructorArgsArray)
            ->onlyMethods(['getFileContentWrapper', 'getCachePool'])
            ->getMock();
        $mockCacheInterface = $this->getMockBuilder(Apc::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasServicesEndpoints', 'saveServicesEndpoints'])
            ->getMock();

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getFileContentWrapper')
            ->willReturn('{"urls":["https://homologation-1.payline.com/V4", "https://homologation-2.payline.com/V4"]}');

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getCachePool')
            ->willReturn($mockCacheInterface);

        $mockCacheInterface->expects($this->atLeastOnce())
            ->method('hasServicesEndpoints')
            ->willReturn(false);

        $mockWebserviceClient->setUseFailover(true);
        $methodUnderTest = $this->getMethod($mockWebserviceClient, 'switchSoapContextFailoverOnFault');
        $methodParam = 'doAuthorization';
        $nextTryNumParam = 2;
        $callStartParam = 1;

        // Test
        $result = $methodUnderTest->invokeArgs($mockWebserviceClient, array($methodParam, $nextTryNumParam, $callStartParam, '502'));

        // Verif
        $this->assertTrue($result);

        $headers = $this->getPropertyForMock($mockWebserviceClient, 'sdkFailoverCurrentHeaders');

        $this->assertNotEmpty($headers);
        $this->assertEquals("HTTP_502", $headers['x-failover-cause']);
        $this->assertNotNull($headers['x-failover-duration']);
        $this->assertEquals(null, $headers['x-failover-origin']);
        $this->assertEquals("1", $headers['x-failover-index']);
    }

    /**
     * @throws ReflectionException
     */
    public function test__switchSoapContextFailoverOnException()
    {
        $soapOptions = array();
        $soapOptions['proxy_host'] = 'localhost.nowhere.com';
        $soapOptions['proxy_port'] = '999';
        $soapOptions['proxy_login'] = 'login';
        $soapOptions['proxy_password'] = 'password';
        $constructorArgsArray = [null, null, 'endpointsDirectoryLocation', $soapOptions, array()];

        $mockWebserviceClient = $this->getMockBuilder(WebserviceClient::class)
            ->setConstructorArgs($constructorArgsArray)
            ->onlyMethods(['getFileContentWrapper', 'getCachePool'])
            ->getMock();
        $mockCacheInterface = $this->getMockBuilder(Apc::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasServicesEndpoints', 'saveServicesEndpoints'])
            ->getMock();

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getFileContentWrapper')
            ->willReturn('{"urls":["https://homologation-1.payline.com/V4", "https://homologation-2.payline.com/V4"]}');

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getCachePool')
            ->willReturn($mockCacheInterface);

        $mockCacheInterface->expects($this->atLeastOnce())
            ->method('hasServicesEndpoints')
            ->willReturn(false);

        $mockWebserviceClient->setUseFailover(true);
        $methodUnderTest = $this->getMethod($mockWebserviceClient, 'switchSoapContextFailoverOnException');
        $methodParam = 'doAuthorization';
        $nextTryNumParam = 2;
        $callStartParam = 1;
        $this->setProtectedPropertyForClass(WebserviceClient::class, $mockWebserviceClient, 'exeptionErrorList', ['0']);

        // Test
        $result = $methodUnderTest->invokeArgs($mockWebserviceClient, array($methodParam, $nextTryNumParam, $callStartParam, new SoapFault('0', 'error')));

        // Verif
        $this->assertTrue($result);

        $headers = $this->getPropertyForMock($mockWebserviceClient, 'sdkFailoverCurrentHeaders');

        $this->assertNotEmpty($headers);
        $this->assertEquals("EXCEPTION", $headers['x-failover-cause']);
        $this->assertNotNull($headers['x-failover-duration']);
        $this->assertEquals(null, $headers['x-failover-origin']);
        $this->assertEquals("1", $headers['x-failover-index']);
    }

    /**
     * @throws ReflectionException
     */
    public function test__getAllFailoverServicesEndpoint_withCURLCall()
    {
        $constructorArgsArray = [null, null, 'endpointsDirectoryLocation', array(), array()];

        $mockWebserviceClient = $this->getMockBuilder(WebserviceClient::class)
            ->setConstructorArgs($constructorArgsArray)
            ->onlyMethods(['getCachePool', 'getCallEndpointsDirectoryMethod', 'curlExecWrapper'])
            ->getMock();
        $this->getMethod($mockWebserviceClient, 'getCallEndpointsDirectoryMethod'); // Pour la mettre en publique

        $mockCacheInterface = $this->getMockBuilder(Apc::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasServicesEndpoints', 'saveServicesEndpoints'])
            ->getMock();

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getCallEndpointsDirectoryMethod')
            ->willReturn(WebserviceClient::CALL_WITH_CURL);
        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('curlExecWrapper')
            ->willReturn(null);

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('getCachePool')
            ->willReturn($mockCacheInterface);

        $mockCacheInterface->expects($this->atLeastOnce())
            ->method('hasServicesEndpoints')
            ->willReturn(false);

        $mockWebserviceClient->setUseFailover(true);

        $methodUnderTest = $this->getMethod($mockWebserviceClient, 'getAllFailoverServicesEndpoint');
        $methodParam = 'doAuthorization';
        $nextTryNumParam = 2;
        $callStartParam = 1;
        $this->setProtectedPropertyForClass(WebserviceClient::class, $mockWebserviceClient, 'sdkDefaultLocation', 'sdkDefaultLocation xxx');

        // Test
        $result = $methodUnderTest->invokeArgs($mockWebserviceClient, array($methodParam, $nextTryNumParam, $callStartParam, new SoapFault('0', 'error')));

        // Verif
        $this->assertNotEmpty($result);
        $this->assertEquals('sdkDefaultLocation xxx', $result[0]);
    }

    /**
     * @throws ReflectionException
     * @throws SoapFault
     * @throws Exception
     */
    public function test__setFailoverOptions()
    {
        $webserviceClient = new WebserviceClient(null, null, null, array(), array());
        $options = array();
        $options['services_with_failover'] = 'doAuthorizationMethod';
        $options['disabled'] = true;

        // Test
        $setFailoverOptions = $webserviceClient->setFailoverOptions($options);

        $this->assertNotNull($setFailoverOptions);
        $protectedProperty = $this->getProtectedProperty($webserviceClient, 'failoverOptions');
        $this->assertNotNull($protectedProperty);
        $servicesWithFailoverProperty = $this->getProtectedProperty($webserviceClient, 'servicesWithFailover');
        $this->assertNotNull($servicesWithFailoverProperty);
        $this->assertEquals('doAuthorizationMethod', $servicesWithFailoverProperty);
        $useFailvoverProperty = $this->getProtectedProperty($webserviceClient, 'useFailvover');
        $this->assertFalse($useFailvoverProperty);
    }

    /**
     * @throws ReflectionException
     * @throws SoapFault
     * @throws Exception
     */
    public function test__setFailoverOptions_withException()
    {
        $constructorArgsArray = [null, null, null, array(), array()];

        $mockWebserviceClient = $this->getMockBuilder(WebserviceClient::class)
            ->setConstructorArgs($constructorArgsArray)
            ->onlyMethods(['setWebserviceProperty'])
            ->getMock();

        $this->getMethod($mockWebserviceClient, 'setWebserviceProperty');

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('setWebserviceProperty')
            ->withAnyParameters()
            ->willReturn(false);

        $options = array();
        $options['services_with_failover'] = 'doNothingMethod'; // Unknow method
        $options['disabled'] = true;

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot set property "services_with_failover" via setFailoverOptions');

        // Test
        $setFailoverOptions = $mockWebserviceClient->setFailoverOptions($options);

        $this->assertNull($setFailoverOptions);
    }

    /**
     * @throws ReflectionException
     * @throws SoapFault
     * @throws Exception
     */
    public function test__setWebserviceProperty_shouldReturnFalse()
    {
        $webserviceClient = new WebserviceClient(null, null, null, array(), array());
        $method = $this->getMethod($webserviceClient, 'setWebserviceProperty');

        // Test
        $result = $method->invokeArgs($webserviceClient, array('xxxproperty Unknownxxx', null));

        $this->assertFalse($result);
    }

    /**
     * @throws ReflectionException
     * @throws SoapFault
     * @throws Exception
     */
    public function test__call_withFault()
    {
        $constructorArgsArray = [null, null, null, array(), array()];

        $mockWebserviceClient = $this->getMockBuilder(WebserviceClient::class)
            ->setConstructorArgs($constructorArgsArray)
            ->onlyMethods(['buildClientSdk'])
            ->getMock();

        $this->getMethod($mockWebserviceClient, 'buildClientSdk');

        $mockSoapClient = $this->getMockBuilder(SoapClient::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['__getLastRequest', '__getLastRequestHeaders', '__getLastResponseHeaders'])
            ->getMock();

        $mockWebserviceClient->expects($this->atLeastOnce())
            ->method('buildClientSdk')
            ->withAnyParameters()
            ->willReturn($mockSoapClient);

        $this->expectException(SoapFault::class);
        $this->expectExceptionMessage('Error finding "uri" property');

        // Test
        $result = $mockWebserviceClient->__call('doAuthorization');

        $this->assertNotNull($result);
    }


    /**
     * Fonction utilisée pour modifier la valeur d'une propriété par réfléxion
     * @throws ReflectionException
     */
    function setProtectedProperty($obj, $property, $value)
    {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }

    /**
     * Fonction utilisée pour modifier la valeur d'une propriété par réfléxion
     * @throws ReflectionException
     */
    function setProtectedPropertyForClass($class, $obj, $property, $value)
    {
        $reflection = new ReflectionClass($class);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($obj, $value);
    }


    /**
     * Fonction utilisée pour récupérer la valeur d'une propriété par réfléxion
     * @throws ReflectionException
     */
    function getProtectedProperty($obj, $property)
    {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($obj);
    }

    /**
     * Fonction utilisée pour appeler tester les fonctions protected
     * @param $obj
     * @param $name
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    function getMethod($obj, $name): ReflectionMethod
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
        return $method;
    }

    /**
     * @param $mockWebserviceClient
     * @param $property
     * @return mixed
     * @throws ReflectionException
     */
    public function getPropertyForMock($mockWebserviceClient, $property)
    {
        $reflection = new ReflectionClass(WebserviceClient::class);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($mockWebserviceClient);
    }
}
