import ReturnSaleView from './ReturnSaleView.js';
import ReturnSaleExperimentalView from './ReturnSaleExperimentalView.js';

const adapters = {
    fullscreen: ReturnSaleView,
    experimental: ReturnSaleExperimentalView,
};

const RefundUIManager = {
    activeName: 'fullscreen',
    experimentalSession: false,

    activate(name = 'fullscreen') {
        this.activeName = adapters[name] ? name : 'fullscreen';
        if (this.activeName === 'experimental') this.experimentalSession = true;
        return this.current();
    },

    current() {
        return adapters[this.activeName];
    },

    isExperimental() {
        return this.activeName === 'experimental';
    },

    isVisible() {
        return this.current().isVisible();
    },

    show() {
        this.current().show();
    },

    hide() {
        this.current().hide();
    },

    focusSearch() {
        this.current().focusSearch();
    },

    getSearchTerm() {
        if (this.isExperimental()) return ReturnSaleExperimentalView.getSearchTerm();
        return document.getElementById('returnSearchInput')?.value?.trim() || '';
    },

    render(state) {
        if (this.isExperimental()) {
            ReturnSaleExperimentalView.render(state);
            return;
        }

        ReturnSaleView.renderSearchResult(state.data || {doc: null});
        ReturnSaleView.renderProducts(state.lines || [], state.alreadyRefunded);
        ReturnSaleView.renderCart(state.cartLines || []);
        ReturnSaleView.updateSummary(state.total || 0, state.docTotal || 0);

        const confirm = document.getElementById('returnSaleConfirmBtn');
        if (confirm && (state.loading || state.quoting)) confirm.disabled = true;

        (state.cartLines || []).forEach(item => {
            const checkbox = document.querySelector(`.return-product-check[value="${item.idlinea}"]`);
            if (checkbox) checkbox.checked = true;
        });
    },

    reset() {
        this.current().reset();
    },

    endSession() {
        this.experimentalSession = false;
        this.activeName = 'fullscreen';
    },

    blocksProductScan() {
        return ReturnSaleView.isVisible() || ReturnSaleExperimentalView.isVisible() || this.experimentalSession;
    },
};

export default RefundUIManager;
