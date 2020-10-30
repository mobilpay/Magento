<?php

class Mobilpay_Payment_SellerAccount
{
     const ERROR_INVALID_PARAMETER = 0x11110001;
     const ERROR_INVALID_STATUS = 0x11110002;
     const ERROR_ITEM_INSERT_INVALID_INDEX = 0x11110003;
     
     const ERROR_LOAD_FROM_XML_CURRENCY_ATTR_MISSING = 0x31110001;
     
     public $status = null;
     public $paymentMethods = null;

     public function __construct (DOMNode $elem = null)
     {

          if ($elem != null)
          {
               $this->loadFromXml($elem);
          }
     }

     protected function loadFromXml (DOMNode $elem)
     {

          $status = $elem->getElementsByTagName('status');
          if ($status == null )
          {
               throw new Exception('Mobilpay_Payment_SellerAccount::loadFromXml failed; status attribute missing', self::ERROR_LOAD_FROM_XML_CURRENCY_ATTR_MISSING);
          }
          
          $this->status = $status->item(0)->nodeValue;
          
          $pm = $elem->getElementsByTagName('payment_methods');
          if ($pm != null)
          {
               $pm1 = array();
               if ($pm->length == 1)
               {
                    $sm = $pm->item(0)->getElementsByTagName('sms');
                    if ($sm->length == 1)
                    {
                         $status = $sm->item(0)->nodeValue;
                         $pm1['sms'] = $status;
                    }
                    $card = $pm->item(0)->getElementsByTagName('card');
                    if ($sm->length == 1)
                    {
                         $status = $card->item(0)->nodeValue;
                         $pm1['card'] = $status;
                    }
               }
          }
          $this->paymentMethods = $pm1;
     
     }

     public function createXmlElement (DOMDocument $xmlDoc)
     {

          if (! ($xmlDoc instanceof DOMDocument))
          {
               throw new Exception('', self::ERROR_INVALID_PARAMETER);
          }
          
          $xmlSac = $xmlDoc->createElement('seller_account');
          

          if ($this->status === null)
          {
               throw new Exception('Invalid status', self::ERROR_INVALID_STATUS);
          }
          
          $xmlStaus = $xmlDoc->createElement('status',$this->status);
          
          $xmlSac->appendChild($xmlStaus);
          if ($this->paymentMethods === null || ! is_array($this->paymentMethods))
          {
               throw new Exception('Invalid payment methods', self::ERROR_INVALID_STATUS);
          
          }
          $xmlPaymentMethods = $xmlDoc->createElement('payment_methods');
          foreach ($this->paymentMethods as $type => $status)
          {
               if (in_array($type, array(
                    'sms' , 
                    'card')))
               {
                    $$type = $xmlDoc->createElement($type, $status);
                    $xmlPaymentMethods->appendChild($$type);
               }
          }
          $xmlSac->appendChild($xmlPaymentMethods);
          
          return $xmlSac;
     }

     public function setStatus ($status)
     {

          $this->status = $status;
     }

     public function getStatus ()
     {

          return $this->status;
     }

}
