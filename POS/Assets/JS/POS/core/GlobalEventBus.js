/**
 * Global Event Bus - Global event system for communication between scripts
 *
 * Usage:
 * - Listen to events: window.posAppEvents.on('eventName', callback)
 * - Emit events: window.posAppEvents.emit('eventName', data)
 * - Stop listening: window.posAppEvents.off('eventName', callback)
 */
class GlobalEventBus {
    constructor() {
        this.events = {};
        this.debug = false;
    }

    /**
     * Registers a listener for an event
     * @param {string} event - The event name
     * @param {Function} listener - The callback function
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
     * Registers a listener that runs only once
     * @param {string} event - The event name
     * @param {Function} listener - The callback function
     */
    once(event, listener) {
        const onceWrapper = (...args) => {
            listener(...args);
            this.off(event, onceWrapper);
        };
        this.on(event, onceWrapper);
    }

    /**
     * Emits an event with optional data
     * @param {string} event - The event name
     * @param {...*} args - Arguments passed to the listeners
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
     * Removes a specific listener from an event
     * @param {string} event - The event name
     * @param {Function} listener - The callback function to remove
     */
    off(event, listener) {
        if (!this.events[event]) return;

        this.events[event] = this.events[event].filter(l => l !== listener);

        if (this.debug) {
            console.log(`[GlobalEventBus] Removed listener from: ${event}`);
        }
    }

    /**
     * Clears all listeners from an event, or all events
     * @param {string} [event] - The event name (optional)
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
     * Lists all registered events
     * @returns {Array<string>}
     */
    listEvents() {
        return Object.keys(this.events);
    }

    /**
     * Gets the number of listeners for an event
     * @param {string} event - The event name
     * @returns {number}
     */
    listenerCount(event) {
        return this.events[event] ? this.events[event].length : 0;
    }

    /**
     * Enables/disables debug mode
     * @param {boolean} enabled
     */
    setDebug(enabled) {
        this.debug = enabled;
        console.log(`[GlobalEventBus] Debug mode ${enabled ? 'enabled' : 'disabled'}`);
    }
}

// Create the global instance
if (!window.posAppEvents) {
    window.posAppEvents = new GlobalEventBus();
}

// Export for use with ES6 modules
export default window.posAppEvents;
