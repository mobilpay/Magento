<?php

/**
 * Class Mobilpay_Payment_Address
 * @copyright NETOPIA System
 * @author Claudiu Tudose
 * @version 1.0
 * 
 */
class Mobilpay_Payment_Address
{
    const TYPE_COMPANY = 'company';
    const TYPE_PERSON = 'person';
    
    const ERROR_INVALID_PARAMETER = 0x11100001;
    const ERROR_INVALID_ADDRESS_TYPE = 0x11100002;
    const ERROR_INVALID_ADDRESS_TYPE_VALUE = 0x11100003;
    
    public $type = null;
    public $firstName = null;
    public $lastName = null;
    public $fiscalNumber = null;
    public $identityNumber = null;
    public $country = null;
    public $county = null;
    public $city = null;
    public $zipCode = null;
    public $address = null;
    public $email = null;
    public $mobilePhone = null;
    public $bank = null;
    public $iban = null;

    public function __construct (DOMNode $elem = null)
    {

        if ($elem != null)
        {
            $this->loadFromXml($elem);
        }
    }

    protected function loadFromXml (DOMNode $elem)
    {

        $attr = $elem->attributes->getNamedItem('type');
        if ($attr != null)
        {
            $this->type = $attr->nodeValue;
        } else
        {
            $this->type = self::TYPE_PERSON;
        }
        $elems = $elem->getElementsByTagName('first_name');
        if ($elems->length == 1)
        {
            $this->firstName = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('last_name');
        if ($elems->length == 1)
        {
            $this->lastName = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('fiscal_number');
        if ($elems->length == 1)
        {
            $this->fiscalNumber = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('identity_number');
        if ($elems->length == 1)
        {
            $this->identityNumber = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('country');
        if ($elems->length == 1)
        {
            $this->country = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('county');
        if ($elems->length == 1)
        {
            $this->county = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('city');
        if ($elems->length == 1)
        {
            $this->city = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('zip_code');
        if ($elems->length == 1)
        {
            $this->zipCode = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('address');
        if ($elems->length == 1)
        {
            $this->address = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('email');
        if ($elems->length == 1)
        {
            $this->email = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('mobile_phone');
        if ($elems->length == 1)
        {
            $this->mobilePhone = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('bank');
        if ($elems->length == 1)
        {
            $this->bank = urldecode($elems->item(0)->nodeValue);
        }
        $elems = $elem->getElementsByTagName('iban');
        if ($elems->length == 1)
        {
            $this->iban = urldecode($elems->item(0)->nodeValue);
        }
    }

    public function createXmlElement (DOMDocument $xmlDoc, $nodeName)
    {

        if (! ($xmlDoc instanceof DOMDocument))
        {
            throw new Exception('', self::ERROR_INVALID_PARAMETER);
        }
        
        $addrElem = $xmlDoc->createElement($nodeName);
        
        if ($this->type == null)
        {
            throw new Exception('Invalid address type', self::ERROR_INVALID_ADDRESS_TYPE);
        } elseif ($this->type != self::TYPE_COMPANY && $this->type != self::TYPE_PERSON)
        {
            throw new Exception('Invalid address type', self::ERROR_INVALID_ADDRESS_TYPE_VALUE);
        }
        
        $xmlAttr = $xmlDoc->createAttribute('type');
        $xmlAttr->nodeValue = $this->type;
        $addrElem->appendChild($xmlAttr);
        
        if ($this->firstName != null)
        {
            $xmlElem = $xmlDoc->createElement('first_name');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->firstName)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->lastName != null)
        {
            $xmlElem = $xmlDoc->createElement('last_name');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->lastName)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->fiscalNumber != null)
        {
            $xmlElem = $xmlDoc->createElement('fiscal_number');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->fiscalNumber)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->identityNumber != null)
        {
            $xmlElem = $xmlDoc->createElement('identity_number');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->identityNumber)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->country != null)
        {
            $xmlElem = $xmlDoc->createElement('country');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->country)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->county != null)
        {
            $xmlElem = $xmlDoc->createElement('county');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->county)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->city != null)
        {
            $xmlElem = $xmlDoc->createElement('city');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->city)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->zipCode != null)
        {
            $xmlElem = $xmlDoc->createElement('zip_code');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->zipCode)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->address != null)
        {
            $xmlElem = $xmlDoc->createElement('address');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->address)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->email != null)
        {
            $xmlElem = $xmlDoc->createElement('email');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->email)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->mobilePhone != null)
        {
            $xmlElem = $xmlDoc->createElement('mobile_phone');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->mobilePhone)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->bank != null)
        {
            $xmlElem = $xmlDoc->createElement('bank');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->bank)));
            $addrElem->appendChild($xmlElem);
        }
        
        if ($this->iban != null)
        {
            $xmlElem = $xmlDoc->createElement('iban');
            $xmlElem->appendChild($xmlDoc->createCDATASection(urlencode($this->iban)));
            $addrElem->appendChild($xmlElem);
        }
        
        return $addrElem;
    }

    public function toArray ()
    {

        return array(
            'ppiFirstName' => $this->firstName , 
            'ppiLastName' => $this->lastName , 
            'ppiCountry' => $this->country , 
            'ppiCounty' => $this->county , 
            'ppiCity' => $this->city , 
            'ppiPostalCode' => $this->zipCode , 
            'ppiAddress' => $this->address , 
            'ppiEmail' => $this->email , 
            'ppiPhone' => $this->mobilePhone , 
            'ppiBank' => $this->bank , 
            'ppiIban' => $this->iban , 
            'ppiFiscalNumber' => $this->fiscalNumber , 
            'ppiIdentityNumber' => $this->identityNumber);
    }
}
