import Modals from "../components/Modals.js";
import templates from "../views/TemplateManger.js";
import EventManager from "../core/EventManager.js";
import * as Money from "../Money.js";

const viewElements = {
    agentNameLabel: document.getElementById('agentNameLabel'),
    customerNameLabel: document.getElementById('customerNameLabel'),
    customerSearchBox: document.getElementById('customerSearchBox'),
    cartTotalLabel: document.getElementById('cartTotal'),
    documentClassLabel: document.getElementById('documentClassLabel'),
    orderDiscountAmountLabel: document.getElementById('orderDiscountAmountLabel'),
    orderDiscountAmountInput: document.getElementById('orderDiscountAmountInput'),
    orderHoldButton: document.getElementById('orderHoldButton'),
    orderItemsNumberLabel: document.getElementById('orderItemsNumber'),
    orderNetoLabel: document.getElementById('orderTotalNet'),
    orderTaxesLabel: document.getElementById('orderTaxes'),
    orderTotalLabel: document.getElementById('orderTotal'),
    productQuantityInput: document.getElementById('productQuantityInput')
};

class CartView {
    cartTotalLabel = () => viewElements.cartTotalLabel;
    customerSearchBox = () => viewElements.customerSearchBox;
    orderDiscountAmountLabel = () => viewElements.orderDiscountAmountLabel;
    orderDiscountAmountInput = () => viewElements.orderDiscountAmountInput;
    orderHoldButton = () => viewElements.orderHoldButton;
    orderItemsNumberLabel = () => viewElements.orderItemsNumberLabel;
    orderNetoLabel = () => viewElements.orderNetoLabel;
    productQuantityInput = () => viewElements.productQuantityInput;

    showProductEditModal = (product = {}) => {
        this.renderCartEditView(product);
        Modals.toggleModal('product:edit:modal');
    };

    showQuantityEditModal = ({index, cantidad}) => {
        this.productQuantityInput().dataset.index = index;
        this.productQuantityInput().value = cantidad;
        Modals.toggleModal('productQuantityEditModal');
    };

    updateAgentLabel = (name = '') => {
        if (viewElements.agentNameLabel) {
            viewElements.agentNameLabel.textContent = name;
        }
    };

    updateCustomerListView = (data = []) => {
        templates.render('customer:list:template', {customers: data}, 'customer:list:view');
    };

    updateCustomerNameLabel = (name = '') => {
        viewElements.customerNameLabel.textContent = name;
    };

    updateDocumentClassLabel = (name = '') => {
        viewElements.documentClassLabel.textContent = name;
    };

    renderCartEditView = (product = {}) => {
        templates.render('cart:edit:form:template', {product}, 'cart:edit:form:view');
    };

    updateTotals = (data = {}) => {
        this.cartTotalLabel().textContent = Money.roundFixed(data.doc.total);
        this.orderItemsNumberLabel().textContent = Money.roundFixed(data.count);
        this.orderDiscountAmountInput().value = data.doc.dtopor1 ?? 0;
        this.orderDiscountAmountLabel().textContent = Money.roundFixed(data.getDiscountAmount());
        this.orderNetoLabel().textContent = Money.roundFixed(data.doc.neto);

        templates.render('cart:list:template', data, 'cart:list:template:view');
        EventManager.emit('cart:rendered');
    };

    toggleAgentSelectModal = () => {
        Modals.toggleModal('agent:select:modal');
    };

    toggleCustomerSearchModal = () => {
        Modals.toggleModal('customer:search:modal');
    };

    toggleDocumentClassSearchModal = () => {
        Modals.toggleModal('document:type:modal');
    };
}

const instance = new CartView();
Object.freeze(instance);
export default instance;
