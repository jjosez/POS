import EventManager from '../core/EventManager.js';
import MainView from '../views/MainView.js';
import * as Core from '../Core.js';

const OrderRefundController = class OrderRefundController {
    init() {
        // abrir desde el modal de últimas operaciones
        EventManager.on(
            'click',
            '[data-action="returns:sale-open-from-list:action"]',
            this.openFromList.bind(this)
        );

        // buscar venta en el modal de devoluciones
        EventManager.register(
            'submit',
            '[data-action="returns:sale-search:action"]',
            this.searchOrder.bind(this)
        );

        // cargar última venta
        EventManager.register(
            'click',
            '[data-action="returns:sale-last:action"]',
            this.loadLastOrder.bind(this)
        );

        // limpiar
        EventManager.register(
            'click',
            '[data-action="returns:sale-clear:action"]',
            this.clear.bind(this)
        );

        // confirmar devolución
        EventManager.register(
            'click',
            '[data-action="returns:sale-confirm:action"]',
            this.confirm.bind(this)
        );
    };

    async openFromList(ev) {
        ev.preventDefault();
        ev.stopPropagation();

        const btn   = ev.currentTarget;
        const code  = btn.dataset.code;
        const model = btn.dataset.model;
        const order = btn.dataset.order;

        // cierra el modal de últimas operaciones
        MainView.toggleLastOrdersModal();

        // pide la venta al backend
        const data = await Core.getOrderForReturn({ code, model, order });

        // muestra el modal de devoluciones con esas líneas
        MainView.showReturnSaleModal(data);
    };

    async searchOrder(ev) {
        ev.preventDefault();

        const form = ev.currentTarget;
        const term = form.term.value.trim();
        if (!term) return;

        const data = await Core.searchOrderForReturn({ term });
        MainView.showReturnSaleModal(data);
    };

    async loadLastOrder() {
        const data = await Core.getLastOrderForReturn();
        MainView.showReturnSaleModal(data);
    };

    clear() {
        MainView.showReturnSaleModal([]); // o limpias vistas específicas
    };

    confirm() {
        // aquí armarías las líneas negativas y despacharías al carrito
    }
};

export default OrderRefundController;
