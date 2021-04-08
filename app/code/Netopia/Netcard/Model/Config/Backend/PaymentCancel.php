<?php

namespace Netopia\Netcard\Model\Config\Backend;

class PaymentCancel implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
    return [
        ['value' => '', 'label' => __('Magento Default')],
        ['value' => 'STATE_CANCELED', 'label' => __('Canceled')],
        ['value' => 'STATE_CLOSED', 'label' => __('Closed')],
        ['value' => 'STATE_HOLDED', 'label' => __('On Hold')]
    ];
    }
}