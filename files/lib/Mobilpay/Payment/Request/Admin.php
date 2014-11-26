<?php

class Mobilpay_Payment_Request_Admin extends Mobilpay_Payment_Request_Abstract
{
     const ERROR_LOAD_FROM_XML_ORDER_INVOICE_ELEM_MISSING = 0x30000001;
     
     public $sellerAccount = null;

     function __construct ()
     {

          parent::__construct();
          $this->type = self::PAYMENT_TYPE_ADMIN;
     }

     protected function _loadFromXml (DOMElement $elem)
     {

          parent::_parseFromXml($elem);
          
          //card request specific data
          $elems = $elem->getElementsByTagName('seller_account');
          if ($elems->length != 1)
          {
               throw new Exception('Mobilpay_Payment_Request_Admin::loadFromXml failed; seller account element is missing', self::ERROR_LOAD_FROM_XML_ORDER_INVOICE_ELEM_MISSING);
          }
          
          $this->sellerAccount = new Mobilpay_Payment_SellerAccount($elems->item(0));
          
          return $this;
     }

     protected function _prepare ()
     {

          if (is_null($this->signature) || ! ($this->sellerAccount instanceof Mobilpay_Payment_SellerAccount))
          {
               throw new Exception('One or more mandatory properties are invalid!', self::ERROR_PREPARE_MANDATORY_PROPERTIES_UNSET);
          }
          
          $this->_xmlDoc = new DOMDocument('1.0', 'utf-8');
          $rootElem = $this->_xmlDoc->createElement('order');
          
          //set id attribute
          $xmlAttr = $this->_xmlDoc->createAttribute('id');
          $xmlAttr->nodeValue = rand();
          $rootElem->appendChild($xmlAttr);
          
          //set payment type attribute
          $xmlAttr = $this->_xmlDoc->createAttribute('type');
          $xmlAttr->nodeValue = $this->type;
          $rootElem->appendChild($xmlAttr);
          

          //set timestamp attribute
          $xmlAttr = $this->_xmlDoc->createAttribute('timestamp');
          $xmlAttr->nodeValue = date('YmdHis');
          $rootElem->appendChild($xmlAttr);
          
          $xmlElem = $this->_xmlDoc->createElement('signature');
          $xmlElem->nodeValue = $this->signature;
          $rootElem->appendChild($xmlElem);
          

          $xmlElem = $this->sellerAccount->createXmlElement($this->_xmlDoc);
          $rootElem->appendChild($xmlElem);
          

          $this->_xmlDoc->appendChild($rootElem);
          
          return $this;
     }

}
