<?php


namespace test\Payline;

use Monolog\Logger;
use Payline\Cache\Apc;
use Payline\WebserviceClient;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use SoapFault;

class WebserviceClientTest extends TestCase
{


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
     * @throws SoapFault
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
     * @param $name
     * @return ReflectionMethod
     * @throws ReflectionException
     */
    function getMethod($obj, $name)
    {
        $class = new ReflectionClass($obj);
        $method = $class->getMethod($name);
        // $method->setAccessible(true); // Use this if you are running PHP older than 8.1.0
        return $method;
    }

    /**
     * @param $mockWebserviceClient
     * @return mixed
     */
    public function getPropertyForMock($mockWebserviceClient, $property)
    {
        $reflection = new ReflectionClass(WebserviceClient::class);
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $headers = $property->getValue($mockWebserviceClient);
        return $headers;
    }
}
