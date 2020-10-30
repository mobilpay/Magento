<?php
namespace Mobilpay\Credit\Controller\Cc;
use Magento\Framework\App\Action\Context;

class Cancel extends \Magento\Framework\App\Action\Action
{
    protected $resultPateFactory;
    protected $_orderFactory;
    protected $_checkoutSession;
    public function __construct(Context $context,
                                \Magento\Framework\View\Result\PageFactory $resultPageFactory,
                                \Magento\Checkout\Model\Session $checkoutSession
    )
    {
        $this->_checkoutSession = $checkoutSession;
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
        if($order = $this->_checkoutSession->getLastRealOrder()){
            $order->cancel()->save();
        }
        return $this->resultRedirectFactory->create()->setPath('checkout/cart');
    }
}
