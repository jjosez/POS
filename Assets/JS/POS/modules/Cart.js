import {recalculateRequest} from "../Order.js";
import CartClass from "../model/CartClass.js";
import * as view from "../View.js";

const Cart = new CartClass({
    'doc': {
        'codserie': AppSettings.document.serie,
        'codalmacen': AppSettings.codalmacen,
        'codcliente': AppSettings.customer.codcliente,
        'idpausada': 'false',
        'tipo-documento': AppSettings.document.code
    },
    'token': AppSettings.token
});

/**
 * @param {{index:int}} data
 */
function productDeleteAction({index}) {
    Cart.deleteProduct(index);
}

/**
 * @param {{index:int}} data
 */
function productShowEditDialog({index}) {
    view.cart().showProductEditModal(Cart.getProduct(index));
}

/**
 * @param {{index:int}} data
 */
function productShowQuantityEditDialog({index}) {
    const product = Cart.getProduct(index);
    view.cart().showQuantityEditModal(product)
}

/**
 * @param {{index:string, field:string}} data
 * @param value
 */
function productEditFieldAction({index, field}, value) {
    Cart.editProduct(index, field, value);

    onChangeCartAction().then(() => {
        view.cart().updateLinesView(Cart.getProduct(index));
    });
}

function productQuantityDecreaseAction() {
    let value = view.cart().productQuantityInput().valueAsNumber;
    let index = view.cart().productQuantityInput().dataset.index;
    value -= 1;

    view.cart().productQuantityInput().valueAsNumber = value;

    productEditFieldAction({field: 'cantidad', index: index}, value);
}

function productQuantityIncreaseAction() {
    let value = view.cart().productQuantityInput().valueAsNumber;
    let index = view.cart().productQuantityInput().dataset.index;
    value += 1;

    view.cart().productQuantityInput().valueAsNumber = value;

    productEditFieldAction({field: 'cantidad', index: index}, value);
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setCustomerAction({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setCustomer(code);
    view.modals().customerSearchModal().hide();
    view.main().updateCustomerNameLabel(description);
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setDocumentAction({code, serie, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.updateDocumentType(code, serie);
    view.modals().documentTypeModal().hide();
    view.main().updateDocumentNameLabel(description);
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setProductAction({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setProduct(code, description);
}

async function onChangeCartAction() {
    Cart.update(await recalculateRequest(Cart));
}

/**
 * @param {{detail}} data
 */
function onUpdateCartAction({detail}) {
    view.cart().updateView(detail);
    view.main().updateView(detail);
}

/**
 * @param {Event} event
 */
function clickCartLineEventHandler(event) {
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null || event.type !== 'click') {
        return;
    }

    switch (action) {
        case 'deleteProductAction':
            return productDeleteAction(data);

        case 'editProductAction':
            return productShowEditDialog(data);

        case 'editProductQuantityAction':
            return productShowQuantityEditDialog(data);

        case 'quantityDecreaseAction':
            return productQuantityDecreaseAction(data);

        case 'quantityIncreaseAction':
            return productQuantityIncreaseAction(data);

        case 'setCustomerAction':
            return setCustomerAction(data);

        case 'setDocumentAction':
            return setDocumentAction(data);

        case 'setProductAction':
            return setProductAction(data);
    }
}

function editDocumentLineEventHandler(event) {
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null) {
        return;
    }

    switch (action) {
        case 'editProductFieldAction':
            return productEditFieldAction(data, event.target.value);
    }
}

function editDocumentFieldAction(field, target) {
    if (typeof field === 'undefined' || target === undefined) {
        return;
    }

    switch (target.type) {
        case 'checkbox':
            Cart.setCustomField(field, target.checked ?? false);
            break;
        default:
            Cart.setCustomField(field, target.value);
    }
}

function editDocumentFieldEventHandler(event) {
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null) {
        return;
    }

    switch (action) {
        case 'edit-document-field':
            return editDocumentFieldAction(data.documentField, event.target);
    }
}

document.addEventListener('click', clickCartLineEventHandler);
document.addEventListener('change', editDocumentLineEventHandler);
document.addEventListener('change', editDocumentFieldEventHandler);
document.addEventListener('onCartChange', onChangeCartAction);
document.addEventListener('onCartUpdate', onUpdateCartAction);

export default Cart;








