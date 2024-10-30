<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;

class DisableOrderEmail implements ObserverInterface
{
    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $paymentMethod = $order->getPayment()->getMethod();

        // Check if the selected payment method is NETOPIA
        if ($paymentMethod === 'net_card') {
            // Disable the email flag
            $order->setCanSendNewEmailFlag(false);
        }
    }
}