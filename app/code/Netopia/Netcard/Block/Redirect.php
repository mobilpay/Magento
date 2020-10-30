<?php
/**
 * Copyright © Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Block;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order;
use Magento\Quote\Model\QuoteFactory;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\App\ObjectManager;
use Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentInvoice;
use Netopia\Netcard\Mobilpay\Payment\Request\MobilpayPaymentRequestCard;
use Netopia\Netcard\Mobilpay\Payment\MobilpayPaymentAddress;
use Magento\Framework\Module\Dir;

/**
 * Class Redirect
 * To handel Redirect from Magento to Sandbox
 * @package Netopia\Netcard\Block
 */
class Redirect extends Template
{
    protected $_storeManager;
    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_orderFactory;
    protected $_resource;
    protected $_moduleReader;
    Protected $quoteFactory;

    /**
     * @var MobilpayPaymentRequestCard
     */
    Protected $mobilpayPaymentRequestCard;
    /**
     * @var MobilpayPaymentInvoice
     */
    Protected $mobilpayPaymentInvoice;
    /**
     * @var Payment\MobilpayPaymentAddress
     */
    Protected $mobilpayPaymentAddress;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param Session $session
     * @param ResourceConnection $resource
     * @param Order $orderFactory
     * @param QuoteFactory $quoteFactory
     * @param Reader $reader
     * @param array $data
     */
    public function __construct(
                                Context $context,
                                Session $session,
                                ResourceConnection $resource,
                                Order $orderFactory,
                                QuoteFactory $quoteFactory,
                                Reader $reader,
                                array $data)
    {
        $this->_resource = $resource;
        $this->_checkoutSession = $session;
        $this->_orderFactory = $orderFactory;
        $this->quoteFactory = $quoteFactory;
        $this->_moduleReader = $reader;
        parent::__construct($context, $data);
    }

    public function getOrder()
    {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $this->_resource->getTableName('sales_order');
        $tblQuoteIdMask = $this->_resource->getTableName('quote_id_mask');
        $quoteId = $this->getRequest()->getParam('quote');

        /** @var ObjectManager $ */
        $obm = ObjectManager::getInstance();

        /** @var \Magento\Framework\App\Http\Context $context */
        $context = $obm->get('Magento\Framework\App\Http\Context');

        // check AUth before Payment
        /** @var bool $isLoggedIn */
        $isLoggedIn = $context->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            $orderId = $connection->fetchAll('SELECT entity_id FROM `'.$tblSalesOrder.'` WHERE quote_id='.$connection->quote($quoteId).' LIMIT 1');

            //return $this->quoteFactory->create()->load($quoteId);
//            $order = $this->_checkoutSession->getLastRealOrder();
//            $orderId=$order->getEntityId();
        } else {
            $orderId = $connection->fetchAll('SELECT `'.$tblSalesOrder.'`.entity_id FROM `'.$tblSalesOrder.'` INNER JOIN `'.$tblQuoteIdMask.'` ON `'.$tblSalesOrder.'`.quote_id=`'.$tblQuoteIdMask.'`.quote_id AND `'.$tblQuoteIdMask.'`.masked_id='.$connection->quote($quoteId));
           // Mage::app()->getFrontController()->getResponse()->setRedirect(Mage::getUrl('customer/account'));
        }
        //print_r($this->_orderFactory->loadByAttribute('entity_id',$orderId));
        return $this->_orderFactory->loadByAttribute('entity_id',$orderId);
    }


    public function getFormData()
    {
        $e = null;
        $moduleDirectory = $this->_moduleReader->getModuleDir(Dir::MODULE_ETC_DIR, 'Netopia_Netcard');
        $filePath = $moduleDirectory . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR;
        $shipping = $this->getOrder()->getShippingAddress();
        $billing = $this->getOrder()->getBillingAddress();
        $order = $this->getOrder();
        $result = [];

        try {
            $objPmReqCard = new MobilpayPaymentRequestCard();
            $objPmReqCard->signature = $this->getConfigData('auth/signature');

            // Get Public Key filename
            $mode = $this->getConfigData('mode/is_live') ? "live." : "sandbox.";
            if($this->getConfigData('mode/is_live')) {
                $livePublicKey = $this->getConfigData('mode/live_public_key');
                if(!is_null($livePublicKey) && file_exists($filePath.$livePublicKey)){
                    $x509FilePath = $filePath.$livePublicKey;
                } else {
                    $x509FilePath = $moduleDirectory . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR . $mode . $objPmReqCard->signature . ".public.cer";
                }
            } else {
                $sandboxPublicKey = $this->getConfigData('mode/sandbox_public_key');
                if(!is_null($sandboxPublicKey) && file_exists($filePath.$sandboxPublicKey)) {
                    $x509FilePath = $filePath.$sandboxPublicKey;
                } else {
                    $x509FilePath = $moduleDirectory . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR . $mode . $objPmReqCard->signature . ".public.cer";
                }
            }


            $objPmReqCard->orderId = $this->getOrder()->getId();
            $objPmReqCard->returnUrl = $this->getUrl('netopia/payment/success');
            $objPmReqCard->confirmUrl = $this->getUrl('netopia/payment/ipn');


            // Add invoice info to Obj
            $objPmReqCard->invoice = new MobilpayPaymentInvoice();

            $objPmReqCard->invoice->currency = $order->getBaseCurrencyCode();
            $objPmReqCard->invoice->amount = $order->getBaseGrandTotal();

            $cart_description = $this->getConfigData('description');
            if ($cart_description != '') {
                $objPmReqCard->invoice->details = $cart_description;
            } else {
                $objPmReqCard->invoice->details = "Netopia - Magento 2 - Default description";
            }

            // Add billing address info to Obj
            $billingAddress = new MobilpayPaymentAddress();
            $company = $billing->getCompany();
            if (!empty($company)) {
                $billingAddress->type = 'company';
            } else {
                $billingAddress->type = 'person';
            }
            $billingAddress->firstName = $billing->getFirstname();
            $billingAddress->lastName = $billing->getLastname();
            $billingAddress->country = $billing->getCountryId();

            $billingAddress->city = $billing->getCity();
            $billingAddress->zipCode = $billing->getPostcode();
            $billingAddress->state = $billing->getRegion();
            $billingAddress->address = implode(', ', $billing->getStreet());
            $billingAddress->email = $billing->getEmail();
            $billingAddress->mobilePhone = $billing->getTelephone();

            $objPmReqCard->invoice->setBillingAddress($billingAddress);

            $objPmReqCard->encrypt($x509FilePath);
        } catch (\Exception $e) {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }

        if (!($e instanceof \Exception)) {
            $result['status'] = 1;
            $result['data'] = $objPmReqCard->getEncData();
            $result['form_key'] = $objPmReqCard->getEnvKey();
            $result['billing'] = $billing->getData();

        } else {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    public function getConfigData($field)
    {
        $str = 'payment/net_card/'.$field;
        return $this->_scopeConfig->getValue($str);
    }

    public function setLog($log) {
        $logPoint = date(" - H:i:s - ").rand(1,1000)."\n";
        file_put_contents('/var/www/html/var/log/netopiaLog.log', $log.' <<< Redarect >>> '.$logPoint, FILE_APPEND | LOCK_EX);
    }
}
