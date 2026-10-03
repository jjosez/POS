/**
 * EventDispatcher — Centralized event control based on `data-action`.
 */
class EventDispatcher {
    constructor() {
        this.listeners = {};
        this._listening = false;
        this.debug = false;
    }

    /**
     * Registers an action and its associated handler
     * @param {string} action - The action name (e.g. "showMesaDetailAction")
     * @param {Function} handler - The function executed on click
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
     * Initializes the global listener (only once)
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
dispatcher.debug = false;
export default dispatcher;
