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
            eventManager.emit('customer:changed', {
                code: response.customer.codcliente,
                description: response.customer.nombre
            });
        }
    },

    init() {
        dispatcher.register('customer:save', this.create);

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
