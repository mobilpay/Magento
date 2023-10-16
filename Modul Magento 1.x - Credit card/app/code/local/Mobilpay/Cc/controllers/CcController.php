<?php

/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Mage
 * @package    Mage_Bibit
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 */
class Mobilpay_Cc_CcController extends Mage_Core_Controller_Front_Action
{

    public function getCc ()
    {

        return Mage::getSingleton('cc/cc');
    }

    public function redirectAction ()
    {

        $session = Mage::getSingleton('checkout/session');
        $session->setCcQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('cc/redirect')->toHtml());
        $session->unsQuoteId();
    }

    public function cancelAction ()
    {

        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getCcQuoteId(true));
        if ($session->getLastRealOrderId())
        {
            $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());
            if ($order->getId())
            {
                $order->cancel()->save();
            }
        }
        $this->_redirect('checkout/cart');
    }

    public function successAction ()
    {

        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getCcQuoteId(true));
       
        $id = $this->_request->getParam('orderId', null);
        if ($id)
        {
            $order = Mage::getModel('sales/order')->loadByIncrementId($id);
            if (($order->getStatus() == Mage_Sales_Model_Order::STATE_CANCELED || Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW))
            {
                
                try
                {
                    
                    $msg = current($order->getAllStatusHistory())->getComment();
                    Mage::getSingleton('customer/session')->addNotice($msg);
                    //$this->loadLayout();
                    //$this->_initLayoutMessages('customer/session');                                        
                    //$this->renderLayout();
                    $this->_redirect('customer/account', array(
                        '_secure' => true));
                
                } catch (Exception $e)
                {
                
                }
                return;
            }
        }
        
        Mage::getSingleton('checkout/session')->getQuote()->setIsActive(false)->save();
        $this->_redirect('checkout/onepage/success', array(
            '_secure' => true));
    }

    function ipnAction ()
    {

        $objPmReq = $this->_processRequest();
        try
        {
            if ($objPmReq instanceof Mobilpay_Payment_Request_Info)
            {
                Mage::getSingleton('cc/product')->processRequest($objPmReq);
            } else
            {
                Mage::getSingleton('cc/cc')->processNotification($objPmReq);
            }
        } catch (Exception $e)
        {
        	//print_r($e);
            $this->_sendResponse(Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY, $e->getCode()+100, $e->getMessage());
        }
    }

    function productInfoAction ()
    {

        $objPmReq = $this->_processRequest();
        try
        {
            Mage::getSingleton('cc/product')->processRequest($objPmReq);
        } catch (Exception $e)
        {
            $this->_sendResponse(Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY, $e->getCode(), $e->getMessage());
        }
    }

    private function _processRequest ()
    {

        error_reporting(E_ALL);
        
        $request = $this->getRequest();
        if ($request->isPost())
        {
            $envKey = $request->getParam('env_key', false);
		$cipher     = 'rc4';
		$iv         = null;
		if(array_key_exists('cipher', $request->getParams()))
		{
		    $cipher = $request->getParam('cipher', false);
		    if(array_key_exists('iv', $request->getParams()))
		    {
		        $iv = $request->getParam('iv', false);
		    }
		}
            $envData = $request->getParam('data', false);
            if ($envKey && $envData)
            {
                $path = Mage::getModuleDir('local', 'Mobilpay_Cc') . DS . "etc/certificates" . DS;
                $cc = Mage::getModel('cc/cc');

		if ($cc->getConfigData('debug') == 1) {
			$privateKeyFilePath = $path . "sandbox.".$cc->getConfigData('signature')."private.key";
			}
		else {
			$privateKeyFilePath = $path . "live.".$cc->getConfigData('signature')."private.key";
			}

                try
                {
                    $objPmReq = Mobilpay_Payment_Request_Abstract::factoryFromEncrypted($envKey, $envData, $privateKeyFilePath, null, $cipher, $iv);
                } catch (Exception $e)
                {
                    
                    $this->_sendResponse(Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_TEMPORARY, $e->getCode(), $e->getMessage());
                }
            
            }
        }
        
        return $objPmReq;
    }

    private function _sendResponse ($errorType, $errorCode, $errorMessage)
    {

        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        if ($errorCode == 0)
        {
            echo "<crc>{$errorMessage}</crc>";
        } else
        {
            echo "<crc error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
        }
        exit();
    }

}
