<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Netopia\Netcard\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Netopia\Netcard\Gateway\Http\Client\ClientMock;

/**
 * Class ConfigProvider
 */
final class ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'net_card';
    protected $scopeConfig;

   /**
   * Netopia QR code path
   */
   const QR_CODE_PATH = 'payment/net_card/api/qr_payment';

   public function __construct(\Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig)
   {
        $this->scopeConfig = $scopeConfig;
   }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'method' => [
                        'card' => __('Card'),
                        'crypto' => __('Crypto')
                    ],
                    'transactionResults' => [
                        ClientMock::SUCCESS => __('Success'),
                        ClientMock::FAILURE => __('Fraud')
                    ],
                    'isQrCode' => $this->getAllowQrCode() ? "willDisplay" : '',
                ]
            ]
        ];
    }

    //Get Qr Permition
    protected function getAllowQrCode()
        {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $isAllowQrCode = $this->scopeConfig->getValue(self::QR_CODE_PATH, $storeScope);
        if($isAllowQrCode)
            return true;
        else
            return false;
        }
}
