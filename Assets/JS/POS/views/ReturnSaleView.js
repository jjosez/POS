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

        this.updateConfirmButtonLabel();
    },

    updateConfirmButtonLabel() {
        const btn = elements.confirmBtn;
        if (!btn) return;

        if (AppSettings.aceptapagos) {
            btn.innerHTML = '<i class="fa-solid fa-credit-card mr-1"></i> Cobrar devoluci\u00f3n';
        } else {
            btn.innerHTML = '<i class="fa-solid fa-floppy-disk mr-1"></i> Guardar borrador';
        }
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

    renderProducts(lines, alreadyRefunded) {
        const items = Array.isArray(lines) && lines.length > 0 ? lines : [];

        if (alreadyRefunded) {
            templates.render('returnSaleAlreadyRefundedTemplate', {}, 'returnSaleProductsView');
            if (elements.productsBadge) {
                elements.productsBadge.textContent = '0';
            }
            return;
        }

        templates.render('returnSaleProductsTemplate', {lines: items}, 'returnSaleProductsView');
        if (elements.productsBadge) {
            elements.productsBadge.textContent = items.length;
        }
    },

    renderProductsWithPreselect(lines) {
        const items = Array.isArray(lines) && lines.length > 0 ? lines : [];
        templates.render('returnSaleProductsTemplate', {lines: items}, 'returnSaleProductsView');
        if (elements.productsBadge) {
            elements.productsBadge.textContent = items.length;
        }

        items.forEach(line => {
            if (line._preselected) {
                const checkbox = document.querySelector(`.return-product-check[value="${line.idlinea}"]`);
                if (checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change', {bubbles: true}));
                }

                const input = document.querySelector(`.return-qty-input[data-line="${line.idlinea}"]`);
                if (input && line._preselected_qty) {
                    input.value = line._preselected_qty;
                    input.dispatchEvent(new Event('input', {bubbles: true}));
                }
            }
        });
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
