import Modals from '../components/Modals.js';
import templates from './TemplateManger.js';

const MODAL_ID = 'return:sale:experimental:modal';

const elements = {
    root: document.getElementById(MODAL_ID),
    searchInput: document.getElementById('returnExperimentalSearchInput'),
    localFilter: document.getElementById('returnExperimentalLocalFilter'),
    filterWrap: document.getElementById('returnExperimentalFilterWrap'),
    orderSummary: document.getElementById('returnExperimentalOrderSummary'),
    customerInitial: document.getElementById('returnExperimentalCustomerInitial'),
    orderCode: document.getElementById('returnExperimentalOrderCode'),
    orderDate: document.getElementById('returnExperimentalOrderDate'),
    customerName: document.getElementById('returnExperimentalCustomerName'),
    documentTotal: document.getElementById('returnExperimentalDocumentTotal'),
    selectedCount: document.getElementById('returnExperimentalSelectedCount'),
    selectedUnits: document.getElementById('returnExperimentalSelectedUnits'),
    total: document.getElementById('returnExperimentalTotal'),
    confirm: document.getElementById('returnExperimentalConfirm'),
    clearSelection: document.getElementById('returnExperimentalClearSelection'),
};

const configuredDecimals = Number.parseInt(AppSettings.currency?.decimals, 10);
const decimals = Number.isInteger(configuredDecimals) ? configuredDecimals : 2;

const ReturnSaleExperimentalView = {
    state: null,

    init() {
        elements.localFilter?.addEventListener('input', () => this.render(this.state));
    },

    isVisible() {
        return Boolean(elements.root && !elements.root.classList.contains('hidden'));
    },

    show() {
        Modals.showModal(MODAL_ID);
        this.updateConfirmButtonLabel();
    },

    hide() {
        Modals.hideModal(MODAL_ID);
    },

    getSearchTerm() {
        return elements.searchInput?.value?.trim() || '';
    },

    focusSearch() {
        setTimeout(() => elements.searchInput?.focus(), 100);
    },

    updateConfirmButtonLabel() {
        const label = elements.confirm?.querySelector('span');
        const icon = elements.confirm?.querySelector('i');
        if (!label || !icon) return;

        if (AppSettings.aceptapagos) {
            icon.className = 'fa-solid fa-credit-card mr-2';
        } else {
            icon.className = 'fa-solid fa-floppy-disk mr-2';
        }
    },

    render(state) {
        if (!state) return;
        this.state = state;

        const doc = state.data?.doc || null;
        const filter = elements.localFilter?.value?.trim().toLocaleLowerCase() || '';
        const visibleLines = (state.lines || []).filter(line => {
            if ((Number.parseFloat(line.refundable) || 0) <= 0) return false;
            if (!filter) return true;
            return `${line.referencia || ''} ${line.descripcion || ''}`.toLocaleLowerCase().includes(filter);
        }).map(line => {
            const selected = state.cartLines.find(item => String(item.idlinea) === String(line.idlinea));
            return {
                ...line,
                cantidad: Number.parseFloat(line.cantidad) || 0,
                refunded: Number.parseFloat(line.refunded) || 0,
                refundable: Number.parseFloat(line.refundable) || 0,
                selected: Boolean(selected),
                selectedQty: Number.parseFloat(selected?.cantidad) || 0,
                disableDecrease: (Number.parseFloat(selected?.cantidad) || 0) <= 0,
                disableIncrease: (Number.parseFloat(selected?.cantidad) || 0) >= (Number.parseFloat(line.refundable) || 0),
            };
        });

        templates.render('returnExperimentalLinesTemplate', {
            loading: Boolean(state.loading),
            error: state.error || '',
            hasOrder: Boolean(doc),
            lines: visibleLines,
        }, 'returnExperimentalLinesView');

        elements.orderSummary?.classList.toggle('hidden', !doc);
        elements.orderSummary?.classList.toggle('flex', Boolean(doc));
        elements.filterWrap?.classList.toggle('hidden', !doc);

        if (doc) {
            const customer = doc.nombrecliente || doc.codcliente || '-';
            if (elements.customerInitial) elements.customerInitial.textContent = customer.charAt(0).toUpperCase();
            if (elements.orderCode) elements.orderCode.textContent = doc.codigo || '-';
            if (elements.orderDate) elements.orderDate.textContent = `${doc.fecha || ''} ${doc.hora || ''}`.trim();
            if (elements.customerName) elements.customerName.textContent = customer;
            if (elements.documentTotal) elements.documentTotal.textContent = (Number.parseFloat(doc.total) || 0).toFixed(decimals);
        }

        if (elements.selectedCount) elements.selectedCount.textContent = String(state.cartLines.length);
        if (elements.selectedUnits) elements.selectedUnits.textContent = String(state.selectedUnits || 0);
        if (elements.total) elements.total.textContent = (Number.parseFloat(state.total) || 0).toFixed(decimals);
        if (elements.confirm) elements.confirm.disabled = !state.cartLines.length || state.total <= 0 || state.loading || state.quoting;
        if (elements.clearSelection) elements.clearSelection.disabled = !state.cartLines.length;
    },

    reset() {
        this.state = null;
        if (elements.searchInput) elements.searchInput.value = '';
        if (elements.localFilter) elements.localFilter.value = '';
        this.render({data: null, lines: [], cartLines: [], selectedUnits: 0, total: 0});
    },
};

ReturnSaleExperimentalView.init();

export default ReturnSaleExperimentalView;
