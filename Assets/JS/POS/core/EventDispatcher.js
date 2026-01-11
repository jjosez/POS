/**
 * EventDispatcher — Control centralizado de eventos basado en `data-action`.
 */
class EventDispatcher {
    constructor() {
        this.listeners = {};
        this._listening = false;
        this.debug = false;
    }

    /**
     * Registra una acción y su función asociada
     * @param {string} action - Nombre de la acción (ej. "showMesaDetailAction")
     * @param {Function} handler - Función que se ejecuta al hacer clic
     */
    register(action, handler) {
        if (!this.listeners[action]) {
            this.listeners[action] = [];
        }
        if (!this.listeners[action].includes(handler)) {
            this.listeners[action].push(handler);
        }
    }

    dispatch(action, el, event) {
        if (this.listeners[action]) {
            this.listeners[action].forEach(handler => handler(el, event));
        } else if (this.debug) {
            console.warn(`⚠️ No handler registered for action: ${action}`);
        }
    }

    /**
     * Inicializa el listener global (una sola vez)
     */
    listen() {
        if (this._listening) return;
        this._listening = true;

        document.addEventListener('click', (event) => {
            const el = event.target.closest('[data-action]');
            if (!el) return;

            const action = el.dataset.action;
            this.dispatch(action, el, event);
        });
    }
}

const dispatcher = new EventDispatcher();
dispatcher.debug = true;
export default dispatcher;
