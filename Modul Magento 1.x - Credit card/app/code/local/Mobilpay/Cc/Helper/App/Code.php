<?php

class Mobilpay_Cc_Helper_App_Code extends Mage_Core_Helper_Abstract
{

    public function get ($product2)
    {

        $path = Mage::getModuleDir(false, 'Mobilpay_Cc');
        
        $obj = new Mobilpay_Payment_AppEncode();
        //var_dump($product);die();
        $myId = $product2->getId();
        $product = mage::getModel('catalog/product');
        
        $product->load($myId);
        $typeS = $product->getAttributeText('mobilpay_app_code_type');
        
        if ($typeS != 'power_key')
        {
            $code = $obj->getCode($myId, array(
                'sellerCode' => 1825 , 
                'type' => 2));
        } else
        {
            $code = $product->mobilpay_app_code;
        }
        //print_r($product);die();
        //$type=$product->getAttribute('mobilpay_app_code_type');
        return "{$code}";
    }

    public function getForCart ()
    {

        try
        {
            $quote = Mage::getSingleton('checkout/session')->getQuote();
            $pm = Mage::getSingleton('cc/cc');
            
            $not = new Mobilpay_Payment_Request_Info();
            $not->signature = $pm->getConfigData('signature');
            $pReq = new Mobilpay_Payment_Product_Code();
            $pReq->code = '1111';
            $orderId = "ntp_" . time() . mt_rand(0, 9999999);
            
            $pReq->crc = md5(mt_rand(1, 99999) . time() . mt_rand(1, 99999));
            $not->orderId = $orderId;
            $not->product = $pReq;
            
            $currency = $quote->getBaseCurrencyCode();
            
            $price = $quote->getBaseGrandTotal();
            $items = $quote->getAllItems();
            $details = sizeof($items) . " item(s)";
            foreach ($items as $item)
            {
                $details .= "\n\r" . $item->getName();
            }
            
            $name = $details;
            
            $not->product->price = $price;
            $not->product->currency = $currency;
            $not->product->name = $name;
            $not->product->details = $details;
            $not->product->delivery = mt_rand(1, 3);
            
            //print_r($not->getXml()->saveXML());
            

            $not->encrypt(Mage::getModuleDir('local', 'Mobilpay_Cc') . DS . "etc/certificates" . DS . 'public.cer');
            
            $httpClient = new Zend_Http_Client();
            $httpClient->setUri('http://secure.duduta.dev.mobilpay.ro/en/default/app/get-code', array(
                'allow_unwise' => true));
            $httpClient->setHeaders(array(
                'Accept-encoding: '));
            $httpClient->setMethod(Zend_Http_Client::POST);
            $httpClient->setParameterPost('env_key', $not->getEnvKey());
            $httpClient->setParameterPost('data', $not->getEncData());
            $objResponse = $httpClient->request();
            $response=Mobilpay_Payment_Request_Info::factory($objResponse->getBody());
            

            return $response->product->code;
        } catch (Exception $e)
        {
            print_r($e);
            return '';
        }

        //$productInfo = Mobilpay_Payment_Request_Info::factory($objResponse->getBody());
    }
}