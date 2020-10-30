<?php

/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 */

class Mobilpay_Cc_Model_Cc extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'mobilpay_cc';
    
    protected $_infoBlockType = 'cc/info';
    protected $_formBlockType = 'cc/form';
    
    /**
     * Availability options
     */
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = false;
    protected $_canRefund = false;
    protected $_canRefundInvoicePartial = false;
    protected $_canReviewPayment = true;
    
    protected $formData;
    protected $formKey;
    protected $_order = null;
    protected $_objPmReq = null;
    protected $_newOrderStatus = null;

    public function capture (Varien_Object $payment, $amount)
    {

    }

    public function getOrderPlaceRedirectUrl ()
    {

        $e = null;
        $shipping = $this->getQuote()->getShippingAddress();
        $billing = $this->getQuote()->getBillingAddress();
        $quote = $this->getQuote();
        
        $test = array(
            'a' => 1);
        
        try
        {
            $objPmReqCard = new Mobilpay_Payment_Request_Card();
            
            $objPmReqCard->signature = $this->getConfigData('signature');
            
            if ($this->getConfigData('debug') == 1) {
			$x509FilePath = Mage::getModuleDir('local', 'Mobilpay_Cc') . DS . "etc/certificates" . DS . "sandbox.".$this->getConfigData('signature').".public.cer";
		}
		else {
			$x509FilePath = Mage::getModuleDir('local', 'Mobilpay_Cc') . DS . "etc/certificates" . DS . "live.".$this->getConfigData('signature').".public.cer";
		}
            
            $objPmReqCard->orderId = $this->getQuote()->getReservedOrderId();
            
            $objPmReqCard->returnUrl = Mage::getUrl('cc/cc/success');
            $objPmReqCard->confirmUrl = Mage::getUrl('cc/cc/ipn/');
            $objPmReqCard->cancelUrl = Mage::getUrl('cc/cc/cancel');
            
            $objPmReqCard->invoice = new Mobilpay_Payment_Invoice();
            
            $objPmReqCard->invoice->currency = $quote->getBaseCurrencyCode();
            $objPmReqCard->invoice->amount = $quote->getBaseGrandTotal();
            $cart_description = $this->getConfigData('description');
            if ($cart_description != '') $objPmReqCard->invoice->details = $cart_description;
            
            $billingAddress = new Mobilpay_Payment_Address();
            
            $company = $billing->getCompany();
            if (! empty($company))
            {
                $billingAddress->type = 'company';
            } else
            {
                $billingAddress->type = 'person';
            }
            $billingAddress->firstName = $billing->getFirstname();
            $billingAddress->lastName = $billing->getLastname();
            
            //not supported by this shopping cart $billingAddress->fiscalNumber	= $_POST['billing_fiscal_number'];
            //not supported by this shopping cart $billingAddress->identityNumber	= $_POST['billing_identity_number'];
            

            $billingAddress->country = $billing->getCountry();
            
            $billingAddress->city = $billing->getCity();
            $billingAddress->zipCode = $billing->getPostcode();
            $billingAddress->state = $billing->getRegion();
            $billingAddress->address = $billing->getStreet(1);
            $billingAddress->email = $billing->getEmail();
            $billingAddress->mobilePhone = $billing->getTelephone();
            
            //not supported by this shopping cart $billingAddress->bank	= $_POST['billing_bank'];
            //not supported by this shopping cart $billingAddress->iban	= $_POST['billing_iban'];
            

            $objPmReqCard->invoice->setBillingAddress($billingAddress);
            
            $shippingAddress = new Mobilpay_Payment_Address();
            
            $company = $shipping->getCompany();
            if (! empty($company))
            {
                $shippingAddress->type = 'company';
            } else
            {
                $shippingAddress->type = 'person';
            }
            $shippingAddress->firstName = $shipping->getFirstname();
            $shippingAddress->lastName = $shipping->getLastname();
            
            //not supported by this shopping cart $shippingAddress->fiscalNumber	= $_POST['shipping_fiscal_number'];
            //not supported by this shopping cart $shippingAddress->identityNumber	= $_POST['shipping_identity_number'];
            

            $shippingAddress->country = $shipping->getCountry();
            
            $shippingAddress->city = $shipping->getCity();
            $shippingAddress->zipCode = $shipping->getPostcode();
            $shippingAddress->state = $shipping->getRegion();
            $shippingAddress->address = $shipping->getStreet(1);
            $shippingAddress->email = $shipping->getEmail();
            $shippingAddress->mobilePhone = $shipping->getTelephone();
            
            $objPmReqCard->invoice->setShippingAddress($shippingAddress);
            
            $objPmReqCard->encrypt($x509FilePath);
        } 

        catch (Exception $e)
        {
            $error = $e->getMessage();
            Mage::throwException($error);
        }
        
        if (! ($e instanceof Exception))
        {
            Mage::getSingleton('checkout/session')->setFormData($objPmReqCard->getEncData());
            Mage::getSingleton('checkout/session')->setFormKey($objPmReqCard->getEnvKey());
        } else
        {
            $error = $e->getMessage();
            Mage::throwException($error);
        }
        
        return Mage::getUrl('cc/cc/redirect', array(
            '_secure' => true));
    }

    public function processNotification ($objPmReq)
    {

        $errorCode = 0;
        $errorType = Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_NONE;
        $errorMessage = '';
        $this->_initData($objPmReq);
       
        switch ($objPmReq->objPmNotify->action)
        {
            case 'confirmed':
                if ($objPmReq->objPmNotify->errorCode != 0)
                {
                    //TODO: de vazut daca e cerere de capture sau doar daca e plata cu autorizare!!!
                //$this->_handlePaymentDenial();
                

                } else
                {
                    $this->_handleCapture();
                }
                break;
            
            case 'confirmed_pending':
                if ($objPmReq->objPmNotify->errorCode != 0)
                {
                    $this->_handlePaymentDenial();
                } else
                {
                    $this->_handleCapturePending();
                }
                break;
            
            case 'paid_pending':
                if ($objPmReq->objPmNotify->errorCode != 0)
                {
                    $this->_handlePaymentDenial();
                } else
                {
                    $this->_handleCapturePending();
                }
                break;
            
            case 'paid':
                if ($objPmReq->objPmNotify->errorCode != 0)
                {
                    $this->_handlePaymentDenial();
                } else
                {
                    
                    $this->_handleAuthorization(0);
                }
                
                break;
            
            case 'canceled':
                if ($objPmReq->objPmNotify->errorCode == 0)
                {
                    
                    $this->_handleCancel();
                }
                break;
            
            case 'credit':
                if ($objPmReq->objPmNotify->errorCode == 0)
                {
                    
                    $this->_handleRefund();
                }
                break;
            
            default:
                $errorType = Mobilpay_Payment_Request_Abstract::CONFIRM_ERROR_TYPE_PERMANENT;
                $errorCode = Mobilpay_Payment_Request_Abstract::ERROR_CONFIRM_INVALID_ACTION;
                $errorMessage = 'mobilpay_refference_action paramaters is invalid';
                break;
        }
        
        return $this->_sendResponse($errorType, $errorCode, $errorMessage);
    
    }

    private function _sendResponse ($errorType, $errorCode, $errorMessage)
    {

        $id = $this->_order->getIncrementId();
        echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
        if ($errorCode == 0)
        {
            echo "<crc order_id=\"{$id}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
        } else
        {
            echo "<crc order_id='{$id}' error_type=\"{$errorType}\" error_code=\"{$errorCode}\">{$errorMessage}</crc>";
        }
        exit();
    }

    protected function _initData ($objPmReq)
    {

        $this->_objPmReq = $objPmReq;
        $order_id = $objPmReq->orderId;
        $this->_order = Mage::getModel('sales/order');
        if (strpos($order_id, "ntpi_") === 0)
        {
            $sess = Mage::getSingleton('checkout/session');
            $sess->setQuoteId($infos['code']);
            $quote = $sess->getQuote();
            $service = Mage::getModel('sales/service_quote', $quote);
            //$this->_order = $this->_buildOrder($objPmReq);
            $service->submitAll();
            $this->_order = $service->getOrder();
            //$objPmReq->orderId = $order->getIncrementId();
        

        } else
        {
            if (strpos($order_id, "ntp_") === 0)
            {
                $this->_order = $this->_buildOrder($objPmReq);
            
            } else
            {
                $this->_order->loadByIncrementId((int) $order_id);
            }
        }
        
        $this->_newOrderStatus = $this->getConfigData(strtolower('order_status_' . $objPmReq->objPmNotify->action));
    
    }

    protected function _createInvoice ($ap)
    {

        if (! $this->_order->canInvoice())
        {
            //when order cannot create invoice, need to have some logic to take care
            $this->_order->addStatusToHistory($this->_order->getStatus(), // keep order status/state
Mage::helper('cc')->__('Error in creating an invoice', true), $notified = true);
        }
        
        $this->_order->getPayment()->setTransactionId($this->_objPmReq->objPmNotify->purchaseId . $ap);
        $this->_order->getPayment()->place();
        $this->_order->save();
    }

    protected function _handleCancel ()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":c");
        $payment->registerVoidNotification();
        $this->_order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
        $this->_order->save();
    }

    protected function _handleAuthorization ($underVerification = true)
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":p");
        $payment->setIsTransactionClosed(0);
        
        if (! $underVerification)
        {
            $payment->setIsTransactionPending(false);
            $this->_createInvoice(":p");
            $payment->registerAuthorizationNotification($this->_objPmReq->objPmNotify->processedAmount);
            $this->_order->setStatus(Mage_Sales_Model_Order::STATE_PROCESSING);
        } else
        {
            $payment->setIsTransactionPending(true);
            $this->_order->setStatus(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW);
            $this->_order->sendNewOrderEmail();
        }
        $this->_order->save();
    }

    protected function _handlePaymentDenial ()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":d");
        //$payment->setNotificationResult(true);
        $payment->setIsTransactionClosed(true);
        $payment->save();
        //$payment->registerPaymentReviewAction(Mage_Sales_Model_Order_Payment::REVIEW_ACTION_DENY, false);
        //$this->_order->setStatus(Mage_Sales_Model_Order::STATE_CANCELED);
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_CANCELED, $this->_objPmReq->objPmNotify->errorMessage);
        $this->_order->save();
    }

    protected function _handleRefund ()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":r");
        $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":c");
        $payment->setIsTransactionClosed(true);
        
        $this->_order->getBaseTotalRefunded(- 1 * $this->_objPmReq->objPmNotify->processedAmount);
        $payment->registerRefundNotification(- 1 * $this->_objPmReq->objPmNotify->processedAmount);
        $this->_order->setTotalRefunded($this->_order->getTotalRefunded() - $this->_objPmReq->objPmNotify->processedAmount);
        
        //$payment->setAmount(- 1 * $this->_objPmReq->objPmNotify->processedAmount);
        /*
         //nu prea functioneaza bine prb legate de magento invoices
        $creditMemo = Mage::getModel('sales/order_creditmemo');
        $creditMemo->setOrder($this->_order);
        
        $creditMemo->setBaseGrandTotal(- 1 * $this->_objPmReq->objPmNotify->processedAmount);
        $creditMemo->register();
        $creditMemo->save();
*/
        
        $this->_order->setStatus(Mage_Sales_Model_Order::STATE_CLOSED);
        $this->_order->save();
    }

    protected function _handleCapturePending ()
    {
        $payment = $this->_order->getPayment();
        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionPending(true);
        $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);
        $this->_order->addStatusToHistory(Mage_Sales_Model_Order::STATE_PAYMENT_REVIEW, "Tranzactie in curs de procesare");
        $this->_order->save();	
    }

    protected function _handleCapture ()
    {

        $payment = $this->_order->getPayment();
        $payment->setPreparedMessage($this->_objPmReq->objPmNotify->errorMessage);
        $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":c");
        $payment->setIsTransactionClosed(0);
        $payment->setIsTransactionPending(false);
        if ($this->_order->getStatus() == Mage_Sales_Model_Order::STATE_PROCESSING)
        {
            $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":p");
            $payment->registerCaptureNotification($this->_objPmReq->objPmNotify->processedAmount);
            $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);
        
        } else
        {
            
            $payment->setAmount($this->_objPmReq->objPmNotify->processedAmount);
            $payment->setTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":f");
            $payment->setParentTransactionId($this->_objPmReq->objPmNotify->purchaseId . ":c");
            $payment->registerCaptureNotification($this->_objPmReq->objPmNotify->processedAmount);
            $this->_order->sendNewOrderEmail(":f");
        
        }
        $this->_order->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
        $this->_order->save();
    }

    private function _buildOrder ($request = null)
    {

        $id = 2; // get Customer Id
        $customer = Mage::getModel('customer/customer')->load($id);
        
        $shippingData = array(
            'is_active' => '1' , 
            'firstname' => $request->objPmNotify->customer->firstName , 
            'lastname' => $request->objPmNotify->customer->lastName , 
            #            'company' => 'myCompany' , 
            'city' => $request->objPmNotify->customer->city , 
            'region' => $request->objPmNotify->customer->county , 
            'postcode' => $request->objPmNotify->customer->zipCode , 
            //'country_id' => 'RO' ,
            'country' => $request->objPmNotify->customer->country , 
            'telephone' => $request->objPmNotify->customer->mobilePhone , 
            'fax' => $request->objPmNotify->customer->email , 
            //'region_id' => '2',
            'street' => $request->objPmNotify->customer->address); //'customer_id' => '2',
        

        $transaction = Mage::getModel('core/resource_transaction');
        $storeId = $customer->getStoreId();
        $reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);
        
        $currency = Mage::app()->getStore($storeId)->getCurrentCurrency()->currency_code;
        
        $order = Mage::getModel('sales/order')->setIncrementId($reservedOrderId)->setStoreId($storeId)->setQuoteId(0)->setGlobal_currency_code($currency)->setBase_currency_code($currency)->setStore_currency_code($currency)->setOrder_currency_code($currency);
        
        $email = $request->objPmNotify->customer->email;
        $fname = $request->objPmNotify->customer->firstName;
        $lname = $request->objPmNotify->customer->lastName;
        
        // set Customer data
        $order->setCustomer_phone($request->objPmNotify->customer->mobilePhone)->setCustomer_email($email)->setCustomerFirstname($fname)->setCustomerLastname($fname)->setCustomer_is_guest(1);
        //->setCustomerGroupId($customer->getGroupId())
        //->setCustomer($customer);
        // set Billing Address
        $billing = $customer->getDefaultBillingAddress();
        $billingAddress = Mage::getModel('sales/order_address');
        $billingAddress->setData($shippingData);
        $order->setBillingAddress($billingAddress);
        
        $shipping = $customer->getDefaultShippingAddress();
        $shippingAddress = Mage::getModel('sales/order_address');
        $shippingAddress->setData($shippingData);
        $order->setShippingAddress($shippingAddress)->setShipping_method('flatrate_flatrate')->setShippingDescription($this->getCarrierName('flatrate'));
        
        $orderPayment = Mage::getModel('sales/order_payment')->setStoreId($storeId)->setCustomerPaymentId($request->objPmNotify->purchaseId)->setMethod($this->getCode())->setPo_number($request->objPmNotify->purchaseId); //$request->objPmNotify->purchaseId
        $order->setPayment($orderPayment);
        
        $subTotal = 0;
        
        $orderItem = Mage::getModel('sales/order_item')->setStoreId($storeId)->setQuoteItemId(0)->setQuoteParentItemId(NULL);
        $orderItem->setQtyBackordered(NULL)->setTotalQtyOrdered(1)->setQtyOrdered(1);
        $orderItem->setName($request->invoice->details); //->setSku($_product->getSku());
        

        $price = $request->objPmNotify->processedAmount;
        
        $orderItem->setPrice($price)->setBasePrice($price)->setOriginalPrice($price);
        $orderItem->setRowTotal($price)->setBaseRowTotal($price);
        
        $order->setSubtotal($price)->setBaseSubtotal($price)->setGrandTotal($price)->setBaseGrandTotal($price);
        
        $order->addItem($orderItem);
        
        $transaction->addObject($order);
        $transaction->addCommitCallback(array(
            $order , 
            'place'));
        $transaction->addCommitCallback(array(
            $order , 
            'save'));
        $transaction->save();
        
        return $order;
    
    }

    function getFormData ()
    {

        return Mage::getSingleton('checkout/session')->getFormData();
    }

    function getFormKey ()
    {

        return Mage::getSingleton('checkout/session')->getFormKey();
    }

    public function canUseInternal ()
    {

        return false;
    }

    public function canUseForMultishipping ()
    {

        return false;
    }

    public function onOrderValidate (Mage_Sales_Model_Order_Payment $payment)
    {

        return $this;
    }

    public function onInvoiceCreate (Mage_Sales_Model_Invoice_Payment $payment)
    {

    }

    public function canCapture ()
    {

        return false;
    }

    public function getSession ()
    {

        return Mage::getSingleton('cc/session');
    }

    public function getCheckout ()
    {

        return Mage::getSingleton('checkout/session');
    }

    public function getQuote ()
    {

        return $this->getCheckout()->getQuote();
    }

}
