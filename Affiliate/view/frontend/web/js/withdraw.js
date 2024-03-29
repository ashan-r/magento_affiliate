/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_Affiliate
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */
define([
    'jquery',
    'validation',
    'prototype'
], function ($) {
    "use strict";

    $.widget('affiliate.withdraw', {
        options: {
            feeConfig: {},
            priceFormatJs: ''
        },

        _create: function () {
            this.initObserve();
        },

        initObserve: function () {
            var self = this;
            var amountEl = this.element.find('#withdraw_amount');
            amountEl.bind('change', function (e) {
                e.stopPropagation();
                self.estimateFee();
            });

            var submitBt = this.element.find('#withdraw-submit-button');
            submitBt.bind('click', function (e) {
                e.stopPropagation();
                self.submitForm();
            });

            var methodEl = this.element.find('#withdraw-payment-method');
            methodEl.bind('change', function (e) {
                e.stopPropagation();
                self.switchMethod();
            });
            this.switchMethod();
        },

        submitForm: function () {
            var form = $('#form-validate');
            if (form.valid()) {
                var container = $('#withdraw-submit-button');
                container.addClass('disabled');
                container.css("opacity", 0.5);
                container.disabled = true;

                $('#withdraw-please-wait').show();

                form.submit();
            }
        },

        estimateFee: function () {
            var feeSelector = $('#withdraw_estimate_fee_container');
            feeSelector.hide();

            var feeConfig = this.options.feeConfig;
            if (Object.keys(feeConfig).length <= 0) {
                return;
            }

            var fee = 0;

            var amount = $('#withdraw_amount').val();
            if (!amount) {
                return;
            }

            var currentMethod = $('#withdraw-payment-method').val();
            if (feeConfig[currentMethod]) {
                var method = feeConfig[currentMethod];
                if (method['type'] == 'percent') {
                    fee = amount * method['value'] / 100;
                } else {
                    fee = method['value'];
                }
            }
            if (fee > 0) {
                fee = formatCurrency(fee, this.options.priceFormatJs);
                $('#withdraw_estimate_fee').text(fee);
                feeSelector.show();
            }
        },
        switchMethod: function () {
            var currentMethod = $('#withdraw-payment-method').val();

            $('.withdraw-payment-method-detail').hide();
            $('#withdraw-payment-method-' + currentMethod).show();

            this.estimateFee();
        }
    });

    return $.affiliate.withdraw;
});
