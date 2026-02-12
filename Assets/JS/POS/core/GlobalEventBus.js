/**
 * Global Event Bus - Sistema de eventos globales para comunicación entre scripts
 *
 * Uso:
 * - Escuchar eventos: window.posAppEvents.on('eventName', callback)
 * - Emitir eventos: window.posAppEvents.emit('eventName', data)
 * - Dejar de escuchar: window.posAppEvents.off('eventName', callback)
 */
class GlobalEventBus {
    constructor() {
        this.events = {};
        this.debug = false;
    }

    /**
     * Registra un listener para un evento
     * @param {string} event - Nombre del evento
     * @param {Function} listener - Función callback
     */
    on(event, listener) {
        if (typeof listener !== 'function') {
            console.error('[GlobalEventBus] Listener must be a function');
            return;
        }

        if (!this.events[event]) {
            this.events[event] = [];
        }

        if (!this.events[event].includes(listener)) {
            this.events[event].push(listener);

            if (this.debug) {
                console.log(`[GlobalEventBus] Registered listener for: ${event}`);
            }
        }
    }

    /**
     * Registra un listener que se ejecuta solo una vez
     * @param {string} event - Nombre del evento
     * @param {Function} listener - Función callback
     */
    once(event, listener) {
        const onceWrapper = (...args) => {
            listener(...args);
            this.off(event, onceWrapper);
        };
        this.on(event, onceWrapper);
    }

    /**
     * Emite un evento con datos opcionales
     * @param {string} event - Nombre del evento
     * @param {...*} args - Argumentos a pasar a los listeners
     */
    emit(event, ...args) {
        if (!this.events[event] || this.events[event].length === 0) {
            if (this.debug) {
                console.log(`[GlobalEventBus] No listeners for: ${event}`);
            }
            return;
        }

        if (this.debug) {
            console.log(`[GlobalEventBus] Emitting: ${event}`, ...args);
        }

        this.events[event].forEach(listener => {
            try {
                listener(...args);
            } catch (error) {
                console.error(`[GlobalEventBus] Error in listener for ${event}:`, error);
            }
        });
    }

    /**
     * Elimina un listener específico de un evento
     * @param {string} event - Nombre del evento
     * @param {Function} listener - Función callback a eliminar
     */
    off(event, listener) {
        if (!this.events[event]) return;

        this.events[event] = this.events[event].filter(l => l !== listener);

        if (this.debug) {
            console.log(`[GlobalEventBus] Removed listener from: ${event}`);
        }
    }

    /**
     * Limpia todos los listeners de un evento o todos los eventos
     * @param {string} [event] - Nombre del evento (opcional)
     */
    clear(event) {
        if (event) {
            delete this.events[event];
            if (this.debug) {
                console.log(`[GlobalEventBus] Cleared event: ${event}`);
            }
        } else {
            this.events = {};
            if (this.debug) {
                console.log('[GlobalEventBus] Cleared all events');
            }
        }
    }

    /**
     * Lista todos los eventos registrados
     * @returns {Array<string>}
     */
    listEvents() {
        return Object.keys(this.events);
    }

    /**
     * Obtiene el número de listeners para un evento
     * @param {string} event - Nombre del evento
     * @returns {number}
     */
    listenerCount(event) {
        return this.events[event] ? this.events[event].length : 0;
    }

    /**
     * Activa/desactiva el modo debug
     * @param {boolean} enabled
     */
    setDebug(enabled) {
        this.debug = enabled;
        console.log(`[GlobalEventBus] Debug mode ${enabled ? 'enabled' : 'disabled'}`);
    }
}

// Crear instancia global
if (!window.posAppEvents) {
    window.posAppEvents = new GlobalEventBus();
}

// Exportar para uso con módulos ES6
export default window.posAppEvents;
