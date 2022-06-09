import * as Core from './Core.js';
import * as UI from './AppUI.js';

UI.toggleableElements.forEach(element => element.addEventListener('click', toggleEventHandler));

function deleteOrderHandler(element) {
    Core.deleteOrderRequest(element.dataset.code).then(() => {
        Core.getPausedOrders().then(response => {
            UI.toggleModal(Core.getElement('pausedOrdersModal'));
            UI.updatePausedOrdersListView(response);
        });
    });
}

function resumeOrderHandler(element) {
    Core.resumeOrder(element.dataset.code).then(response => {
        Cart.update(response);
        Cart.doc.token = response.token;
    });
}

export function scanCodeHandler(code) {
    Core.searchBarcode(code).then(response => {
        Cart.addProduct(response.code, response.description);
        UI.productBarcodeBox.value = '';
    });
}

export function saveNewCostumerHandler() {
    const taxID = Core.getElement('newCustomerTaxID').value;
    const name = Core.getElement('newCustomerName').value;

    function saveCustomer(response) {
        if (response.codcliente) {
            Cart.setCustomer(response.codcliente);
            UI.updateCustomer(response.razonsocial);
        }
    }

    Core.saveNewCustomer(taxID, name).then(saveCustomer);
}

export function searchCustomerHandler() {
    Core.searchCustomer(this.value).then(response => {
        UI.updateCustomerListView(response);
    });
}

export function searchProductHandler() {
    Core.searchProduct(this.value || '').then(response => {
        UI.updateProductListView(response);
    });
}

function setCustomerHandler(element) {
    Cart.setCustomer(element.dataset.code);
    UI.updateCustomer(element.dataset.description);
}

function setProductHandler(element) {
    Cart.addProduct(element.dataset.code, element.dataset.description);
}

export function tagToggleHandler(element) {
    Core.toggleTag(element);
    const query = UI.productSearchBox.value;

    Core.searchProduct(query).then(response => {
        UI.updateProductListView(response);
    });
}

export function toggleEventHandler() {
    const target = Core.getElement(this.dataset.target);

    switch (this.dataset.toggle) {
        case 'modal':
            UI.toggleModal(target);
            break;
        case 'collapse':
            UI.toggle(this);
            break;
        default:
            UI.toggle(this);
    }
}

export function customEventHandler(event) {
    const element = event.target;
    switch (true) {
        case element.matches('.add-customer-btn'):
            setCustomerHandler(element);
            break;
        case element.matches('.add-product-btn'):
            console.log(element)
            setProductHandler(element);
            break;
        case element.matches('.resume-order-btn'):
            resumeOrderHandler(element);
            break;
        case element.matches('.delete-order-btn'):
            deleteOrderHandler(element);
            break;
        case element.matches('.product-tag'):
            tagToggleHandler(element);
            break;
        default:
            break;
    }
}
