import * as Core from '../Core.js';
import MainView from '../views/MainView.js';
import dispatcher from '../core/EventDispatcher.js';
import {searchFilter} from '../models/FilterModel.js';
import eventManager from '../core/EventManager.js';

let searchTimer;

const ProductController = {
    async searchByBarcode(code) {
        const response = await Core.searchBarcode(code);

        if (response.code) {
            dispatcher.dispatch('cart:product:add', {
                dataset: {
                    code: response.code,
                    description: response.description,
                    thumbnail: response.thumbnail || ''
                }
            });
        }
    },

    async searchByName(el) {
        if (searchTimer) {
            clearTimeout(searchTimer);
        }

        searchTimer = setTimeout(async () => {
            const query = el.value.trim();

            const results = await Core.searchProduct(query, searchFilter);
            MainView.updateProductSearchResult(results);
        }, 200);

        /*const results = await Core.searchProduct(el.value, searchFilter);
        MainView.updateProductSearchResult(results);*/
    },

    async showImages(el) {
        const {id, code} = el.dataset;

        const images = await Core.getProductImages(id, code);
        MainView.showProductImagesModal(images);
    },

    async showStockDetail(el) {
        const {code} = el.dataset;

        const stock = await Core.getProductStock(code);
        MainView.showProductStockDetailModal(stock);
    },

    setFamilyFilter(el) {
        const {code, description, thumbnail} = el.dataset;

        searchFilter.toggleFamilyFilter(code, description, thumbnail);
        eventManager.emit('product:filter:changed', searchFilter);
    },

    init() {
        dispatcher.register('product:image:show', this.showImages);
        dispatcher.register('product:stock:show', this.showStockDetail);
        dispatcher.register('product:filter:family:toggle', this.setFamilyFilter);

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
