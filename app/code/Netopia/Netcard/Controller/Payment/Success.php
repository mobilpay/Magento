<?php
namespace Netopia\Netcard\Controller\Payment;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;

class Success extends Action
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * Index constructor.
     * @param Context $context
     * @param PageFactory $pageFactory
     */

    public function __construct(
        Context $context,
        PageFactory $pageFactory
    )
    {
        parent::__construct($context);
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
           $page = $this->pageFactory->create();
           return $page;
        } else {
            // Temporary Show Detaile 
             $page = $this->pageFactory->create();
             return $page;
            // $objectManager =  \Magento\Framework\App\ObjectManager::getInstance();
            // $orderData = $objectManager->create('Magento\Sales\Model\Order')->loadByIncrementId($_GET['orderId']);

            // if($orderData->getCustomerIsGuest()){
            //     echo "Is Guest";
            //     // $page = $this->pageFactory->create();
            //     // return $page;
            // } else {
            //     echo "este lup";
            //     // $resultRedirect = $this->resultRedirectFactory->create();
            //     // $resultRedirect->setPath(''); // set this path to what you want your customer to go
            //     // return $resultRedirect;
            // }
        }        
    }
}
