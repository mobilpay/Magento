<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\ObjectManager;
use Magento\Sales\Model\Order;
use Magento\Checkout\Model\Session;

class Redirect extends Action {
    /**
     * @var PageFactory
     */
    private $pageFactory;
    protected $_resource;
    protected $_checkoutSession;
    
    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param ResourceConnection $resource
     */

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        ResourceConnection $resource,
        Session $checkoutSession
    )
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->_resource = $resource;
        $this->_checkoutSession = $checkoutSession;
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
        
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order') ->loadByIncrementId($this->_checkoutSession->getLastRealOrderId());
        $orderState = Order::STATE_PENDING_PAYMENT;
        $order->setState($orderState)->setStatus(Order::STATE_PENDING_PAYMENT);
        $order->save();
        
        $page = $this->pageFactory->create();
        return $page;
    }

    public function getRealQuoteId($ntpQuoteId) {
        $expArr = explode('_QT_', $ntpQuoteId);
        return $expArr[0];
    }
}
