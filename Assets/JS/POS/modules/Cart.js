import {cartView, mainView} from "../UI.js";
import {recalculateRequest} from "../Order.js";
import CartClass from "../model/CartClass.js";

const Cart = new CartClass({
    'doc': {
        'codserie': settings.serie,
        'codalmacen': settings.warehouse,
        'codcliente': settings.customer,
        'idpausada': 'false',
        'tipo-documento': settings.document
    },
    'token': settings.token
});

/**
 * @param {{index:int}} data
 */
function productDeleteAction({index}) {
    Cart.deleteProduct(index);
}

/**
 * @param value
 */
function editDiscountAction(value) {
    Cart.setDiscountPercent(value);
}

/**
 * @param {{index:int}} data
 */
function productShowEditDialog({index}) {
    cartView().updateEditForm(Cart.getProduct(index));
    cartView().showEditView();
}

/**
 * @param {{index:int}} data
 */
function productShowQuantityEditDialog({index}) {
    const product = Cart.getProduct(index);
    cartView().updateEditForm(product);
    cartView().showQuantityEditView(product);
}

/**
 * @param {{index:string, field:string}} data
 * @param value
 */
function productEditFieldAction({index, field}, value) {
    Cart.editProduct(index, field, value);

    onChangeCartAction().then(() => {
        cartView().updateEditForm(Cart.getProduct(index));
    });
}

function productQuantityDecreaseAction() {
    let value = cartView().productQuantityInput.valueAsNumber;
    let index = cartView().productQuantityInput.dataset.index
    value -= 1;

    cartView().productQuantityInput.valueAsNumber = value;

    productEditFieldAction({index: index, field: 'cantidad'}, value);
}

function productQuantityIncreaseAction() {
    let value = cartView().productQuantityInput.valueAsNumber++;
    let index = cartView().productQuantityInput.dataset.index
    value += 1;

    cartView().productQuantityInput.valueAsNumber = value;

    productEditFieldAction({index: index, field: 'cantidad'}, value);
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setCustomerAction({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setCustomer(code);
    mainView().toggleCustomerListModal();
    mainView().updateCustomer(description);
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setDocumentAction({code, serie, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setDocumentType(code, serie);
    mainView().toggleDoctypeListModal();
    mainView().updateDocument(description);
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
    cartView().updateListView(detail);
    cartView().updateTotals(detail);
}

/**
 * @param {{detail}} data
 */
function onCartCustomFieldUpdateAction({detail}) {
    if (typeof detail.field === 'undefined' || detail.value === null) {
        return;
    }
    Cart.setCustomField(detail.field, detail.value);
    console.log(Cart.doc);
}

/**
 * @param {Event} event
 */
function clickCartEventHandler(event) {
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

function editCartEventHandler(event) {
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null) {
        return;
    }

    switch (action) {
        case 'editDiscountAction':
            return editDiscountAction(event.target.value);

        case 'editProductFieldAction':
            return productEditFieldAction(data, event.target.value);
    }
}

document.addEventListener('click', clickCartEventHandler);
document.addEventListener('change', editCartEventHandler);
document.addEventListener('onCartChange', onChangeCartAction);
document.addEventListener('onCartUpdate', onUpdateCartAction);
document.addEventListener('onCartCustomFieldUpdate', onCartCustomFieldUpdateAction);

export default Cart;








