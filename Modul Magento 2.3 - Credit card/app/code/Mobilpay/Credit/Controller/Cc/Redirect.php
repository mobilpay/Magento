<?php
namespace Mobilpay\Credit\Controller\Cc;

use Magento\Framework\App\Action\Context;

class Redirect extends \Magento\Framework\App\Action\Action {
    protected $resultPateFactory;

    public function __construct(Context $context,
    \Magento\Framework\View\Result\PageFactory $resultPageFactory
)
    {
        $this->resultPateFactory = $resultPageFactory;
        parent::__construct($context);
    }


    public function execute()
    {

        // TODO: Implement execute() method.
        $resultPage = $this->resultPateFactory->create();
        return $resultPage;
    }

}