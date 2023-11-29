<?php

namespace test\Payline;
use Monolog\Logger;
use Payline\PaylineSDK;
use PHPUnit\Framework\TestCase;
use SoapClient;

class PaylineSDKTest extends TestCase
{
    // Given
    const merchant_id = '1111111';
    const access_key = 'xxxyyy222';
    const environment = PaylineSDK::ENV_HOMO;
    const logLvl = Logger::API;

    public $soapClientMock = null;

    public function testNewPaylineSDKInstance()
    {
        // Test - Try to create an instance
        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
            self::environment, $pathLog= null, self::logLvl);

        // Then
        $this->assertNotNull($paylineSDK);
    }
//
//    public function testCallDoAuthorization()
//    {
//        // Given
//        // Create instance
//        $paylineSDK = new PaylineSDK(self::merchant_id, self::access_key, null, null, null, null,
//            self::environment, $pathLog= null, self::logLvl);
//        // Create Call
//
//        // Mock SOAP API
////        $soapClientMock = $this->createMock(SoapClient::class);
////        $soapClientMock = $this->getMockBuilder('\pClient');
////        $soapClientMock = $this->getMockFromWsdl(__DIR__ . '\wsdl\DirectPaymentAPI.wsdl')
////        ->method('__soapCall')
////        ->willReturn($reponseMock);
////        $soapClientMock
////            ->method('__soapCall')
////            ->willReturn('');
//
//        $reponseMock = array();
//        $reponseMock['result']['code'] = '00000';
//
////        $soapClientMock = $this->getMockBuilder('\SoapClient')
////            ->disableOriginalConstructor()
////            ->getMock();
//
//
////        $soapClientMock = $this
////            ->getMockBuilder(\SoapClient::class)
////            ->disableOriginalConstructor()
//////            ->setMethods(['__soapCall'])
////            ->getMock();
//
////        $soapClientMock = $this->getMockBuilder('SoapClient')
////            ->disableOriginalConstructor()
////            ->getMock();
//        $soapClientMock = $this->getMockFromWsdl(__DIR__ . '\wsdl\DirectPaymentAPI.wsdl')
//            ->method('__soapCall')
//            ->willReturn($reponseMock);
//
////        $options = array();
////        $options['uri'] = 'http://google.com';
////
////        $sdkWsdl = __DIR__ . '\wsdl\DirectPaymentAPI.wsdl';
////        $sdkClient = new SoapClient($sdkWsdl, $options);
//
////        $soapClientMock->expects($this->once())
////            ->method('__soapCall')
//////            ->with([ 'countryCode' => /*Put the value you want here*/, 'vatNumber' => /*Put the value you want here*/ ])
////            ->willReturn($reponseMock);
////
////        $soapClientMock->expects($this->once())
////            ->method('__call')
//////            ->with([ 'countryCode' => /*Put the value you want here*/, 'vatNumber' => /*Put the value you want here*/ ])
////            ->willReturn($reponseMock);
//
//
//        // Test - Call doAuthorization
//        $doAuthorizationRequest = array();
//
//        $doAuthorizationRequest['cancelURL'] = 'https://Demo_Shop.com/cancelURL.php';
//        $doAuthorizationRequest['returnURL'] = 'https://Demo_Shop.com/returnURL.php';
//        $doAuthorizationRequest['notificationURL'] = 'https://Demo_Shop.com/notificationURL.php';
//        // PAYMENT
//        $doAuthorizationRequest['payment']['amount'] = 1000; // this value has to be an integer amount is sent in cents
//        $doAuthorizationRequest['payment']['currency'] = 978; // ISO 4217 code for euro
//        $doAuthorizationRequest['payment']['action'] = 100; // 101 stand for "authorization+capture"
//        $doAuthorizationRequest['payment']['mode'] = 'CPT'; // one shot payment
//        $doAuthorizationRequest['payment']['contractNumber'] = 'CB';
//        // ORDER
//        $doAuthorizationRequest['order']['ref'] = 'myOrderRef_35656'; // the reference of your order
//        $doAuthorizationRequest['order']['amount'] = 1000; // may differ from payment.amount if currency is different
//        $doAuthorizationRequest['order']['currency'] = 978; // ISO 4217 code for euro
////        $doAuthorizationRequest['order']['date'] = '15/12/2014 12:20';
//        // CARD
//        $doAuthorizationRequest['card']['number'] = '4444333322221111';
//        $doAuthorizationRequest['card']['type'] = 'CB';
//        $doAuthorizationRequest['card']['expirationDate'] = '1235';
//        $doAuthorizationRequest['card']['cvx'] = '123';
//        $doAuthorizationRequest['card']['cardholder'] = 'Marcel Patoulatchi';
//
//
//        $doAuthorizationResponse = $paylineSDK->doAuthorization($doAuthorizationRequest);
//
//
//        // Then
//        $this->assertNotNull($doAuthorizationResponse);
//        // Should get ResultCode OK
//        $this->assertEquals('00000', $doAuthorizationResponse['result']['code']);
//        $this->assertEquals('ACCEPTED', $doAuthorizationResponse['result']['shortMessage']);
//        $this->assertNotNull($doAuthorizationResponse['token']);
//    }
}
