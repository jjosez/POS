import templates from "./TemplateManger.js";

/**
 * Renderiza la sección del carrito
 * @param {Object} cart
 */
export function renderCart(cart) {
    templates.render('#cartViewTemplate', {cart}, '#cartView');
}

/**
 * Renderiza la sección de productos
 * @param {Array} products
 */
export function renderProductsGrid(products) {
    templates.render('#productGridViewTemplate', {products}, '#productGridView');
}

/**
 * Renderiza la sección de filtros
 * @param {Array} filters
 */
export function renderProductsFilter(filters) {
    templates.render('#productFilterViewTemplate', {filters}, '#productFilterView');
}
