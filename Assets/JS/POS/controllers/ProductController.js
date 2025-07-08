import * as Core from '../Core.js';
import MainView from '../views/MainView.js';
import dispatcher from '../core/EventDispatcher.js';
import {searchFilter} from '../models/FilterModel.js';
import eventManager from '../core/EventManager.js';

const ProductController = {
    async searchByBarcode(code) {
        const response = await Core.searchBarcode(code);

        if (response.code) {
            dispatcher.dispatch('setProductAction', {
                dataset: {
                    code: response.code,
                    description: response.description,
                    thumbnail: response.thumbnail || ''
                }
            });
        }
    },

    async searchByName(el) {
        const results = await Core.searchProduct(el.value, searchFilter);
        MainView.updateProductSearchResult(results);
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
        eventManager.emit('onProductFilterChange', searchFilter);
    },

    init() {
        dispatcher.register('productImageAction', this.showImages);
        dispatcher.register('stockDetailAction', this.showStockDetail);
        dispatcher.register('setFamilyFilterAction', this.setFamilyFilter);

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
