<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mobilpay\Credit\Model;



/**
 * Pay In Store payment method model
 */
class Cardcc extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'cardcc';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;


  public function getOrderPlaceRedirectUrl(){                  
      return 'mobilpaycredit/cc/redirect';
  }

}
