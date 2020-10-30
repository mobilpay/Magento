/*jshint jquery:true*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        return function (messageContainer) {
            if($('#qrcodePayment').is(':checked')) 
                { 
                    console.log('qrcode is checked');
                    $.mage.redirect('../netopia/payment/qrcode/quote/' + quote.getQuoteId()); // Redirect to QrCode

                } else if($('#cardPayment').is(':checked')) 
                {
                    console.log('cardPayment is checked');
                    $.mage.redirect('../netopia/payment/redirect/quote/' + quote.getQuoteId()); // Redirect to SANDBOX 
                } else
                {
                    console.log('Someother Method is selected as defulte');
                }         
            
        };
    }
);
