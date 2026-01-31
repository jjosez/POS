/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';
import * as Core from '../Core.js';
import CartController from './CartController.js';
import CheckoutController from './CheckoutController.js';
import MainView from '../views/MainView.js';

const OrderController = {
    /**
     * Eliminar un pedido en pausa / borrador
     * @param {string} code
     */
    async deleteDraftOrder(code) {
        const data = new FormData();

        data.set('action', 'order:draft:delete');
        data.set('code', code);

        return Core.postRequest(data);
    },

    /**
     * Reanudar un pedido en pausa
     * @param {string} code
     */
    async resumeOrder(code) {
        const data = new FormData();

        data.set('action', 'order:draft:resume');
        data.set('code', code);

        return Core.postRequest(data);
    },

    /**
     * Guardar pedido definitivo
     * @param {{doc: object, lines: array, token: string}} state
     * @param {array} payments
     */
    async saveRequest(state, payments) {
        const payload = {
            ...state.doc,
            lines: state.lines,
            payments
        };

        const resource = `POS?action=order:save&token=${state.token}`;
        return this.postJsonRequest(resource, payload);
    },

    /**
     * Guardar pedido en borrador / pausa
     * @param {{doc: object, lines: array, token: string}} state
     */
    async saveDraftRequest(state) {
        const payload = {
            ...state.doc,
            lines: state.lines,
            draft: true,
            token: state.token
        };

        const resource = `POS?action=order:draft:save&token=${state.token}`;
        return this.postJsonRequest(resource, payload);
    },

    async getDraftOrdersRequest() {
        const data = new FormData();

        data.set('action', 'order:draft:list');

        return Core.postRequest(data);
    },

    async getLastOrders() {
        const data = new FormData();

        data.set('action', 'order:last:list');

        return Core.postRequest(data);
    },

    async getOrder({order}) {
        const data = new FormData();

        data.set('action', 'order:refund:get');
        data.set('code', order || '');

        return Core.postRequest(data);
    },

    async recalculateRequest({doc, lines}) {
        const payload = {
            ...doc,
            lines
        };

        const resource = 'POS?action=order:recalculate';
        return this.postJsonRequest(resource, payload);
    },

    /**
     * Base POST JSON
     * @param {string} resource
     * @param {Object} payload
     */
    async postJsonRequest(resource, payload = {}) {
        try {
            const response = await fetch(resource, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) {
                return {
                    status: 'error',
                    error: `HTTP ${response.status}`
                };
            }

            const result = await response.json();

            if (result.messages && result.messages.length) {
                EventManager.emit('responseMessages', result.messages);
            }

            return result;
        } catch (e) {
            console.warn(`❌ Error al ejecutar la consulta. ${e.message}`);
            return {
                status: 'error',
                error: e.message,
                data: {}
            };
        }
    },

    /**
     * data-action="order:draft:delete"
     */
    async handleDraftOrderDeleteAction(el) {
        const {code} = el.dataset;

        await this.deleteDraftOrder(code);

        if (CartController.isDraftOrder(code)) {
            location.reload();
        }

        MainView.toggleDraftOrdersModal();
    },

    /**
     * data-action="order:draft:resume"
     */
    async handleDraftOrderResumeAction(el) {
        const {code} = el.dataset;

        const updatedCart = await this.resumeOrder(code);

        CartController.update(updatedCart);

        EventManager.emit('event:order:resumed', updatedCart.doc);
        MainView.toggleDraftOrdersModal();
    },

    /**
     * event:on="cart:changed"
     */
    async handleOrderRecalculate(cartState) {
        const result = await this.recalculateRequest(cartState);
        EventManager.emit('event:order:recalculated', result);
    },

    /**
     * data-action="order:save"
     */
    async handleOrderSaveAction() {
        if (!CartController.hasLines()) return;

        const result = await this.saveRequest(
            CartController.getState(),
            CheckoutController.getState().payments
        );

        CartController.update(result);

        if (result?.status === 'success') {
            MainView.showPrintOrderContextModal(result.data);
            EventManager.emit('event:order:completed', result);
        }
    },

    /**
     * data-action="order:draft:save"
     */
    async handleDraftOrderSaveAction() {
        if (!CartController.hasLines()) return;

        const result = await this.saveDraftRequest(CartController.getState());

        CartController.update(result);
        EventManager.emit('event:order:completed', result);
    },

    /**
     * data-action="order:draft:list"
     */
    async handleShowDraftOrdersAction() {
        const orders = await this.getDraftOrdersRequest();
        MainView.showPausedOrdersModal(orders);
    },

    /**
     * data-action="order:last:list"
     */
    async handleShowLastOrdersAction() {
        const orders = await this.getLastOrders();
        MainView.showLastOrdersModal(orders);
    },

    /**
     * data-action="order:return:show"
     */
    async handleShowReturnSaleAction(el) {
        const {code, model, order} = el.dataset;

        const result = await this.getOrder({order});
        MainView.showReturnSaleModal(result);
    },

    init() {
        dispatcher.register('order:draft:delete', this.handleDraftOrderDeleteAction.bind(this));
        dispatcher.register('order:draft:resume', this.handleDraftOrderResumeAction.bind(this));
        dispatcher.register('order:save', this.handleOrderSaveAction.bind(this));
        dispatcher.register('order:draft:save', this.handleDraftOrderSaveAction.bind(this));
        dispatcher.register('order:draft:list', this.handleShowDraftOrdersAction.bind(this));
        dispatcher.register('order:last:list', this.handleShowLastOrdersAction.bind(this));
        dispatcher.register('order:return:show', this.handleShowReturnSaleAction.bind(this));

        EventManager.on('cart:changed', this.handleOrderRecalculate.bind(this));
        EventManager.on('keyboard:order:draft:save', this.handleDraftOrderSaveAction.bind(this));
    }
};

export default OrderController;
