<?php

namespace Netopia\Netcard\Model\Config\Backend;

class PaymentComplit implements \Magento\Framework\Data\OptionSourceInterface
{
    public function toOptionArray()
    {
    return [
        ['value' => '', 'label' => __('Magento Default')],
        ['value' => 'STATE_PROCESSING', 'label' => __('Processing')],
        ['value' => 'STATE_COMPLETE', 'label' => __('Complete')],
        ['value' => 'STATE_CLOSED', 'label' => __('Closed')]
    ];
    }
}