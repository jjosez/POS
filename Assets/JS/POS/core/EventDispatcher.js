/**
 * EventDispatcher — Control centralizado de eventos basado en `data-action`.
 */
class EventDispatcher {
    constructor() {
        this.handlers = {}; // { actionName: callback }
        this.selector = '[data-action]';
        this.debug = false;
    }

    /**
     * Registra una acción y su función asociada
     * @param {string} action - Nombre de la acción (ej. "showMesaDetailAction")
     * @param {Function} callback - Función que se ejecuta al hacer clic
     */
    register(action, callback) {
        this.handlers[action] = callback;
    }

    /**
     * Inicializa el listener global (una sola vez)
     */
    listen() {
        document.addEventListener('click', (event) => {
            const el = event.target.closest(this.selector);
            if (!el) return;

            const action = el.dataset.action;
            const handler = this.handlers[action];

            if (handler) {
                handler(el, event);
            } else {
                 if (this.debug) console.warn(`⚠️ No handler registered for action: ${action}`);
            }
        });
    }
}

const dispatcher = new EventDispatcher();
export default dispatcher;
