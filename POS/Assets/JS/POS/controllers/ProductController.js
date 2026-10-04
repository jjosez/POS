import * as Core from '../Core.js';
import MainView from '../views/MainView.js';
import ReturnSaleView from '../views/ReturnSaleView.js';
import dispatcher from '../core/EventDispatcher.js';
import {searchFilter} from '../models/FilterModel.js';
import eventManager from '../core/EventManager.js';

let searchTimer;
let requestSeq = 0;
let lastSeq = 0;
let inflightController = null;

function nextSeq() {
    requestSeq += 1;
    return requestSeq;
}

function cancelInflight() {
    if (inflightController) {
        try {
            inflightController.abort();
        } catch (e) {
            // noop
        }
        inflightController = null;
    }
}

function buildFiltersPayload() {
    return {
        families: searchFilter.getFamiliesPayload(),
        codcliente: searchFilter.codcliente
    };
}

const ProductController = {
    /**
     * @param {string} code
     */
    async searchByBarcode(code) {
        const result = await Core.searchRequest('product:barcode:search', code);

        if (result.code) {
            eventManager.emit('event:product:scanned', {
                code: result.code,
                description: result.description,
                thumbnail: result.thumbnail || '',
                bloqueado: result.bloqueado
            });
        }
    },

    async searchByName(el) {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        const input = el;
        searchTimer = setTimeout(async () => {
            await this.runProductSearch(input.value.trim());
        }, 200);
    },

    async runProductSearch(query = '', {skipIfStale = true} = {}) {
        cancelInflight();

        const controller = new AbortController();
        inflightController = controller;

        const seq = nextSeq();
        lastSeq = seq;

        const filters = buildFiltersPayload();
        const results = await Core.searchRequest('product:search', query, filters, {signal: controller.signal});

        if (controller.signal.aborted) return;

        if (results?.aborted) return;

        if (skipIfStale && seq !== lastSeq) return;

        MainView.updateProductFamilyList(searchFilter.filters);
        MainView.updateProductSearchResult(results);

        eventManager.emit('product:search:completed', {
            query,
            filters: buildFiltersPayload(),
            results,
            seq
        });
    },

    async showImages(el) {
        const {id, code} = el.dataset;
        const data = new FormData();

        data.set('action', 'product:images:get');
        data.set('id', id);
        data.set('code', code);

        const images = await Core.postRequest(data);
        MainView.showProductImagesModal(images);
    },

    async showStockDetail(el) {
        const {code} = el.dataset;

        const stock = await Core.searchRequest('product:stock:get', code);
        MainView.showProductStockDetailModal(stock);
    },

    async showDetail(el) {
        const data = new FormData();

        data.set('action', 'product:detail:get');
        data.set('code', el.dataset.code || '');
        data.set('customer', searchFilter.codcliente || '');

        const detail = await Core.postRequest(data);
        MainView.showProductDetailModal(detail);
    },

    selectDetailImage(el) {
        const image = document.getElementById('product:detail:main:image');
        if (!image || !el.dataset.image) return;

        image.src = el.dataset.image;
        document.querySelectorAll('[data-action="product:detail:image:select"]').forEach(button => {
            const selected = button === el;
            button.classList.toggle('ring-blue-500', selected);
            button.classList.toggle('ring-slate-200', !selected);
            button.setAttribute('aria-pressed', selected ? 'true' : 'false');
        });
    },

    /**
     * data-action="product:family:navigate"
     */
    async navigateToFamily(el) {
        const code = el?.dataset?.code || '';

        const result = await Core.searchRequest('family:filter:set', code);

        if (!result || result.aborted) return;

        const mother = result.madre || null;
        const children = (result.children || []).map(child => ({
            ...child,
            isShortcut: child.isShortcut === true,
            hasChildren: child.hasChildren === true,
            selected: searchFilter.hasFamily(child.codfamilia)
        }));

        searchFilter.navigateTo(mother);

        MainView.updateFamilyNavigator({
            mother,
            children,
            breadcrumb: searchFilter.breadcrumb,
            selectedCodes: searchFilter.getSelectedCodes()
        });
    },

    /**
     * data-action="product:family:back"
     */
    async navigateBack(el) {
        searchFilter.navigateBack();
        const code = searchFilter.currentFamily?.codfamilia || '';

        const result = await Core.searchRequest('family:filter:set', code);

        if (!result || result.aborted) return;

        const children = (result.children || []).map(child => ({
            ...child,
            isShortcut: child.isShortcut === true,
            hasChildren: child.hasChildren === true,
            selected: searchFilter.hasFamily(child.codfamilia)
        }));

        MainView.updateFamilyNavigator({
            mother: result.madre || null,
            children,
            breadcrumb: searchFilter.breadcrumb,
            selectedCodes: searchFilter.getSelectedCodes()
        });
    },

    /**
     * data-action="product:filter:family:toggle"
     * Toggles filter membership of a family without navigating.
     */
    toggleFilterFamily(el) {
        const {code, description, thumbnail} = el?.dataset || {};

        if (!code) return;

        if (searchFilter.hasFamily(code)) {
            searchFilter.removeFamily(code);
        } else {
            searchFilter.addFamily({code, description, thumbnail});
        }

        MainView.updateProductFamilyList(searchFilter.filters);
        MainView.updateFamilySelection(searchFilter.getSelectedCodes());

        eventManager.emit('product:filter:changed', buildFiltersPayload());
    },

    /**
     * data-action="product:filter:family:remove"
     * Removes a family filter without affecting navigation.
     */
    removeFilterFamily(el) {
        const {code} = el?.dataset || {};

        if (!code) return;

        searchFilter.removeFamily(code);

        MainView.updateProductFamilyList(searchFilter.filters);
        MainView.updateFamilySelection(searchFilter.getSelectedCodes());

        eventManager.emit('product:filter:changed', buildFiltersPayload());
    },

    /**
     * data-action="product:filter:family:clear"
     */
    clearFilterFamilies() {
        searchFilter.clearFilters();

        MainView.updateProductFamilyList(searchFilter.filters);
        MainView.updateFamilySelection(searchFilter.getSelectedCodes());

        eventManager.emit('product:filter:changed', buildFiltersPayload());
    },

/**
     * event:on="product:filter:changed"
     */
    async handleFilterChanged() {
        const query = MainView.productSearchBox()?.value || '';
        await this.runProductSearch(query);
    },

    handleCustomerChanged({code}) {
        searchFilter.setCustomer(code);

        this.handleFilterChanged();
    },

    syncFamilySelectionOnModalOpen(event) {
        const target = event.target;
        if (!target || target.id !== 'product:filter:modal') return;

        MainView.updateFamilySelection(searchFilter.getSelectedCodes());
    },

    init() {
        searchFilter.setCustomer(AppSettings.customer.codcliente);
        dispatcher.register('product:detail:show', this.showDetail.bind(this));
        dispatcher.register('product:detail:image:select', this.selectDetailImage.bind(this));
        dispatcher.register('product:image:show', this.showImages.bind(this));
        dispatcher.register('product:stock:show', this.showStockDetail.bind(this));
        dispatcher.register('product:family:navigate', this.navigateToFamily.bind(this));
        dispatcher.register('product:family:back', this.navigateBack.bind(this));
        dispatcher.register('product:filter:family:toggle', this.toggleFilterFamily.bind(this));
        dispatcher.register('product:filter:family:remove', this.removeFilterFamily.bind(this));
        dispatcher.register('product:filter:family:clear', this.clearFilterFamilies.bind(this));

        eventManager.on('event:customer:changed', this.handleCustomerChanged.bind(this));
        eventManager.on('event:order:completed', () => this.handleCustomerChanged({
            code: AppSettings.customer.codcliente
        }));
        eventManager.on('product:filter:changed', this.handleFilterChanged.bind(this));

        document.addEventListener('scan', (event) => {
            if (ReturnSaleView.isSessionActive()) return;
            this.searchByBarcode(event.detail.scanCode);
        });

        const searchBox = MainView.productSearchBox();
        if (searchBox) {
            searchBox.addEventListener('keyup', (event) => {
                this.searchByName(event.target);
            });
        }

        document.addEventListener('pos:modal:shown', this.syncFamilySelectionOnModalOpen.bind(this));
    }
};

export default ProductController;
