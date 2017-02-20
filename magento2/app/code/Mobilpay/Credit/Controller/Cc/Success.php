<?php
namespace Mobilpay\Credit\Controller\Cc;
use Magento\Framework\App\Action\Context;
use Magento\Sales\Model\Order;

class Success extends \Magento\Framework\App\Action\Action
{
    protected $resultPateFactory;
    protected $_orderFactory;
    protected $_messageManager;
    public function __construct(Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Sales\Model\Order $orderFactory
    )
    {
        $this->_orderFactory = $orderFactory;
        $this->resultPateFactory = $resultPageFactory;
        parent::__construct($context);
    }
    /**
     * Order success action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPateFactory->create();
        $orderId = $this->_request->getParam('orderId');
        $order = $this->_orderFactory->load($orderId);
        if(!$order){
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }else{
            if($order->getStatus()==Order::STATE_CANCELED || $order->getStatus()==Order::STATE_PAYMENT_REVIEW){
                $msg = current($order->getAllStatusHistory())->getComment();
                $this->_messageManager->addError($msg);
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }
        }

        $this->_eventManager->dispatch(
            'checkout_onepage_controller_success_action',
            ['order_ids' => [$orderId]]
        );
        return $resultPage;
        
    }
}
