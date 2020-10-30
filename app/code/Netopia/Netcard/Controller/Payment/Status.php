<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Result\PageFactory;

class Status extends Action {
    /**
     * @var PageFactory
     */
    private $pageFactory;
    protected $orderRepository;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     */

    public function __construct(
        Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        PageFactory $pageFactory
    )
    {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->pageFactory = $pageFactory;
    }

    /**
     * Execute action based on request and return result
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        if($customerSession->isLoggedIn()) {
            $orderId = $_GET['id']; //Order Id
            $order = $this->orderRepository->get($orderId);
            $status = $order->getStatus();
            echo $status;
        }else{
            echo "access_denied";
        }    
    }
}
