<?php

namespace Mobilpay\Credit\Block;
use Mobilpay_Payment_Request_Card;
use Mobilpay_Payment_Invoice;
use Mobilpay_Payment_Address;

class Redirect extends \Magento\Framework\View\Element\Template
{
    protected $_storeManager;
    protected $_moduleDirReader;
    protected $_checkoutSession;
    protected $_scopeConfig;
    protected $_orderFactory;
    protected $_resource;

    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                \Magento\Store\Model\StoreManagerInterface $storeManager,
                                \Magento\Checkout\Model\Session $session,
                                \Magento\Framework\Module\Dir\Reader $reader,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Framework\App\ResourceConnection $resource,
                                \Magento\Sales\Model\Order $orderFactory,
                                array $data)
    {
        $this->_resource = $resource;
        $this->_checkoutSession = $session;
        $this->_storeManager = $storeManager;
        $this->_moduleDirReader = $reader;
        $this->_scopeConfig = $scopeConfig;
        $this->_orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }


    public function getOrder()
    {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $connection->getTableName('sales_order');
		$quoteIdMask = $connection->getTableName('quote_id_mask');
        $quoteId = $this->getRequest()->getParam('quote');
		if (is_numeric($quoteId)) {
			$orderId = $connection->fetchAll('SELECT entity_id FROM `'.$tblSalesOrder.'` WHERE quote_id='.$quoteId);
		}
		else {	
			$orderId = $connection->fetchAll('SELECT sales_order.entity_id FROM sales_order INNER JOIN quote_id_mask ON sales_order.quote_id=quote_id_mask.quote_id AND quote_id_mask.masked_id="'.$quoteId.'"');
			
		}
        return $this->_orderFactory->load($orderId);
    }


    public function getFormData()
    {
        $filePath = $this->_moduleDirReader->getModuleDir('etc', 'Mobilpay_Credit');

        $e = null;
        $shipping = $this->getOrder()->getShippingAddress();
        $billing = $this->getOrder()->getBillingAddress();
        $order = $this->getOrder();
        $result = [];
        try {
            $objPmReqCard = new Mobilpay_Payment_Request_Card();

            $objPmReqCard->signature = $this->getConfigData('signature');

            if ($this->getConfigData('debug') == 1) {
                $x509FilePath = $filePath . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR . "sandbox." . $this->getConfigData('signature') . ".public.cer";
            } else {
                $x509FilePath = $filePath . DIRECTORY_SEPARATOR . "certificates" . DIRECTORY_SEPARATOR . "live." . $this->getConfigData('signature') . ".public.cer";
            }
            $objPmReqCard->orderId = $this->getOrder()->getId();

            $objPmReqCard->returnUrl = $this->getUrl('mobilpaycredit/cc/success');
            $objPmReqCard->confirmUrl = $this->getUrl('mobilpaycredit/cc/ipn/');
            $objPmReqCard->cancelUrl = $this->getUrl('mobilpaycredit/cc/cancel');

            $objPmReqCard->invoice = new Mobilpay_Payment_Invoice();

            $objPmReqCard->invoice->currency = $order->getBaseCurrencyCode();
            $objPmReqCard->invoice->amount = $order->getBaseGrandTotal();
            $cart_description = $this->getConfigData('description');
            if ($cart_description != '') $objPmReqCard->invoice->details = $cart_description;

            $billingAddress = new Mobilpay_Payment_Address();

            $company = $billing->getCompany();
            if (!empty($company)) {
                $billingAddress->type = 'company';
            } else {
                $billingAddress->type = 'person';
            }
            $billingAddress->firstName = $billing->getFirstname();
            $billingAddress->lastName = $billing->getLastname();

            //not supported by this shopping cart $billingAddress->fiscalNumber	= $_POST['billing_fiscal_number'];
            //not supported by this shopping cart $billingAddress->identityNumber	= $_POST['billing_identity_number'];


            $billingAddress->country = $billing->getCountryId();

            $billingAddress->city = $billing->getCity();
            $billingAddress->zipCode = $billing->getPostcode();
            $billingAddress->state = $billing->getRegion();
	    $billingAddress->address = implode(', ', $billing->getStreet());
            $billingAddress->email = $billing->getEmail();
            $billingAddress->mobilePhone = $billing->getTelephone();

            //not supported by this shopping cart $billingAddress->bank	= $_POST['billing_bank'];
            //not supported by this shopping cart $billingAddress->iban	= $_POST['billing_iban'];


            $objPmReqCard->invoice->setBillingAddress($billingAddress);

            $shippingAddress = new Mobilpay_Payment_Address();

            $company = $shipping->getCompany();
            if (!empty($company)) {
                $shippingAddress->type = 'company';
            } else {
                $shippingAddress->type = 'person';
            }
            $shippingAddress->firstName = $shipping->getFirstname();
            $shippingAddress->lastName = $shipping->getLastname();

            //not supported by this shopping cart $shippingAddress->fiscalNumber	= $_POST['shipping_fiscal_number'];
            //not supported by this shopping cart $shippingAddress->identityNumber	= $_POST['shipping_identity_number'];


            $shippingAddress->country = $shipping->getCountryId();

            $shippingAddress->city = $shipping->getCity();
            $shippingAddress->zipCode = $shipping->getPostcode();
            $shippingAddress->state = $shipping->getRegion();
		$shippingAddress->address = implode(', ', $shipping->getStreet());
            $shippingAddress->email = $shipping->getEmail();
            $shippingAddress->mobilePhone = $shipping->getTelephone();

            $objPmReqCard->invoice->setShippingAddress($shippingAddress);

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
            $result['shipping'] = $shipping->getData();

        } else {
            $result['status'] = 0;
            $result['message'] = $e->getMessage();
        }
        return $result;
    }

    public function getConfigData($field)
    {

        $path = 'payment/cardcc/' . $field;
        return $this->_scopeConfig->getValue($path);
    }
}
