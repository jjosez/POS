import * as Core from '../Core.js';
import eventManager from '../core/EventManager.js';
import dispatcher from '../core/EventDispatcher.js';
import CartView from '../views/CartView.js';

const CustomerController = {

    async search(query) {
        const results = await Core.searchCustomer(query);
        CartView.updateCustomerListView(results);
    },

    async create() {
        const taxID = Core.getElement('newCustomerTaxID').value;
        const name = Core.getElement('newCustomerName').value;

        const response = await Core.saveNewCustomer(taxID, name);

        if (response.customer?.codcliente) {
            eventManager.emit('onCustomerChange', {
                code: response.customer.codcliente,
                description: response.customer.nombre
            });
        }
    },

    init() {
        dispatcher.register('saveCustomerAction', this.create);

        CartView.customerSearchBox().addEventListener('keyup', (event) => {
            this.search(event.target.value);
        });
    }
};

export default CustomerController;
