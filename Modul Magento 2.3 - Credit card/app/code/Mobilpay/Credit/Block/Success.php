<?php

namespace Mobilpay\Credit\Block;
use Magento\Customer\Model\Context;
use Magento\Sales\Model\Order;

class Success extends \Magento\Framework\View\Element\Template
{
    protected $_orderFactory;
    protected $_orderConfig;
    protected $httpContext;
    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                \Magento\Sales\Model\Order $orderFactory,
                                \Magento\Framework\App\Http\Context $httpContext,
                                \Magento\Sales\Model\Order\Config $orderConfig,
                                array $data)
    {
        $this->_orderFactory = $orderFactory;
        $this->httpContext = $httpContext;
        $this->_orderConfig = $orderConfig;
        parent::__construct($context, $data);
    }

    /**
     * Initialize data and prepare it for output
     *
     * @return string
     */
    protected function _beforeToHtml()
    {
        $this->prepareBlockData();
        return parent::_beforeToHtml();
    }

    /**
     * Prepares block data
     *
     * @return void
     */
    protected function prepareBlockData()
    {
        $order = $this->getOrder();

        $this->addData(
            [
                'is_order_visible' => $this->isVisible($order),
                'view_order_url' => $this->getUrl(
                    'sales/order/view/',
                    ['order_id' => $order->getEntityId()]
                ),
                'print_url' => $this->getUrl(
                    'sales/order/print',
                    ['order_id' => $order->getEntityId()]
                ),
                'can_print_order' => $this->isVisible($order),
                'can_view_order'  => $this->canViewOrder($order),
                'order_id'  => $order->getIncrementId()
            ]
        );
    }

    /**
     * Can view order
     *
     * @param Order $order
     * @return bool
     */
    protected function canViewOrder(Order $order)
    {
        return $this->httpContext->getValue(Context::CONTEXT_AUTH)
        && $this->isVisible($order);
    }

    /**
     * Is order visible
     *
     * @param Order $order
     * @return bool
     */
    protected function isVisible(Order $order)
    {
        return !in_array(
            $order->getStatus(),
            $this->_orderConfig->getInvisibleOnFrontStatuses()
        );
    }
    
    public function getOrder(){
        $orderId = $this->getRequest()->getParam('orderId');
        return $this->_orderFactory->load($orderId);
    }


}