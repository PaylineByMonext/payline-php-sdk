<?php


namespace test\Payline;

use Payline\SoapVarFactory;
use PHPUnit\Framework\Constraint\Count;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

class SoapVarFactoryTest extends TestCase
{

    public function test_CreateObject()
    {
        // Data
        $objectFactory = new SoapVarFactory();
        $data = array();
        $list = array();
        $list['key'] = 'aaaa';
        $list['value'] = 'aaaa value';
        $data['PrivateDataList'] = $list;

        // Test
        $result = $objectFactory->create('PrivateDataList', $data);

        // Verif
        $this->assertNotNull($result);
        $this->assertEquals('aaaa', $result[0]);
        $this->assertEquals('aaaa value', $result[1]);
    }

    public function test_userDataIsNotEmpty_withCountable()
    {
        // Data
        $soapVarFactory = new SoapVarFactory();
        $methodUserDataIsNotEmpty = $this->getMethod($soapVarFactory, 'userDataIsNotEmpty');
        $count = new Count(5);

        // Test
        $result = $methodUserDataIsNotEmpty->invokeArgs($soapVarFactory, array($count));

        // Verif
        $this->assertNotNull($result);
        $this->assertTrue($result);
    }
    public function test_userDataIsNotEmpty_withNumber()
    {
        // Data
        $soapVarFactory = new SoapVarFactory();
        $methodUserDataIsNotEmpty = $this->getMethod($soapVarFactory, 'userDataIsNotEmpty');

        // Test
        $result = $methodUserDataIsNotEmpty->invokeArgs($soapVarFactory, array(0));

        // Verif
        $this->assertNotNull($result);
        $this->assertTrue($result);
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
}
