<?php

namespace Netopia\Netcard\Model\Config\Backend;

class PaymentFraud implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
    return [
        ['value' => '', 'label' => __('Magento Default')],
        ['value' => 'STATE_PAYMENT_REVIEW', 'label' => __('Payment review')],
        ['value' => 'STATE_PROCESSING', 'label' => __('Processing')],
        ['value' => 'STATE_CLOSED', 'label' => __('Closed')],
        ['value' => 'STATE_HOLDED', 'label' => __('On Hold')]
    ];
    }
}