/// <reference types="cypress" />

'use strict';

import { PluginTestHelper } from './test_helper.js';

export var TestMethods = {

    /** Admin & frontend user credentials. */
    StoreUrl: (Cypress.env('ENV_ADMIN_URL').match(/^(?:http(?:s?):\/\/)?(?:[^@\n]+@)?(?:www\.)?([^:\/\n?]+)/im))[0],
    AdminUrl: Cypress.env('ENV_ADMIN_URL'),
    RemoteVersionLogUrl: Cypress.env('REMOTE_LOG_URL'),

    /** Construct some variables to be used bellow. */
    ShopName: 'cubecart',
    VendorName: 'Lunar', // with first capital
    CheckoutUrl: '/index.php?_a=checkout',
    PaymentMethodsAdminUrl: '?_g=plugins&type=plugins&module=Lunar_Payments',
    OrdersPageAdminUrl: '?_g=orders',
    PluginsPageAdminUrl: '?_g=plugins',

    /**
     * Login to admin backend account
     */
    loginIntoAdminBackend() {
        cy.loginIntoAccount('input[name=username]', 'input[name=password]', 'admin');
    },
    /**
     * Login to client|user frontend account
     */
    loginIntoClientAccount() {
        cy.loginIntoAccount('input[name=username]', 'input[name=password]', 'client');
    },

    /**
     * Modify plugin settings
     * @param {String} captureMode
     */
    changeCaptureMode(captureMode) {
        /** Go to payment method. */
        cy.goToPage(this.PaymentMethodsAdminUrl);

        /** Select capture mode. */
        cy.selectOptionContaining('select[name="module[capturemode]"]', captureMode)

        cy.get('.form_control > input[name=save]').click();
    },

    /**
     * Make payment with specified currency and process order
     *
     * @param {String} currency
     * @param {String} paymentAction
     * @param {Boolean} partialAmount
     */
     payWithSelectedCurrency(currency, paymentAction, partialAmount = false) {
        /** Make an instant payment. */
        it(`makes a payment with "${currency}"`, () => {
            this.makePaymentFromFrontend(currency);
        });

        /** Process last order from admin panel. */
        it(`process (${paymentAction}) an order from admin panel`, () => {
            this.processOrderFromAdmin(paymentAction, partialAmount);
        });
    },

    /**
     * Make an instant payment
     * @param {String} currency
     */
    makePaymentFromFrontend(currency) {
        /** Go to store frontend. */
        cy.goToPage(this.StoreUrl);

        /** Change currency. */
        this.changeShopCurrency(currency);

        /** Add to cart random product. */
        var randomInt = PluginTestHelper.getRandomInt(/*max*/ 1);
        cy.get('button[value="Add to Basket"]').eq(randomInt).click();

        /** Go to checkout. */
        cy.goToPage(this.StoreUrl + this.CheckoutUrl);

        /** Choose payment method. */
        cy.get(`input[id*=${this.VendorName}]`).click();

        /** Get & Verify amount. */
        cy.get('#content_checkout_medium_up td').contains('Grand Total').next().then($grandTotal => {
            cy.window().then(win => {
                var expectedAmount = PluginTestHelper.filterAndGetAmountInMinor($grandTotal, currency);
                var orderTotalAmount = Number(win.cc_lunar_params.amount);
                expect(expectedAmount).to.eq(orderTotalAmount);
            });
        });

        /** Show payment popup. */
        cy.get('#checkout_proceed').click();

        /**
         * Fill in payment popup.
         */
         PluginTestHelper.fillAndSubmitPopup();

        cy.wait(500);

        cy.get('.alert-box.success').should('contain', 'Payment has been received');
    },

    /**
     * Process last order from admin panel
     * @param {String} paymentAction
     * @param {Boolean} partialAmount
     */
    processOrderFromAdmin(paymentAction, partialAmount = false) {
        /** Go to admin orders page. */
        cy.goToPage(this.OrdersPageAdminUrl);

        /** Click on first (latest in time) order from orders table. */
        cy.get('#orders tbody a').first().click();

        /**
         * Take specific action on order
         */
        this.paymentActionOnOrderAmount(paymentAction, partialAmount);
    },

    /**
     * Capture an order amount
     * @param {String} paymentAction
     * @param {Boolean} partialAmount
     */
     paymentActionOnOrderAmount(paymentAction, partialAmount = false) {
        switch (paymentAction) {
            case 'capture':
                /** Change order status and submit. */
                cy.selectOptionContaining('select[name="order[status]"]', 'Order Complete');
                break;
            case 'refund':
                /** Select refund tab. */
                cy.get('#tab_lunar_refund').click();
                /** Refund transaction. */
                cy.get('img[rel="#confirm_lunar_refund"]').click();
                break;
            case 'void':
                /** Select void tab. */
                cy.get('#tab_lunar_void').click();
                /** Void transaction. */
                cy.get('img[rel="#confirm_lunar_void"]').click();
                break;
        }

        /** Trigger selected action. */
        cy.get('input[value=Save]').click();

        /** Check if success message. */
        cy.get('#gui_message .success:nth-child(2)').should('be.visible');
    },

    /**
     * Change shop currency in frontend
     */
    changeShopCurrency(currency) {
        cy.get('a[data-dropdown=currency-switch]').then($dropDownCurrencyButton => {
            var currencyAlreadySelected = $dropDownCurrencyButton.text().includes(currency);
            if (!currencyAlreadySelected) {
                $dropDownCurrencyButton.trigger('click');
                cy.get('#currency-switch a').contains(currency).click();
            }
        });
    },

    /**
     * Get Shop & plugin versions and send log data.
     */
    logVersions() {
        /** From admin dashboard click on "Store Overview" tab. */
        cy.get('a[href="#advanced"]').click();

        /** Get framework version. */
        cy.get('dt').contains('CubeCart').closest('dl').then($frameworkVersion => {
            var frameworkVersion = $frameworkVersion.children('dd:nth-child(2)').text();
            cy.wrap(frameworkVersion).as('frameworkVersion');
        });

        /** Go to plugins/modules page. */
        cy.goToPage(this.PluginsPageAdminUrl);

        /** Get plugin version. */
        cy.get(`input[id*=${this.VendorName}]`).closest('tr').then($pluginVersion => {
            var pluginVersion = $pluginVersion.children('td:nth-child(3)').text();
            cy.wrap(pluginVersion).as('pluginVersion');
        });

        /** Get global variables and make log data request to remote url. */
        cy.get('@frameworkVersion').then(frameworkVersion => {
            cy.get('@pluginVersion').then(pluginVersion => {

                cy.request('GET', this.RemoteVersionLogUrl, {
                    key: frameworkVersion,
                    tag: this.ShopName,
                    view: 'html',
                    // framework: frameworkVersion,
                    ecommerce: frameworkVersion,
                    plugin: pluginVersion
                }).then((resp) => {
                    expect(resp.status).to.eq(200);
                });
            });
        });
    },


    /**
     * TEMPORARY ADDED BEGIN
     */
     enableThisModuleDisableOther() {
        cy.goToPage(this.PluginsPageAdminUrl);
        cy.get('img[rel="#status_Lunar_Payments"]').click();
        cy.get('img[rel="#status_Paylike_Payments"]').click();
        cy.get(':nth-child(5) > .form_control > input').click();
        cy.get('#clear_cache_master').click();
    },
    disableThisModuleEnableOther() {
        cy.goToPage(this.PluginsPageAdminUrl);
        cy.get('img[rel="#status_Lunar_Payments"]').click();
        cy.get('img[rel="#status_Paylike_Payments"]').click();
        cy.get(':nth-child(5) > .form_control > input').click();
        cy.get('#clear_cache_master').click();
    },
    /**
     * TEMPORARY ADDED END
     */

}