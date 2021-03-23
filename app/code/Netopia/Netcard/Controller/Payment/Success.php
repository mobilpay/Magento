<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;

class Success extends Action
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
        $order = $this->_orderFactory->load($orderId);


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if($customerSession->isLoggedIn()) {
            if($customerSession->getCustomer()->getEmail() != $order->getCustomerEmail()){
                $msg = 'Oops, thee order is not for you';
                $this->messageManager->addError($msg);
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }

            if(!$order || ($order->getStatus()== NULL) ){
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }else{
                if($order->getStatus()==Order::STATE_PENDING_PAYMENT) {
                    $msg = 'The Order is not paied';
                    $this->messageManager->addError($msg);
                    return $this->resultRedirectFactory->create()->setPath('checkout/cart');
                }elseif($order->getStatus()==Order::STATE_CANCELED || $order->getStatus()==Order::STATE_PAYMENT_REVIEW){
                    // $msg = current($order->getAllStatusHistory())->getComment();
                    // $this->messageManager->addError($msg);
                    // return $this->resultRedirectFactory->create()->setPath('checkout/cart');
                }
            }

        }else{
            if($customerSession->getCustomer()->getEmail() != $order->getCustomerEmail()){
                return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
            }
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
