/**
 * EventDispatcher — Control centralizado de eventos basado en `data-action`.
 */
/*class EventDispatcher {
    constructor() {
        this.handlers = {}; // { actionName: callback }
        this.selector = '[data-action]';
        this.debug = false;
    }

    /!**
     * Registra una acción y su función asociada
     * @param {string} action - Nombre de la acción (ej. "showMesaDetailAction")
     * @param {Function} callback - Función que se ejecuta al hacer clic
     *!/
    register(action, callback) {
        this.handlers[action] = callback;
    }

    /!**
     * Inicializa el listener global (una sola vez)
     *!/
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
}*/
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

    dispatch(action, el) {
        if (this.listeners[action]) {
            this.listeners[action].forEach(handler => handler(el));
        } else {
            if (this.debug) console.warn(`⚠️ No handler registered for action: ${action}`);
        }
    }

    /**
     * Inicializa el listener global (una sola vez)
     */
    listen() {
        if (this._listening) return;
        this._listening = true;

        document.addEventListener('click', (event) => {
            const action = event.target.dataset.action;
            if (action) {
                this.dispatch(action, event.target);
            }
        });
    }
}

const dispatcher = new EventDispatcher();
dispatcher.debug = false;
export default dispatcher;
