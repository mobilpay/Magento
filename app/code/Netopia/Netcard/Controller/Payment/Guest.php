<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;

class Guest extends Action
{
    /**
     * @var PageFactory
     */
    // private $pageFactory;
    protected $resultPageFactory;
    protected $_orderFactory;
    protected $_messageManager;
    protected $httpContext;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Order $orderFactory
     */

    public function __construct(
        \Magento\Framework\App\Http\Context $httpContext,
        Context $context,
        PageFactory $resultPageFactory,
        Order $orderFactory

    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_orderFactory = $orderFactory;
        $this->httpContext = $httpContext;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        
        $resultPage = $this->resultPageFactory->create();
        $orderId = $this->_request->getParam('orderId');
        $code = $this->_request->getParam('code');
        $order = $this->_orderFactory->load($orderId);


        if(md5($order->getCustomerEmail()) != $code){
            $msg = 'Oops, access denied';
            $this->messageManager->addError($msg);
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }

        if(!$order->getCustomerIsGuest()){
            $msg = 'You already have account. To get any information, first loggin to your account';
            $this->messageManager->addError($msg);
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if($customerSession->isLoggedIn()) {
            return $this->resultRedirectFactory->create()->setPath('netopia/payment/success/?&orderId='.$orderId);
        }
   

        if ($order->getCanSendNewEmailFlag()) { 
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $emailSender = $objectManager->create('\Magento\Sales\Model\Order\Email\Sender\OrderSender');
            $emailSender->send($order);
        }

        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order_ids' => [$orderId]]
        );
        
        return $resultPage;
    }
}
