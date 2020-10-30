<?php

class Mobilpay_Cc_Model_Product
{

    public function processRequest ($objPmReq)
    {

        $product = Mage::getModel('catalog/product');
        
        try
        {
            
            $products = Mage::getModel('catalog/product_api');
            $prds = $products->items(array(
                'mobilpay_app_code' => $objPmReq->product->code));
            
            if (sizeof($prds) > 0)
            {
                
                $product->load($prds[0]['product_id']);
                $price = $product->getPrice();
                $name = $product->getName();
                $details = $product->getName();
                $currency = Mage::app()->getStore($product->getStoreId())->getCurrentCurrency()->currency_code;
            
            } else
            {
            	
                $obj = new Mobilpay_Payment_AppEncode();
                $infos = $obj->revertCode($objPmReq->product->code);
                $order_id = $objPmReq->orderId;
                if (($infos['type'] & Mobilpay_Payment_AppEncode::INVOICE) == Mobilpay_Payment_AppEncode::INVOICE)
                {
                    
                    $order = Mage::getModel('sales/order');
                    
                    $order->loadByAttribute('ext_order_id', $objPmReq->product->code);
                    //print_r($order->loadByIncrementId(100000377)->toArray());die();
                    

                    if ($order->getId())
                    {
                    	
                        $currency = $order->getOrderCurrency()->currency_code;
                        $price = $order->getTotalDue();
                        $items = $order->getAllItems();
                        $details = sizeof($items) . " itaaaem(s)";
                        foreach ($items as $item)
                        {
                            $details .= "" . $item->getName();
                        }
                        $objPmReq->orderId = $objPmReq->product->orderId = $order->getIncrementId();
                    
                    } else
                    {
                        
                        $sess = Mage::getSingleton('checkout/session');
                        $sess->setQuoteId($infos['code']);
                        $quote = $sess->getQuote();
                        $totals = $quote->getTotals();
                        $price = $totals['grand_total']->getValue();
                        $items = $quote->getAllItems();
                        $details = sizeof($items) . " item(s)aaa";
                        foreach ($items as $item)
                        {
                            $details .= "\n\r" . $item->getName();
                        }
                        
                        $name = $details;
                        
                        $quote->collectTotals();
                        
                        $service = Mage::getModel('sales/service_quote', $quote);
                        if (! $quote->getBillingAddress)
                        {
                            $customer = $quote->getCustomer();
                            $quote->setBillingAddress($customer->getDefaultBillingAddress());
                        }
                        if (! $quote->getShippingAddress())
                        {
                            $quote->setShippingAddress($quote->getBillingAddress());
                        }
                        
                        $shippingMethod = 'flatrate_flatrate';
                        
                        $quote->getShippingAddress()->requestShippingRates();
                        $quote->getShippingAddress()->collectShippingRates();
                        
                        $rate = $quote->getShippingAddress()->getShippingRateByCode($shippingMethod);
                        //$quote->setCheckoutMethod('mobilpay_cc');
                        if (! $rate)
                        {
                            throw new Exception('NO_SHIPPING_AVAIL', 1112);
                        }
                        
                        $quote->getShippingAddress()->setShippingMethod('flatrate_flatrate');
                        
                        if ($quote->isVirtual())
                        {
                            $quote->getBillingAddress()->setPaymentMethod('mobilpay_cc');
                        } else
                        {
                            $quote->getShippingAddress()->setPaymentMethod('mobilpay_cc');
                        }
                        $payment = $quote->getPayment();
                        $payment->importData(array(
                            'method' => 'mobilpay_cc'));
                        
                        $quote->save();
                        $currency = Mage::app()->getStore($quote->getStoreId())->getCurrentCurrency()->currency_code;
                        $service->submitAll();
                        $order = $service->getOrder();
                        $order->setData('ext_order_id', $objPmReq->product->code);
                        $order->save();
                        $objPmReq->orderId = $objPmReq->product->orderId = $order->getIncrementId();
                    }
                } else
                {
                    $product->load($infos['code']);
                    if (! $product->getId())
                    {
                        throw new Exception('INVALID_PRODUCT_CODE', 1111);
                    }
                    $currency = Mage::app()->getStore($product->getStoreId())->getCurrentCurrency()->currency_code;
                    $price = $product->getPrice();
                    $name = $product->getName();
                    $details = $product->getName();
                }
            
            }
        } catch (Exception $e)
        {
            
            throw $e;
        }
        
        $objPmReq->product->price = $price;
        
        $objPmReq->product->currency = $currency;
        $objPmReq->product->name = $name;
        $objPmReq->product->details = $details;
        $objPmReq->product->delivery = mt_rand(1, 3);
        header('Content-type: application/xml');
        echo $objPmReq->getXml()->saveXML();
        die();
    }
}