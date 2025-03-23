import CartModel from "../model/CartModel.js";
import CartView from '../view/CartView.js';
import EventManager from "../components/EventManager.js";
import {recalculateRequest} from "../Order.js";

const Cart = new CartModel({
    'doc': {
        'codserie': AppSettings.document.serie,
        'codalmacen': AppSettings.codalmacen,
        'codcliente': AppSettings.customer.codcliente,
        'idpausada': null,
        'tipo-documento': AppSettings.document.code
    },
    'token': AppSettings.token
});

/**
 * Deletes a product from the cart based on the provided index.
 *
 * @param {{index: number}} data - The data object containing the index of the product to be deleted.
 * @param {number} data.index - The index or identifier of the product in the cart to be deleted.
 */
function productDeleteAction({index}) {
    Cart.deleteProduct(index);
}

/**
 * Opens the product edit modal for the specified product identified by the given index.
 *
 * @param {{index: number}} data - The data object containing the index of the product to be edited.
 * @param {number} data.index - The index or identifier of the product in the cart to be edited.
 */
function productEditAction({index}) {
    const item = Cart.getProduct(index);
    CartView.showProductEditModal(item);
}

/**
 * Opens the quantity edit modal for the specified product identified by the given index.
 *
 * @param {{index: number}} data - The data object containing the index of the product whose quantity is to be edited.
 * @param {number} data.index - The index or identifier of the product in the cart whose quantity is to be changed.
 */
function productQuantityEditAction({index}) {
    const item = Cart.getProduct(index);
    CartView.showQuantityEditModal(item);
}

/**
 * Edit a product model field, and update the view.
 *
 * @param {{index: string, field: string}} data - The data related to the product field being edited.
 * @param {string} data.index - The index or identifier of the product to be edited in the cart.
 * @param {string} data.field - The specific field of the product to be updated (e.g., 'cantidad' for quantity).
 * @param {any} value - The new value to set for the specified product field (e.g., updated quantity or price).
 */
async function productEditFieldAction({index, field}, value) {
    await Cart.editProduct(index, field, value);

    onChangeCartAction().then(() => CartView.updateCartEditView(Cart.getProduct(index)));
}

/**
 * Decreases the quantity of the specified product by 1. If the quantity is already 0, it remains at 0.
 *
 * @param {{index: string}} data - The data related to the product whose quantity is being decreased.
 * @param {string} data.index - The index or identifier of the product to decrease the quantity.
 */
function productQuantityDecreaseAction({index}) {
    let product = Cart.getProduct(index);
    let value = product.cantidad - 1 || 0;

    productEditFieldAction({field: 'cantidad', index: index}, value);
}


/**
 * Increases the quantity of the specified product by 1.
 *
 * @param {{index: string}} data - The data related to the product whose quantity is being increased.
 * @param {string} data.index - The index or identifier of the product to increase the quantity.
 */
function productQuantityIncreaseAction({index}) {
    let product = Cart.getProduct(index);
    let value = product.cantidad + 1;

    productEditFieldAction({field: 'cantidad', index: index}, value);
}

/**
 * @param {{code: string | null, description: string}} data
 * @param {string | null} data.code - El código del cliente.
 * @param {string} data.description - El nombre del cliente.
 */
function setCustomerAction({code, description}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setCustomer(code);

    CartView.updateCustomerNameLabel(description);
    CartView.toggleCustomerSearchModal();
}

/**
 * @param {{code: string | null, serie: string, description: string}} data
 * @param {string | null} data.code - El código del documento.
 * @param {string} data.serie - La serie asociada al documento.
 * @param {string} data.description - La descripción del tipo de documento.
 */
function setDocumentAction({code, serie, description}) {
    if (typeof code === 'undefined' || code === null) {
        Cart.setDocumentClass(AppSettings.document.code, AppSettings.document.serie);
        CartView.updateDocumentClassLabel(AppSettings.document.description);
        return;
    }

    Cart.updateDocumentType(code, serie);
    CartView.updateDocumentClassLabel(description);
    CartView.toggleDocumentClassSearchModal();
}

/**
 * @param {{code: string | null, description: string, thumbnail: string}} data
 * @param {string | null} data.code - The unique identifier for the product.
 * @param {string} data.description - The name or description of the product to be added.
 * @param {string} data.thumbnail - The URL or path to the product's thumbnail image.
 */
function productAddAction({code, description, thumbnail}) {
    if (typeof code === 'undefined' || code === null) {
        return;
    }
    Cart.setProduct(code, description, thumbnail);
}

/**
 * @param {{data}} data
 */
function onUpdateCartAction(data) {
    CartView.updateTotals(data);
}

function onOrderResumeAction({doc}) {
    const supportedDocuments = AppSettings['supported-documents'];

    const documentClass = supportedDocuments.find(
        item => item.codserie === doc.codserie && item.tipodoc === doc.generadocumento
    );

    if (!documentClass) {
        return;
    }

    CartView.updateDocumentClassLabel(documentClass.descripcion);
}

async function onChangeCartAction() {
    Cart.update(await recalculateRequest(Cart));
}

/**
 * Handles click events on cart line items, triggering corresponding actions based on the event's data-action attribute.
 *
 * @param {Event} event - The click event object triggered by the user interacting with the cart line item.
 */
function clickCartLineEventHandler(event) {
    const {action} = event.target.dataset;
    if (!action || event.type !== 'click') return;

    const actionMap = {
        'deleteProductAction': productDeleteAction,
        'editProductAction': productEditAction,
        'editProductQuantityAction': productQuantityEditAction,
        'quantityDecreaseAction': productQuantityDecreaseAction,
        'quantityIncreaseAction': productQuantityIncreaseAction,
        'setCustomerAction': setCustomerAction,
        'setDocumentAction': setDocumentAction,
        'setProductAction': productAddAction,
    };

    if (actionMap[action]) {
        actionMap[action](event.target.dataset);
    }
}

/**
 * Handles the click events on the cart line items, triggering corresponding actions based on the event's data-action attribute.
 *
 * This function checks the action specified in the event's target `data-action` attribute and calls the appropriate
 * function to perform actions such as deleting a product, editing a product, or modifying the product's quantity in the cart.
 *
 * @param {Event} event - The click event object triggered by the user interacting with the cart line item.
 *
 * @param {Event.target} event.target - The DOM element that triggered the event, expected to have a `data-action` attribute.
 * @param {string} event.target.dataset.action - The action to be performed, corresponding to a case in the switch statement.
 */
/*function clickCartLineEventHandler2(event) {
    const data = event.target.dataset;
    const action = data.action;

    if (typeof action === 'undefined' || action === null || event.type !== 'click') {
        return;
    }

    switch (action) {
        case 'deleteProductAction':
            return productDeleteAction(data);

        case 'editProductAction':
            return productEditAction(data);

        case 'editProductQuantityAction':
            return productQuantityEditAction(data);

        case 'quantityDecreaseAction':
            return productQuantityDecreaseAction(data);

        case 'quantityIncreaseAction':
            return productQuantityIncreaseAction(data);

        case 'setCustomerAction':
            return setCustomerAction(data);

        case 'setDocumentAction':
            return setDocumentAction(data);

        case 'setProductAction':
            return productAddAction(data);
    }
}*/

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

EventManager.on('onCartChange', onChangeCartAction);
EventManager.on('onCartUpdate', onUpdateCartAction);
EventManager.on('onCustomerChange', setCustomerAction);
EventManager.on('onOrderComplete', setDocumentAction);
EventManager.on('onOrderResume', onOrderResumeAction);

export default Cart;








