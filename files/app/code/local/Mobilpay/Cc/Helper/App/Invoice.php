<?php

class Mobilpay_Cc_Helper_App_Invoice extends Mage_Core_Helper_Abstract
{

    public function get ($invoiceCode = null)
    {

        if ($invoiceCode === null)
        {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $quote->reserveOrderId();
            // Mage::getModel('checkout/type_onepage')->getQuote()->getLastRealOrderId()
            //   $invoiceCode = $quote->getReservedOrderId();
            $invoiceCode = $quote->getId();
        }
        
        $path = Mage::getModuleDir(false, 'Mobilpay_Cc');
        
        $obj = new Mobilpay_Payment_AppEncode();
        
        $code = $obj->getCode($invoiceCode, array(
            'sellerCode' => 1825 , 
            'type' => 2 | Mobilpay_Payment_AppEncode::INVOICE));
        
        //print_r($product);die();
        //$type=$product->getAttribute('mobilpay_app_code_type');
        return "{$code}:{$invoiceCode}";
    }
}