<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;
use Magento\Sales\Model\Order;
// use Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface;

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
    // protected $_builderInterface;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Order $orderFactory
    //  * @param BuilderInterface $builderInterface
     */

    public function __construct(
        \Magento\Framework\App\Http\Context $httpContext,
        Context $context,
        PageFactory $resultPageFactory,
        // BuilderInterface $builderInterface,
        Order $orderFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->_orderFactory = $orderFactory;
        $this->httpContext = $httpContext;
        // $this->_builderInterface = $builderInterface;
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
        
        /**
        * Get real Order ID
        */
        // $orderId = $this->_request->getParam('orderId');
        $orderId = $this->getRealOrderId($this->_request->getParam('orderId'));
        
        $order = $this->_orderFactory->load($orderId);


        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if($customerSession->isLoggedIn()) {
            if($customerSession->getCustomer()->getEmail() != $order->getCustomerEmail()){
                $msg = _('Eroare, accesul nu este permis');
                $this->messageManager->addError($msg);
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }

            if(!$order || ($order->getStatus()== NULL) ){
                return $this->resultRedirectFactory->create()->setPath('checkout/cart');
            }else{
                if($order->getStatus()==Order::STATE_PENDING_PAYMENT) {
                    $msg = 'The order is not paid ';
                    $msg .= " | ".current($order->getAllStatusHistory())->getComment();
                    $this->messageManager->addError($msg);
                    /**
                     * Reload cart 
                     */
                    $_checkoutSession = $objectManager->create('\Magento\Checkout\Model\Session');
                    $_quoteFactory = $objectManager->create('\Magento\Quote\Model\QuoteFactory');

                    $quote = $_quoteFactory->create()->loadByIdWithoutStore($order->getQuoteId());
                    if ($quote->getId()) {
                        $quote->setIsActive(true)->setReservedOrderId(null)->save();
                        // $quote->setIsActive(true)->save();
                        // $_checkoutSession->replaceQuote($quote);

                        $payment = $order->getPayment();
                        $payment->setPreparedMessage('Plata respinsa, clientul s-a intors in site');
                        $payment->setIsTransactionDenied(true);
                        $payment->getAdditionalInformation();
                        $payment->registerVoidNotification();
                        
                        $order->setStatus(Order::STATE_CANCELED); // Order status set as cancel & will generate new Cart,..
                        $order->save();
                    }

                    return $this->resultRedirectFactory->create()->setPath('checkout/cart');
                }elseif($order->getStatus()==Order::STATE_CANCELED || $order->getStatus()==Order::STATE_PAYMENT_REVIEW){
                    // $msg = current($order->getAllStatusHistory())->getComment();
                    // $this->messageManager->addError($msg);
                    // return $this->resultRedirectFactory->create()->setPath('checkout/cart');
                }
            }

        }else{
            /**
             * If simply want to redirect Guest user to checkout/onepage/success
             * As is defulte in Magento
             */
            // return $this->resultRedirectFactory->create()->setPath('checkout/onepage/success');
            
            /**
             * Netopia Custom page for Guest
             */
            $cryptedMail = md5($order->getCustomerEmail());
            return $this->resultRedirectFactory->create()->setPath('netopia/payment/guest?&code='.$cryptedMail.'&orderId='.$order->getEntityId());
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

    public function getRealOrderId($ntpTransactionId) {
        $expArr = explode('_T_', $ntpTransactionId);
        return $expArr[0];
    }
}
