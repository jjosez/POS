import EventManager from '../core/EventManager.js';

const KeyboardController = {
    keydownHandler: null,

    getRows() {
        return Array.from(document.querySelectorAll('.cart-line[data-index]'));
    },

    getSelectedIndex() {
        const sel = document.querySelector('.cart-line[aria-selected="true"]');
        return sel ? Number(sel.dataset.index) : null;
    },

    selectByIndex(idx) {
        const row = document.querySelector(`.cart-line[data-index="${idx}"]`);
        if (!row) return false;

        document
            .querySelectorAll('.cart-line[aria-selected="true"]')
            .forEach(r => r.setAttribute('aria-selected', 'false'));

        row.setAttribute('aria-selected', 'true');
        row.focus({ preventScroll: true });

        EventManager.emit('cart:line:select', idx);
        return true;
    },

    selectNext(delta) {
        const rows = this.getRows();
        if (!rows.length) return;

        const current = this.getSelectedIndex();
        if (current === null) {
            this.selectByIndex(Number(rows[0].dataset.index));
            return;
        }

        const pos = rows.findIndex(r => Number(r.dataset.index) === current);
        const nextPos = Math.max(0, Math.min(rows.length - 1, pos + delta));
        this.selectByIndex(Number(rows[nextPos].dataset.index));
    },

    handleKeydown(e) {
        // no secuestrar teclas si estás escribiendo
        const tag = e.target?.tagName?.toLowerCase?.() ?? '';
        const isTyping = tag === 'input' || tag === 'textarea' || e.target?.isContentEditable;
        if (isTyping) return;

        const idx = this.getSelectedIndex();

        // si no hay selección y presionan arriba/abajo, selecciona primera
        if (idx === null && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
            e.preventDefault();
            this.selectNext(0);
            return;
        }

        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.selectNext(+1);
                break;

            case 'ArrowUp':
                e.preventDefault();
                this.selectNext(-1);
                break;

            case 'Enter':
                if (idx === null) return;
                e.preventDefault();
                EventManager.emit('cart:line:edit', idx);
                break;

            case 'Delete':
                if (idx === null) return;
                e.preventDefault();
                EventManager.emit('cart:line:delete', idx);
                break;

            case '+':
            case '=':
                if (idx === null) return;
                e.preventDefault();
                EventManager.emit('cart:line:qty:increase', idx);
                break;

            case '-':
                if (idx === null) return;
                e.preventDefault();
                EventManager.emit('cart:line:qty:decrease', idx);
                break;

            case 'Escape':
                // opcional: limpiar selección y ocultar toolbar
                // e.preventDefault();
                // EventManager.emit('cart:line:cleared');
                break;
        }
    },

    destroy() {
        if (!this.keydownHandler) return;
        document.removeEventListener('keydown', this.keydownHandler);
        this.keydownHandler = null;
    },

    init() {
        if (this.keydownHandler) return;
        this.keydownHandler = this.handleKeydown.bind(this);
        document.addEventListener('keydown', this.keydownHandler);
    },
};

export default KeyboardController;
