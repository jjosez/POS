import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';
import MainView from '../views/MainView.js';
import ReturnSaleView from '../views/ReturnSaleView.js';
import * as Core from '../Core.js';

const OrderRefundController = {
    currentOrder: null,
    currentLines: [],
    cartLines: [],
    docTotal: 0,
    searchTimer: null,
    token: '',

    init() {
        dispatcher.register('returns:sale-open-from-list:action', this.openFromList.bind(this));
        dispatcher.register('returns:sale-search:action', this.searchOrder.bind(this));
        dispatcher.register('returns:sale-last:action', this.loadLastOrder.bind(this));
        dispatcher.register('returns:sale-clear:action', this.clear.bind(this));
        dispatcher.register('returns:sale-confirm:action', this.confirm.bind(this));
        dispatcher.register('returns:cart:clear:action', this.clearCart.bind(this));

        document.addEventListener('change', (e) => {
            if (e.target.matches('.return-product-check')) {
                this.handleCheckboxChange(e.target);
            }
        });

        document.addEventListener('click', (e) => {
            if (e.target.closest('.return-qty-minus')) {
                const btn = e.target.closest('.return-qty-minus');
                this.adjustCartQty(btn.dataset.line, -1);
            }
            if (e.target.closest('.return-qty-plus')) {
                const btn = e.target.closest('.return-qty-plus');
                this.adjustCartQty(btn.dataset.line, 1);
            }
            if (e.target.closest('.return-cart-remove')) {
                const btn = e.target.closest('.return-cart-remove');
                this.removeFromCart(btn.dataset.line);
            }
        });

        document.addEventListener('input', (e) => {
            if (e.target.matches('.return-qty-input')) {
                this.handleCartInput(e.target);
            }
        });

        document.addEventListener('keyup', (e) => {
            if (e.target.matches('#returnSearchInput')) {
                this.handleSearchInput(e.target);
            }
        });

        document.addEventListener('scan', (e) => {
            this.searchByBarcode(e.detail.scanCode);
        });
    },

    handleCheckboxChange(checkbox) {
        const lineId = checkbox.value;
        const refundable = parseFloat(checkbox.dataset.refundable) || 0;

        if (checkbox.checked) {
            const line = this.currentLines.find(l => String(l.idlinea) === lineId);
            if (line) {
                this.addToCart({
                    idlinea: line.idlinea,
                    referencia: line.referencia,
                    descripcion: line.descripcion,
                    pvpunitario: parseFloat(line.pvpunitario) || 0,
                    cantidad: Math.min(1, refundable),
                    maxQty: refundable,
                });
            }
        } else {
            this.removeFromCart(lineId);
        }
    },

    addToCart(item) {
        const exists = this.cartLines.find(l => String(l.idlinea) === String(item.idlinea));
        if (exists) return;

        this.cartLines.push(item);
        ReturnSaleView.renderCart(this.cartLines);
        this.updateSummary();
    },

    removeFromCart(lineId) {
        this.cartLines = this.cartLines.filter(l => String(l.idlinea) !== String(lineId));

        const checkbox = document.querySelector(`.return-product-check[value="${lineId}"]`);
        if (checkbox) checkbox.checked = false;

        ReturnSaleView.renderCart(this.cartLines);
        this.updateSummary();
    },

    adjustCartQty(lineId, delta) {
        const item = this.cartLines.find(l => String(l.idlinea) === String(lineId));
        if (!item) return;

        const newQty = Math.max(0, Math.min(item.maxQty, item.cantidad + delta));
        item.cantidad = newQty;

        if (newQty === 0) {
            this.removeFromCart(lineId);
        } else {
            ReturnSaleView.renderCart(this.cartLines);
            this.updateSummary();
        }
    },

    handleCartInput(input) {
        const lineId = input.dataset.line;
        const item = this.cartLines.find(l => String(l.idlinea) === String(lineId));
        if (!item) return;

        let qty = parseInt(input.value) || 0;
        const max = item.maxQty;

        if (qty > max) qty = max;
        if (qty < 0) qty = 0;

        input.value = qty;
        item.cantidad = qty;

        if (qty === 0) {
            this.removeFromCart(lineId);
        } else {
            this.updateSummary();
        }
    },

    clearCart() {
        this.cartLines.forEach(item => {
            const checkbox = document.querySelector(`.return-product-check[value="${item.idlinea}"]`);
            if (checkbox) checkbox.checked = false;
        });
        this.cartLines = [];
        ReturnSaleView.renderCart(this.cartLines);
        this.updateSummary();
    },

    updateSummary() {
        const refundTotal = this.cartLines.reduce((sum, item) => {
            return sum + (item.cantidad * item.pvpunitario);
        }, 0);
        ReturnSaleView.updateSummary(refundTotal, this.docTotal);
    },

    handleSearchInput(input) {
        if (this.searchTimer) {
            clearTimeout(this.searchTimer);
        }

        this.searchTimer = setTimeout(async () => {
            const term = input.value.trim();
            if (!term) return;

        const data = await Core.searchOrderForReturn({term});
        this.token = data?.token || '';
        this.currentOrder = data?.doc ? {code: data.doc.codigo, model: data.doc.modelClassName, order: data?.idoperacion || null} : null;
            this.docTotal = parseFloat(data?.doc?.total) || 0;
            this.currentLines = Array.isArray(data?.lines) ? data.lines : [];
            this.cartLines = [];

            ReturnSaleView.renderSearchResult(data);
            ReturnSaleView.renderProducts(this.currentLines);
            ReturnSaleView.renderCart([]);
            this.updateSummary();
        }, 200);
    },

    async searchByBarcode(code) {
        if (!code) return;

        const data = await Core.searchOrderForReturn({term: code});
        this.token = data?.token || '';
        this.currentOrder = data?.doc ? {code: data.doc.codigo, model: data.doc.modelClassName, order: data?.idoperacion || null} : null;
        this.docTotal = parseFloat(data?.doc?.total) || 0;
        this.currentLines = Array.isArray(data?.lines) ? data.lines : [];
        this.cartLines = [];

        ReturnSaleView.renderSearchResult(data);
        ReturnSaleView.renderProducts(this.currentLines);
        ReturnSaleView.renderCart([]);
        this.updateSummary();
    },

    async openFromList(el) {
        const code = el.dataset.code;
        const model = el.dataset.model;
        const order = el.dataset.order;

        MainView.toggleLastOrdersModal();

        const data = await Core.getOrderForReturn({code, model, order});
        this.token = data?.token || '';
        this.currentOrder = {code, model, order};
        this.docTotal = parseFloat(data?.doc?.total) || 0;
        this.currentLines = Array.isArray(data?.lines) ? data.lines : [];
        this.cartLines = [];

        ReturnSaleView.show();
        ReturnSaleView.renderSearchResult(data);
        ReturnSaleView.renderProducts(this.currentLines);
        ReturnSaleView.renderCart([]);
        this.updateSummary();
        ReturnSaleView.focusSearch();
    },

    async searchOrder(el) {
        const input = document.getElementById('returnSearchInput');
        const term = input?.value?.trim();
        if (!term) return;

        const data = await Core.searchOrderForReturn({term});
        this.token = data?.token || '';
        this.currentOrder = data?.doc ? {code: data.doc.codigo, model: data.doc.modelClassName, order: data?.idoperacion || null} : null;
        this.docTotal = parseFloat(data?.doc?.total) || 0;
        this.currentLines = Array.isArray(data?.lines) ? data.lines : [];
        this.cartLines = [];

        ReturnSaleView.show();
        ReturnSaleView.renderSearchResult(data);
        ReturnSaleView.renderProducts(this.currentLines);
        ReturnSaleView.renderCart([]);
        this.updateSummary();
        ReturnSaleView.focusSearch();
    },

    async loadLastOrder() {
        const orders = await Core.getLastOrderForReturn();

        if (!orders || !orders.length) {
            ReturnSaleView.show();
            ReturnSaleView.renderSearchResult({doc: null});
            ReturnSaleView.renderProducts([]);
            ReturnSaleView.renderCart([]);
            this.updateSummary();
            return;
        }

        const lastOrder = orders[0];
        const data = await Core.getOrderForReturn({
            code: lastOrder.iddocumento,
            model: lastOrder.tipodoc,
            order: lastOrder.idoperacion
        });
        this.token = data?.token || '';

        this.currentOrder = {code: lastOrder.iddocumento, model: lastOrder.tipodoc, order: lastOrder.idoperacion};
        this.docTotal = parseFloat(data?.doc?.total) || 0;
        this.currentLines = Array.isArray(data?.lines) ? data.lines : [];
        this.cartLines = [];

        ReturnSaleView.show();
        ReturnSaleView.renderSearchResult(data);
        ReturnSaleView.renderProducts(this.currentLines);
        ReturnSaleView.renderCart([]);
        this.updateSummary();
        ReturnSaleView.focusSearch();
    },

    clear() {
        this.currentOrder = null;
        this.currentLines = [];
        this.cartLines = [];
        this.docTotal = 0;

        ReturnSaleView.reset();
        ReturnSaleView.hide();
    },

    async confirm() {
        if (!this.currentOrder || this.cartLines.length === 0) return;

        const selectedLines = this.cartLines.map(l => ({
            idlinea: l.idlinea,
            cantidad: l.cantidad,
        }));

        const refundAmount = this.cartLines.reduce((sum, l) =>
            sum + (l.cantidad * l.pvpunitario), 0
        );

        const payments = [{
            method: AppSettings.cash,
            amount: -refundAmount,
            change: 0,
            is_cash: true,
        }];

        const formData = new FormData();
        formData.set('action', 'order:refund:save');
        formData.set('original_code', this.currentOrder.order);
        formData.set('lines', JSON.stringify(selectedLines));
        formData.set('payments', JSON.stringify(payments));
        formData.set('token', this.token);

        const result = await Core.postRequest(formData);

        if (result?.token) {
            this.token = result.token;
        }

        if (result?.status === 'success') {
            this.currentOrder = null;
            this.currentLines = [];
            this.cartLines = [];
            this.docTotal = 0;

            ReturnSaleView.reset();
            ReturnSaleView.hide();
            EventManager.emit('event:order:completed', result);
        }
    }
};

export default OrderRefundController;
