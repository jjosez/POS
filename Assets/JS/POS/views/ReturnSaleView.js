import templates from './TemplateManger.js';

const elements = {
    fullScreen: document.getElementById('returnFullScreenView'),
    mainLayout: document.getElementById('posMainLayout'),
    summaryCard: document.getElementById('returnSaleSummaryCard'),
    productsView: document.getElementById('returnSaleProductsView'),
    cartView: document.getElementById('returnSaleCartView'),
    searchInput: document.getElementById('returnSearchInput'),
    productsBadge: document.getElementById('returnSaleProductsBadge'),
    cartBadge: document.getElementById('returnSaleCartBadge'),
    confirmBtn: document.getElementById('returnSaleConfirmBtn'),
    totalView: document.getElementById('returnSaleTotalView'),
    subtotal: document.getElementById('returnSaleSubtotal'),
    discount: document.getElementById('returnSaleDiscount'),
    totalAmount: document.getElementById('returnSaleTotalAmount'),
};

const ReturnSaleView = {
    isVisible() {
        return elements.fullScreen && !elements.fullScreen.classList.contains('hidden');
    },

    show() {
        if (!elements.mainLayout || !elements.fullScreen) return;

        elements.mainLayout.classList.add('hidden');
        elements.mainLayout.classList.remove('flex');
        elements.fullScreen.classList.remove('hidden');
        elements.fullScreen.classList.add('flex');
    },

    hide() {
        if (!elements.mainLayout || !elements.fullScreen) return;

        elements.fullScreen.classList.add('hidden');
        elements.fullScreen.classList.remove('flex');
        elements.mainLayout.classList.remove('hidden');
        elements.mainLayout.classList.add('flex');
    },

    renderSearchResult(data) {
        const doc = data?.doc && typeof data.doc === 'object' && !Array.isArray(data.doc) && Object.keys(data.doc).length > 0 ? data.doc : null;
        templates.render('returnSaleSearchResultTemplate', {order: doc}, 'returnSaleSummaryCard');
    },

    renderProducts(lines) {
        const items = Array.isArray(lines) && lines.length > 0 ? lines : [];
        templates.render('returnSaleProductsTemplate', {lines: items}, 'returnSaleProductsView');
        if (elements.productsBadge) {
            elements.productsBadge.textContent = items.length;
        }
    },

    renderCart(cartLines) {
        const items = Array.isArray(cartLines) && cartLines.length > 0 ? cartLines : [];
        templates.render('returnSaleCartTemplate', {items: items}, 'returnSaleCartView');
        if (elements.cartBadge) {
            elements.cartBadge.textContent = items.length;
        }
    },

    updateSummary(refundTotal, docTotal) {
        const totalView = elements.totalView;
        const subtotal = elements.subtotal;
        const discount = elements.discount;
        const totalAmount = elements.totalAmount;
        const confirmBtn = elements.confirmBtn;

        const fmt = (v) => (v || 0).toFixed(2);

        if (totalView) totalView.textContent = fmt(refundTotal);
        if (subtotal) subtotal.textContent = fmt(refundTotal);
        if (discount) discount.textContent = '0.00';
        if (totalAmount) totalAmount.textContent = fmt(refundTotal);

        if (confirmBtn) {
            confirmBtn.disabled = !refundTotal || refundTotal <= 0;
        }
    },

    reset() {
        this.renderSearchResult({doc: null});
        this.renderProducts([]);
        this.renderCart([]);
        this.updateSummary(0, 0);

        if (elements.searchInput) {
            elements.searchInput.value = '';
        }
    },

    focusSearch() {
        if (elements.searchInput) {
            setTimeout(() => elements.searchInput.focus(), 100);
        }
    }
};

export default ReturnSaleView;
