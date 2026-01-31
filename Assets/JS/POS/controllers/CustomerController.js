import * as Core from '../Core.js';
import eventManager from '../core/EventManager.js';
import dispatcher from '../core/EventDispatcher.js';
import CartView from '../views/CartView.js';

const CustomerController = {

    async create() {
        const taxID = Core.getElement('newCustomerTaxID').value;
        const name = Core.getElement('newCustomerName').value;
        const data = new FormData();

        data.set('action', 'customer:create');
        data.set('taxID', taxID);
        data.set('name', name);

        const response = await Core.postRequest(data);

        if (response.customer?.codcliente) {
            eventManager.emit('event:customer:changed', {
                code: response.customer.codcliente,
                description: response.customer.nombre
            });
        }
    },


    /**
     * Update the customer code on the document.
     *
     * @param {HTMLElement} el - The DOM element that triggered the action.
     * @property {string} el.dataset.index - The index of the product to delete.
     * @property {string} el.dataset.description - The index of the product to delete.
     */
    setCustomer(el) {
        const {code, description} = el.dataset;
        if (!code) return;

        eventManager.emit('event:customer:changed', {
            code: code,
            description: description
        });
    },

    init() {
        dispatcher.register('customer:save', this.create);
        dispatcher.register('cart:customer:set', this.setCustomer.bind(this));

        CartView.customerSearchBox().addEventListener('keyup', (event) => {
            this.search(event.target.value);
        });
    },

    async search(query) {
        const results = await Core.searchRequest('customer:search', query);
        CartView.updateCustomerListView(results);
    }
};

export default CustomerController;
