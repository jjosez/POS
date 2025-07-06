class EventManager {
    constructor() {
        this.events = {};
        this.debug = false;
    }

    on(event, listener) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        if (!this.events[event].includes(listener)) {
            this.events[event].push(listener);
        }
    }

    emit(event, ...args) {
        if (!this.events[event]) return;
        if (this.debug) {
            console.log(`📢 Emitting: ${event}`, ...args)
            console.trace();
        }
        this.events[event].forEach(listener => listener(...args));
    }

    off(event, listener) {
        if (!this.events[event]) return;
        this.events[event] = this.events[event].filter(l => l !== listener);
    }

    clear(event) {
        if (event) {
            delete this.events[event];
        } else {
            this.events = {};
        }
    }
}

const eventManager = new EventManager();
eventManager.debug = false;
export default eventManager;
