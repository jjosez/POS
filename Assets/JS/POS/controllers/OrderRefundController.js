import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';
import MainView from '../views/MainView.js';
import ReturnSaleView from '../views/ReturnSaleView.js';
import CheckoutController from './CheckoutController.js';
import CheckoutModel from '../models/CheckoutModel.js';
import CartController from './CartController.js';
import * as Core from '../Core.js';

const CHECKOUT_BTN_ID = 'orderSaveButton';
const RETURN_MODAL_ID = 'return:sale:modal';

const OrderRefundController = {
    currentOrder: null,
    currentData: null,
    currentLines: [],
    cartLines: [],
    docTotal: 0,
    quotedTotal: null,
    searchTimer: null,
    requestSequence: 0,
    quoteSequence: 0,
    token: '',
    pendingRefund: null,
    devolucion_id: null,
    isProcessing: false,
    quoteInFlight: false,
    isLoading: false,
    error: '',

    init() {
        dispatcher.register('returns:sale:open:from-list:action', this.openFromList.bind(this));
        dispatcher.register('returns:sale:search:action', this.searchOrder.bind(this));
        dispatcher.register('returns:sale:close:action', this.clear.bind(this));
        dispatcher.register('returns:sale:change:action', this.changeSale.bind(this));
        dispatcher.register('returns:sale:scan:focus:action', () => ReturnSaleView.focusSearch());
        dispatcher.register('returns:sale:confirm:action', this.confirm.bind(this));
        dispatcher.register('returns:cart:clear:action', this.clearCart.bind(this));
        dispatcher.register('returns:draft:resume:action', this.resumeFromDraft.bind(this));

        document.addEventListener('change', (event) => {
            if (event.target.matches('.return-sale-qty-input')) {
                this.handleQuantityInput(event.target);
            }
        });

        document.addEventListener('click', (event) => {
            const minus = event.target.closest('.return-sale-qty-minus');
            const plus = event.target.closest('.return-sale-qty-plus');

            if (minus) this.adjustCartQty(minus.dataset.line, -1);
            if (plus) this.adjustCartQty(plus.dataset.line, 1);

            const cancelBtn = event.target.closest('[data-toggle="modal"][data-target="checkout:modal"]');
            if (cancelBtn && this.pendingRefund && !this.isProcessing) {
                this.cancelPendingRefund(true);
            }
        });

        document.addEventListener('keyup', (event) => {
            if (event.target.matches('#returnSaleSearchInput')) {
                if (event.key === 'Enter') {
                    this.searchOrder();
                } else {
                    this.handleSearchInput(event.target);
                }
            }
        });

        document.addEventListener('scan', (event) => {
            if (ReturnSaleView.isVisible() && !this.pendingRefund && !this.isProcessing) {
                this.searchByBarcode(event.detail.scanCode);
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape' && event.key !== 'Esc') return;

            if (this.pendingRefund && !this.isProcessing) {
                this.cancelPendingRefund(true);
                return;
            }

            this.resetState();
            ReturnSaleView.endSession();
        });

        document.getElementById(RETURN_MODAL_ID)?.addEventListener('pos:modal:hidden', () => {
            if (this.pendingRefund) return;
            setTimeout(() => {
                if (!this.pendingRefund && !ReturnSaleView.isVisible()) {
                    this.resetState();
                    ReturnSaleView.reset();
                    ReturnSaleView.endSession();
                }
            }, 0);
        });
    },

    createCartItem(line, quantity) {
        return {
            idlinea: line.idlinea,
            referencia: line.referencia,
            descripcion: line.descripcion,
            pvpunitario: Number.parseFloat(line.pvpunitario) || 0,
            dtopor: Number.parseFloat(line.dtopor) || 0,
            dtopor2: Number.parseFloat(line.dtopor2) || 0,
            iva: Number.parseFloat(line.iva) || 0,
            recargo: Number.parseFloat(line.recargo) || 0,
            cantidad: quantity,
            maxQty: Number.parseFloat(line.refundable) || 0,
        };
    },

    setLineQuantity(lineId, quantity, render = true) {
        const line = this.currentLines.find(item => String(item.idlinea) === String(lineId));
        if (!line) return;

        const maxQty = Math.max(0, Number.parseFloat(line.refundable) || 0);
        const nextQuantity = Math.max(0, Math.min(maxQty, Number.parseFloat(quantity) || 0));
        const index = this.cartLines.findIndex(item => String(item.idlinea) === String(lineId));

        if (nextQuantity <= 0) {
            if (index >= 0) this.cartLines.splice(index, 1);
        } else if (index >= 0) {
            this.cartLines[index].cantidad = nextQuantity;
            this.cartLines[index].maxQty = maxQty;
        } else {
            this.cartLines.push(this.createCartItem(line, nextQuantity));
        }

        this.quotedTotal = null;
        this.error = '';
        if (render) this.render();
    },

    adjustCartQty(lineId, delta) {
        const item = this.cartLines.find(line => String(line.idlinea) === String(lineId));
        this.setLineQuantity(lineId, (Number.parseFloat(item?.cantidad) || 0) + delta);
    },

    handleQuantityInput(input) {
        this.setLineQuantity(input.dataset.line, input.value);
    },

    clearCart() {
        this.cartLines = [];
        this.quotedTotal = null;
        this.error = '';
        this.render();
    },

    lineTotalWithTax(item) {
        const quantity = Number.parseFloat(item.cantidad) || 0;
        const net = (Number.parseFloat(item.pvpunitario) || 0) * quantity
            * (1 - (Number.parseFloat(item.dtopor) || 0) / 100)
            * (1 - (Number.parseFloat(item.dtopor2) || 0) / 100);
        const iva = net * (Number.parseFloat(item.iva) || 0) / 100;
        const recargo = net * (Number.parseFloat(item.recargo) || 0) / 100;
        return net + iva + recargo;
    },

    getEstimatedTotal() {
        return this.cartLines.reduce((sum, item) => sum + this.lineTotalWithTax(item), 0);
    },

    getDisplayTotal() {
        return this.quotedTotal === null ? this.getEstimatedTotal() : this.quotedTotal;
    },

    getViewState() {
        return {
            data: this.currentData,
            lines: this.currentLines,
            cartLines: this.cartLines,
            alreadyRefunded: Boolean(this.currentData?.already_refunded),
            docTotal: this.docTotal,
            total: this.getDisplayTotal(),
            selectedUnits: this.cartLines.reduce((sum, item) => sum + (Number.parseFloat(item.cantidad) || 0), 0),
            loading: this.isLoading,
            quoting: this.quoteInFlight,
            error: this.error,
        };
    },

    render() {
        ReturnSaleView.render(this.getViewState());
    },

    setLoading(loading) {
        this.isLoading = loading;
        this.render();
    },

    normalizeError(data) {
        const message = data?.message || data?.error || data?.data?.error;
        return typeof message === 'string' && message ? message : 'No se pudo cargar la venta.';
    },

    applyOrderData(data, fallbackOrder = {}, draftId = null) {
        if (!data?.doc || !Array.isArray(data?.lines)) {
            this.currentData = null;
            this.currentOrder = null;
            this.currentLines = [];
            this.cartLines = [];
            this.docTotal = 0;
            this.error = this.normalizeError(data);
            this.render();
            return false;
        }

        this.token = data.token || this.token;
        this.currentData = data;
        this.currentOrder = {
            code: fallbackOrder.code || data.doc.codigo,
            model: fallbackOrder.model || data.doc.modelClassName,
            order: fallbackOrder.order || data.idoperacion || null,
        };
        this.docTotal = Number.parseFloat(data.doc.total) || 0;
        this.currentLines = data.lines;
        this.cartLines = data.lines
            .filter(line => line._preselected && (Number.parseFloat(line._preselected_qty) || 0) > 0)
            .map(line => this.createCartItem(line, Math.min(
                Number.parseFloat(line._preselected_qty) || 0,
                Number.parseFloat(line.refundable) || 0
            )));
        this.devolucion_id = draftId;
        this.quotedTotal = null;
        this.error = '';
        this.render();
        return true;
    },

    async loadOrder(fetchOrder, fallbackOrder = {}, draftId = null) {
        if (this.pendingRefund || this.isProcessing) return false;
        const sequence = ++this.requestSequence;
        this.error = '';
        this.setLoading(true);

        try {
            const data = await fetchOrder();
            if (sequence !== this.requestSequence) return false;
            return this.applyOrderData(data, fallbackOrder, draftId ?? data?.devolucion_id ?? null);
        } catch (error) {
            if (sequence !== this.requestSequence) return false;
            this.error = error?.message || 'No se pudo cargar la venta.';
            this.currentData = null;
            this.currentOrder = null;
            this.currentLines = [];
            this.cartLines = [];
            return false;
        } finally {
            if (sequence === this.requestSequence) {
                this.isLoading = false;
                this.render();
            }
        }
    },

    handleSearchInput(input) {
        if (this.searchTimer) clearTimeout(this.searchTimer);
        this.searchTimer = setTimeout(() => {
            if (input.value.trim()) this.searchOrder();
        }, 250);
    },

    async searchByBarcode(code) {
        if (!code) return;
        ReturnSaleView.clearLocalFilter();
        await this.loadOrder(() => Core.searchOrderForReturn({term: code}));
    },

    async openFromList(element) {
        const fallback = {
            code: element.dataset.code,
            model: element.dataset.model,
            order: element.dataset.order,
        };

        MainView.toggleLastOrdersModal();
        ReturnSaleView.reset();
        ReturnSaleView.show();
        this.resetState(false);
        this.render();
        await this.loadOrder(() => Core.getOrderForReturn(fallback), fallback);
        ReturnSaleView.focusSearch();
    },

    async searchOrder() {
        const term = ReturnSaleView.getSearchTerm();
        if (!term || this.pendingRefund || this.isProcessing) return;

        ReturnSaleView.clearLocalFilter();
        ReturnSaleView.show();
        await this.loadOrder(() => Core.searchOrderForReturn({term}));
        ReturnSaleView.focusSearch();
    },

    changeSale() {
        if (this.pendingRefund || this.isProcessing) return;
        const searchInput = document.getElementById('returnSaleSearchInput');
        this.resetState(false);
        ReturnSaleView.clearLocalFilter();
        if (searchInput) searchInput.value = '';
        this.render();
        ReturnSaleView.focusSearch();
    },

    resetState(invalidateRequests = true) {
        if (invalidateRequests) this.requestSequence++;
        this.quoteSequence++;
        if (this.searchTimer) clearTimeout(this.searchTimer);
        this.searchTimer = null;
        this.currentOrder = null;
        this.currentData = null;
        this.currentLines = [];
        this.cartLines = [];
        this.docTotal = 0;
        this.quotedTotal = null;
        this.token = '';
        this.devolucion_id = null;
        this.isLoading = false;
        this.error = '';
    },

    clear() {
        this.cancelPendingRefund(false);
        this.resetState();
        ReturnSaleView.reset();
        ReturnSaleView.hide();
        ReturnSaleView.endSession();
    },

    cancelPendingRefund(reopen = false) {
        const hadPendingRefund = this.pendingRefund !== null;
        this.quoteSequence++;
        this.pendingRefund = null;
        const button = document.getElementById(CHECKOUT_BTN_ID);
        if (button) button.dataset.action = 'order:save';

        if (hadPendingRefund) {
            const cartTotal = Number.parseFloat(CartController.getState()?.doc?.total) || 0;
            CheckoutModel.updateTotal(cartTotal);
            CheckoutModel.clear();
        }

        if (reopen && hadPendingRefund && this.currentOrder) {
            ReturnSaleView.show();
            this.render();
            ReturnSaleView.focusSearch();
        }
    },

    getSelectedLines() {
        return this.cartLines
            .filter(line => (Number.parseFloat(line.cantidad) || 0) > 0)
            .map(line => ({idlinea: line.idlinea, cantidad: line.cantidad}));
    },

    async quoteRefund(selectedLines, expectedToken, orderId) {
        const formData = new FormData();
        formData.set('action', 'order:refund:quote');
        formData.set('original_code', orderId);
        formData.set('lines', JSON.stringify(selectedLines));
        formData.set('token', expectedToken);

        const result = await Core.postRequest(formData);
        if (result?.token
            && this.token === expectedToken
            && String(this.currentOrder?.order) === String(orderId)) {
            this.token = result.token;
        }
        if (result?.status !== 'success') throw new Error(this.normalizeError(result));

        const total = Number.parseFloat(result?.data?.total ?? result?.total);
        if (!Number.isFinite(total) || total <= 0) throw new Error('El total de la devolución no es válido.');
        return total;
    },

    async confirm() {
        if (this.pendingRefund) {
            await this.processRefundFromCheckout();
            return;
        }

        if (!this.currentOrder || this.isProcessing || this.isLoading || this.quoteInFlight) return;
        const selectedLines = this.getSelectedLines();
        if (!selectedLines.length) return;

        if (!AppSettings.aceptapagos) {
            await this.saveRefundDraft();
            return;
        }

        const quoteSequence = ++this.quoteSequence;
        const orderId = this.currentOrder.order;
        const expectedToken = this.token;
        const pendingQuote = {quoting: true, selectedLines, refundTotal: 0, quoteSequence};
        this.pendingRefund = pendingQuote;
        this.quoteInFlight = true;
        this.error = '';
        this.render();
        MainView.showLoading();

        try {
            const refundTotal = await this.quoteRefund(selectedLines, expectedToken, orderId);
            if (quoteSequence !== this.quoteSequence
                || this.pendingRefund !== pendingQuote
                || String(this.currentOrder?.order) !== String(orderId)) {
                return;
            }
            this.quotedTotal = refundTotal;
            this.pendingRefund = {quoting: false, selectedLines, refundTotal};
            this.render();

            CheckoutModel.updateTotal(refundTotal);
            CheckoutModel.clear();

            const button = document.getElementById(CHECKOUT_BTN_ID);
            if (button) button.dataset.action = 'returns:sale:confirm:action';
            CheckoutController.showCheckoutModal();
        } catch (error) {
            if (quoteSequence !== this.quoteSequence || this.pendingRefund !== pendingQuote) return;
            this.pendingRefund = null;
            this.error = error?.message || 'No se pudo calcular la devolución.';
            this.render();
        } finally {
            this.quoteInFlight = false;
            MainView.hideLoading();
            if (this.currentOrder) this.render();
        }
    },

    async processRefundFromCheckout() {
        if (this.isProcessing || !this.pendingRefund || this.pendingRefund.quoting || !this.currentOrder) return;
        const checkoutState = CheckoutController.getState();
        if (!checkoutState.payments.length) return;

        const payments = checkoutState.payments.map(payment => ({
            method: payment.method,
            amount: -Math.abs(payment.amount),
            change: -Math.abs(payment.change || 0),
            is_cash: payment.is_cash || payment.method === AppSettings.cash,
        }));

        const formData = new FormData();
        formData.set('action', 'order:refund:save');
        formData.set('original_code', this.currentOrder.order);
        formData.set('lines', JSON.stringify(this.pendingRefund.selectedLines));
        formData.set('payments', JSON.stringify(payments));
        formData.set('token', this.token);
        if (this.devolucion_id) formData.set('devolucion_id', this.devolucion_id);

        this.isProcessing = true;
        EventManager.emit('checkout:processing', true);
        MainView.showLoading();

        try {
            const result = await Core.postRequest(formData);
            if (result?.token) this.token = result.token;

            if (result?.status === 'success') {
                this.cancelPendingRefund(false);
                this.resetState();
                ReturnSaleView.reset();
                ReturnSaleView.hide();
                ReturnSaleView.endSession();
                EventManager.emit('event:order:completed', result);
            }
        } finally {
            MainView.hideLoading();
            EventManager.emit('checkout:processing', false);
            this.isProcessing = false;
        }
    },

    async saveRefundDraft() {
        if (!this.currentOrder || this.isProcessing) return;
        const selectedLines = this.getSelectedLines();
        if (!selectedLines.length) return;

        const formData = new FormData();
        formData.set('action', 'order:refund:draft:save');
        formData.set('original_code', this.currentOrder.order);
        formData.set('lines', JSON.stringify(selectedLines));
        formData.set('token', this.token);
        if (this.devolucion_id) formData.set('devolucion_id', this.devolucion_id);

        this.isProcessing = true;
        MainView.showLoading();
        try {
            const result = await Core.postRequest(formData);
            if (result?.token) this.token = result.token;
            if (result?.status === 'success') {
                this.resetState();
                ReturnSaleView.reset();
                ReturnSaleView.hide();
                ReturnSaleView.endSession();
                EventManager.emit('event:order:completed', result);
            }
        } finally {
            MainView.hideLoading();
            this.isProcessing = false;
        }
    },

    async resumeFromDraft(element) {
        const id = element.dataset.id;
        MainView.toggleDraftOrdersModal();
        ReturnSaleView.reset();
        ReturnSaleView.show();
        this.resetState(false);
        this.render();

        const formData = new FormData();
        formData.set('action', 'order:refund:draft:resume');
        formData.set('id', id);
        await this.loadOrder(() => Core.postRequest(formData), {}, id);
        ReturnSaleView.focusSearch();
    },
};

export default OrderRefundController;
