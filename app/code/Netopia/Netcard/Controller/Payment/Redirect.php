<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;

class Redirect extends Action {
    /**
     * @var PageFactory
     */
    private $pageFactory;
    protected $_resource;
    // protected $_orderFactory;
    
    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
    //  * @param Order $orderFactory
     * @param ResourceConnection $resource
     */

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        // Order $orderFactory,
        ResourceConnection $resource
    )
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->_resource = $resource;
        // $this->_orderFactory = $orderFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        /**
         * Set current order status to Pending
         */
        $orderId = $this->getOrder();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order') ->load($orderId);
        $orderState = Order::STATE_PENDING_PAYMENT;
        $order->setState($orderState)->setStatus(Order::STATE_PENDING_PAYMENT);
        $order->save();
        
        $page = $this->pageFactory->create();
        return $page;
    }

    public function getOrder()
    {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $this->_resource->getTableName('sales_order');
        $tblQuoteIdMask = $this->_resource->getTableName('quote_id_mask');
        $quoteId = $this->getRealQuoteId($this->getRequest()->getParam('quote'));  // Quote Mask ID

        /** @var ObjectManager $ */
        $obm = ObjectManager::getInstance();

        /** @var \Magento\Framework\App\Http\Context $context */
        $context = $obm->get('Magento\Framework\App\Http\Context');

        // check AUth before Payment
        /** @var bool $isLoggedIn */
        $isLoggedIn = $context->getValue(\Magento\Customer\Model\Context::CONTEXT_AUTH);
        if ($isLoggedIn) {
            $orderId = $connection->fetchAll('SELECT entity_id FROM `'.$tblSalesOrder.'` WHERE quote_id='.$connection->quote($quoteId).' ORDER BY `entity_id` DESC LIMIT 1');
            return $orderId[0]['entity_id'];
        } else {
            // Guest Checkout
            $orderId = $this->getOrderGuest($quoteId);
            return $orderId;
        }        
    }

    public function getOrderGuest($quoteMaskId) {
        $connection = $this->_resource->getConnection(\Magento\Framework\App\ResourceConnection::DEFAULT_CONNECTION);
        $tblSalesOrder = $this->_resource->getTableName('sales_order');
        $tblQuoteIdMask = $this->_resource->getTableName('quote_id_mask');

        $getQuoteID = $connection->fetchAll('SELECT quote_id FROM `'.$tblQuoteIdMask.'` WHERE `masked_id`="'.$this->getRealQuoteId($quoteMaskId).'" LIMIT 1');
        $quoteId = $getQuoteID[0]['quote_id'];
        $orderId = $connection->fetchAll('SELECT entity_id FROM `'.$tblSalesOrder.'` WHERE quote_id="'.$quoteId.'" ORDER BY `entity_id` DESC LIMIT 1');
        return $orderId[0]['entity_id'];    
    }

    public function getRealQuoteId($ntpQuoteId) {
        $expArr = explode('_QT_', $ntpQuoteId);
        return $expArr[0];
    }
}
