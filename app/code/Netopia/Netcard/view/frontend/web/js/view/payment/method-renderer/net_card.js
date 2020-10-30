/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Netopia_Netcard/js/action/set-payment-method-action'
    ],
    function (ko, $, Component, setPaymentMethodAction) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Netopia_Netcard/payment/form',
                transactionResult: ''
            },
            afterPlaceOrder: function () {
                // May we can use after paymment is done, ...
                // alert('Palce Order is Pushed');
                setPaymentMethodAction(this.messageContainer);
                console.log('Just Nothing after Order Place');
                return false;
            },
            initObservable: function () {

                this._super()
                    .observe([
                        'transactionResult'
                    ]);
                return this;
            },

            getCode: function() {
                return 'net_card';
            },

            getData: function() {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'transaction_result': this.transactionResult()
                    }
                };
            },

            getTransactionResults: function() {
                return _.map(window.checkoutConfig.payment.net_card.transactionResults, function(value, key) {
                    return {
                        'value': key,
                        'transaction_result': value
                    }
                });
            },

            getQrCode: function() {
                return window.checkoutConfig.payment.net_card.isQrCode;
            }
        });
    }
);
