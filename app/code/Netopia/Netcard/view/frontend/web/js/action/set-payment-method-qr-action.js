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
            // $.mage.redirect('http://www.mdir.eu'); //My Url, regarding Route
            //$.mage.redirect('../netopia/payment/redirect/quote/' + quote.getQuoteId()); //My Url, regarding Route
            alert('jjjjj');
        };
    }
);
