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
function deleteProductAction({index}) {
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
function editProductAction({index}) {
    cartView().updateEditForm(Cart.getProduct(index));
    cartView().showEditView();
}

/**
 * @param {{index:int, field:string}} data
 * @param value
 */
function editProductFieldAction({index, field}, value) {
    Cart.editProduct(index, field, value);

    onChangeCartAction().then(() => {
        cartView().updateEditForm(Cart.getProduct(index));
    });
}

/**
 * @param {{code:string|null, description:string}} data
 */
function setCustomerAction({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setCustomer(code);
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
            return deleteProductAction(data);

        case 'editProductAction':
            return editProductAction(data);

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
            return editProductFieldAction(data, event.target.value);
    }
}

document.addEventListener('click', clickCartEventHandler);
document.addEventListener('change', editCartEventHandler);
document.addEventListener('onCartChange', onChangeCartAction);
document.addEventListener('onCartUpdate', onUpdateCartAction);

export default Cart;








