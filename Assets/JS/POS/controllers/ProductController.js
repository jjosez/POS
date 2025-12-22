import * as Core from '../Core.js';
import MainView from '../views/MainView.js';
import dispatcher from '../core/EventDispatcher.js';
import {searchFilter} from '../models/FilterModel.js';
import eventManager from '../core/EventManager.js';

let searchTimer;

const ProductController = {
    /**
     * @param {string} code
     */
    async searchByBarcode(code) {
        const result = await Core.searchRequest('product:barcode:search', code);

        if (result.code) {
            eventManager.emit('product:scanned:success', {
                dataset: {
                    code: result.code,
                    description: result.description,
                    thumbnail: result.thumbnail || ''
                }
            })
        }
    },

    async searchByName(el) {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        searchTimer = setTimeout(async () => {
            const query = el.value.trim();

            const results = await Core.searchRequest('product:search', query, searchFilter);
            MainView.updateProductSearchResult(results);
        }, 200);

        /*const results = await Core.searchProduct(el.value, searchFilter);
        MainView.updateProductSearchResult(results);*/
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

    /**
     * data-action="product:filter:family:toggle"
     */
    setFamilyFilter(el) {
        const {code, description, thumbnail} = el.dataset;

        searchFilter.toggleFamilyFilter(code, description, thumbnail);
        eventManager.emit('product:filter:changed', searchFilter);
    },

    /**
     * event:on="product:filter:changed"
     */
    async handleFilterChanged(filters) {
        const query = MainView.productSearchBox().value;
        const results = await Core.searchRequest('product:search', query, filters);

        eventManager.emit('product:search:completed', {
            results,
            filters
        });
    },

    /**
     * event:on="product:search:completed"
     */
    handleSearchCompleted({results, filters}) {
        MainView.updateProductFamilyList(filters.families);
        MainView.updateProductSearchResult(results);
    },

    init() {
        dispatcher.register('product:image:show', this.showImages);
        dispatcher.register('product:stock:show', this.showStockDetail);
        dispatcher.register('product:filter:family:toggle', this.setFamilyFilter);

        eventManager.on('product:filter:changed', this.handleFilterChanged.bind(this));
        eventManager.on('product:search:completed', this.handleSearchCompleted.bind(this));

        // Escáner de código de barras
        document.addEventListener('scan', (event) => {
            this.searchByBarcode(event.detail.scanCode);
        });

        // Búsqueda de productos por nombre
        MainView.productSearchBox().addEventListener('keyup', (event) => {
            this.searchByName(event.target);
        });
    }
};

export default ProductController;
