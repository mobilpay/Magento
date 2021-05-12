<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Model;

use Netopia\Netcard\Model\Ui\ConfigProvider;

class Netcard extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = ConfigProvider::CODE;

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    public function getOrderPlaceRedirectUrl(){
        // return 'netopia/payment/redirect';
        return 'netopia/payment/qrcode';
    }
}
