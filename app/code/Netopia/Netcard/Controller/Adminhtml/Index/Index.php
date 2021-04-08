<?php
namespace Netopia\Netcard\Controller\Adminhtml\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\Action\Action;

class Index extends Action
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
        $domain = "https://netopia-payments.com";
        $stream = stream_context_create (array("ssl" => array("capture_peer_cert" => true)));
        $read = fopen($domain, "rb", false, $stream);
        $cont = stream_context_get_params($read);
        $var = ($cont["options"]["ssl"]["peer_certificate"]);
        echo $result = (!is_null($var)) ? true : false;
        echo "<hr> This is Controller from Admin - Test";     
    }
}
