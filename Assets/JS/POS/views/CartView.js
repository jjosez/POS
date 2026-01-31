import Modals from "../components/Modals.js";
import templates from "../views/TemplateManger.js";
import EventManager from "../core/EventManager.js";
import * as Money from "../Money.js";

const viewElements = {
    agentNameLabel: document.getElementById('agentNameLabel'),
    beepAudio: document.getElementById('beepAudio'),
    cartLineToolbar: document.getElementById('cartLineToolbar'),
    cartLineToolbarIndex: document.getElementById('cartLineToolbarIndex'),
    cartTotalLabel: document.getElementById('cartTotal'),
    customerNameLabel: document.getElementById('customerNameLabel'),
    customerSearchBox: document.getElementById('customerSearchBox'),
    documentClassLabel: document.getElementById('documentClassLabel'),
    orderDiscountAmountInput: document.getElementById('orderDiscountAmountInput'),
    orderDiscountAmountLabel: document.getElementById('orderDiscountAmountLabel'),
    orderHoldButton: document.getElementById('orderHoldButton'),
    orderItemsNumberLabel: document.getElementById('orderItemsNumber'),
    orderNetoLabel: document.getElementById('orderTotalNet'),
    orderTaxesLabel: document.getElementById('orderTaxes'),
    orderTotalLabel: document.getElementById('orderTotal'),
    productQuantityInput: document.getElementById('productQuantityInput')
};

class CartView {
    beepAudio = () => viewElements.beepAudio;
    cartTotalLabel = () => viewElements.cartTotalLabel;
    customerSearchBox = () => viewElements.customerSearchBox;
    orderDiscountAmountLabel = () => viewElements.orderDiscountAmountLabel;
    orderDiscountAmountInput = () => viewElements.orderDiscountAmountInput;
    orderHoldButton = () => viewElements.orderHoldButton;
    orderItemsNumberLabel = () => viewElements.orderItemsNumberLabel;
    orderNetoLabel = () => viewElements.orderNetoLabel;
    productQuantityInput = () => viewElements.productQuantityInput;
    cartLineToolbar = () => viewElements.cartLineToolbar;

    applySelection(selectedIndex) {
        if (selectedIndex === null) {
            this.clearSelection();
            return;
        }

        const row = document.querySelector(`.cart-line[data-index="${selectedIndex}"]`);
        if (!row) return; // todavía no existe en DOM (render pendiente)

        this.getSelectedCartLinesElements()
            .forEach(r => r.setAttribute('aria-selected', 'false'));

        row.setAttribute('aria-selected', 'true');
        row.focus?.({preventScroll: true});

        row.scrollIntoView?.({behavior: 'smooth', block: 'nearest'});

        this.showLineToolbar(selectedIndex);
    };

    clearSelection() {
        this.getSelectedCartLinesElements()
            .forEach(r => r.setAttribute('aria-selected', 'false'));

        this.hideLineToolbar();
    };

    getSelectedCartLinesElements = () => {
        return Array.from(document.querySelectorAll('.cart-line[aria-selected="true"]'));
    };

    hideLineToolbar = () => {
        viewElements.cartLineToolbar.classList.add('hidden');
    };

    renderCartEditView = (product = {}) => {
        templates.render('cart:edit:form:template', {product}, 'cart:edit:form:view');
    };

    showProductEditModal = (product = {}) => {
        this.renderCartEditView(product);
        Modals.toggleModal('product:edit:modal');
    };

    showQuantityEditModal = ({index, cantidad}) => {
        this.productQuantityInput().dataset.index = index;
        this.productQuantityInput().value = cantidad;
        Modals.toggleModal('productQuantityEditModal');
    };

    showLineToolbar = (index) => {
        viewElements.cartLineToolbarIndex.textContent = index + 1;
        viewElements.cartLineToolbar.classList.remove('hidden');
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

    updateTotals = (data = {}) => {
        viewElements.cartTotalLabel.textContent = Money.roundFixed(data.doc.total);
        viewElements.orderItemsNumberLabel.textContent = Money.roundFixed(data.count);
        viewElements.orderDiscountAmountInput.value = data.doc.dtopor1 ?? 0;
        viewElements.orderDiscountAmountLabel.textContent = Money.roundFixed(data.getDiscountAmount());
        viewElements.orderNetoLabel.textContent = Money.roundFixed(data.doc.neto);

        templates.render('cart:list:template', data, 'cart:list:template:view');
        EventManager.emit('event:cart:rendered');
    };
}

const instance = new CartView();
Object.freeze(instance);
export default instance;
