<?php

class Mobilpay_Payment_Product_Code
{
    const ERROR_INVALID_PARAMETER = 0x11110001;
    const ERROR_INVALID_STATUS = 0x11110002;
    const ERROR_ITEM_INSERT_INVALID_INDEX = 0x11110003;
    
    const ERROR_LOAD_FROM_XML_ATTR_MISSING = 0x31110001;
    
    public $language = null;
    public $name = null;
    public $details = null;
    public $price = null;
    public $currency = null;
    public $code = null;
    public $delivery = null;
    public $ok_message = null;
    public $crc = null;
    
    protected $_structure = array(
        'crc' => array(
            'node' => 1 , 
            'req' => 1) , 
        'code' => array(
            'node' => 0 , 
            'req' => 1) , 
        'delivery' => array(
            'node' => 0 , 
            'req' => 0) , 
        'language' => array(
            'node' => 0 , 
            'req' => 0) , 
        'name' => array(
            'node' => 1 , 
            'req' => 0) , 
        'details' => array(
            'node' => 1 , 
            'req' => 0) , 
        'ok_message' => array(
            'node' => 1 , 
            'req' => 0) , 
        'price' => array(
            'node' => 0 , 
            'req' => 0) , 
        'currency' => array(
            'node' => 0 , 
            'req' => 0));

    public function __construct (DOMNode $elem = null)
    {

        if ($elem != null)
        {
            $this->loadFromXml($elem);
        }
    }

    protected function loadFromXml (DOMNode $elem)
    {

        foreach ($this->_structure as $key => $props)
        {
            switch ($props['node'])
            {
                case 0:
                    $attr = $elem->attributes->getNamedItem($key);
                    if ($attr != null)
                    {
                        $this->$key = $attr->nodeValue;
                    }
                    break;
                case 1:
                    $tmp = $elem->getElementsByTagName($key);
                    if ($tmp == null && $props['req'])
                    {
                        throw new Exception("Mobilpay_Payment_Product_Code :: loadFromXml failed; {$key} attribute missing", self::ERROR_LOAD_FROM_XML_ATTR_MISSING);
                    }
                    $this->$key = $tmp->item(0)->nodeValue;
                    break;
            }
        }
    
    }

    public function createXmlElement (DOMDocument $xmlDoc)
    {

        if (! ($xmlDoc instanceof DOMDocument))
        {
            throw new Exception('', self::ERROR_INVALID_PARAMETER);
        }
        
        $xmlPc = $xmlDoc->createElement('product_code');
        
        foreach ($this->_structure as $key => $props)
        {
            if ($this->$key === null && $props['req'])
            {
                throw new Exception("Invalid value for {$key} ", self::ERROR_LOAD_FROM_XML_ATTR_MISSING);
            }
            
            switch ($props['node'])
            {
                case 0:
                    $xmlAttr = $xmlDoc->createAttribute($key);
                    $xmlAttr->nodeValue = $this->$key;
                    $xmlPc->appendChild($xmlAttr);
                    break;
                case 1:
                    $xmlNode = $xmlDoc->createElement($key, $this->$key);
                    $xmlPc->appendChild($xmlNode);
                    break;
            }
        }
        
        return $xmlPc;
    }

}
