<?php

namespace Payline;

class SoapVarFactory
{
    /**
     * Basename namespace for WSDLs objects
     */
    const ROOT_CLASSNAME = "Payline\\Objects\\";

    /**
     * SOAP name of address object
     */
    const SOAP_ADDRESS = 'address';

    /**
     * namespace used in web services descriptor
     */
    const PAYLINE_NAMESPACE = 'http://obj.ws.payline.experian.com';

    /**
     * @param $elementKey
     * @param $data
     * @param $default
     * @return \Payline\Objects\AbstractObject|null
     */
    public function create($elementKey, $data) {
        $paylineObject = $this->createObject($elementKey, !empty($data[$elementKey]) ? $data[$elementKey] : null);

        return $paylineObject;
    }


    /**
     * @param $elementKey
     * @param $data
     * @param $default
     * @return \Payline\Objects\AbstractObject|null
     */
    public function createObject($elementKey, $data=null, $baseClassname=null) {
        $newObject = null;
        if ($newClassName = $this->getPaylineClassname($elementKey, $baseClassname)) {
            $newObject = new $newClassName();

            $soapVarType = $this->getSoapVarType($elementKey, $baseClassname);

            if($data && is_array($data)) {
                //TODO: Move list test in fillObject method
                if(preg_match('/(.*)List$/', $elementKey,$matchClassList)) {
                    $pseudoListObject = [];
                    $itemElementKey = $matchClassList[1];
                    $itemClassName = $this->getPaylineClassname($itemElementKey, $baseClassname);
                    if($itemClassName) {
                        foreach ($data as $itemData) {
                            if(is_array($itemData)) {
                                $pseudoListObject[] = $this->getSoapVar($this->fillObject($itemData, new $itemClassName()), $soapVarType);
                            } else {
                                $pseudoListObject[] = $itemData;
                            }
                        }
                    } elseif(property_exists($newObject, lcfirst($itemElementKey))) {
                        $pseudoListObject = $newObject->{lcfirst($itemElementKey)} = $data;
                    }
                    return $pseudoListObject;
                }

                $this->fillObject($data, $newObject);
            }


            return $this->getSoapVar($newObject, $soapVarType);
        }

        return $newObject;
    }

    /**
     * Get SOAP var type
     * Should be same type as base classname
     * Manage exceptions: Buyer/BillingAddress, Buyer/ShippingAdress and Wallet/ShippingAddress
     *
     * @param $elementKey
     * @param $baseClassname
     * @return mixed|string
     */
    protected function getSoapVarType($elementKey, $baseClassname) {
        $soapVarType = $elementKey;
        if($className = $this->getPaylineClassname($elementKey, $baseClassname) ) {

            if(is_subclass_of($className, '\Payline\Objects\Address')) {
                $soapVarType = self::SOAP_ADDRESS;
            }
        }

        return $soapVarType;
    }

    /**
     * @param $newObject
     * @param $elementKey
     * @return \SoapVar
     */
    protected function getSoapVar($newObject, $varType) {
        return new \SoapVar($newObject, SOAP_ENC_OBJECT, $varType, self::PAYLINE_NAMESPACE);
    }

    /**
     * @param array $array
     * @param \Payline\Objects\AbstractObject $object
     * @return \Payline\Objects\AbstractObject
     */
    protected function fillObject(array $array, $object) {
        if ($array) {
            foreach ($array as $propertyKey => $propertyData) {
                $objectClassName = get_class($object);
                if (property_exists($object, $propertyKey)) {
                    if($this->needCreateObjectProperty($object, $propertyKey, $objectClassName)) {
                        $object->$propertyKey = $this->createObject($propertyKey, $propertyData, $objectClassName);
                    } elseif($this->userDataIsNotEmpty($propertyData)) {
                        $object->$propertyKey = $propertyData;
                    }
                }
            }
        }
        return $object;
    }

    /**
     * @param \Payline\Objects\AbstractObject $object
     * @param $property
     * @return bool
     */
    protected function needCreateObjectProperty($object, $property, $baseClassname = null) {
        return $this->getPaylineClassname($property, $baseClassname);
    }

    /**
     * @param $elementKey
     * @return false|string
     */
    protected function getPaylineClassname($elementKey, $baseClassname = null) {
        $newClassName = "\\" . self::ROOT_CLASSNAME .
            ( $baseClassname ? str_replace(self::ROOT_CLASSNAME, "", $baseClassname) . "\\" : '' ) .
            ucfirst($elementKey);

        return class_exists($newClassName) ? $newClassName : false;
    }

    /**
     * Test user data
     *
     * @param $data
     * @return bool
     */
    protected function userDataIsNotEmpty($data) {

        if($data instanceof \Countable ) {
            return (count($data)>0);
        }

        //@see https://github.com/PaylineByMonext/payline-php-sdk/issues/101
        if($data===0 || $data==='0') {
            return true;
        }

        return !empty($data);
    }
}