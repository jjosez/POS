import * as Core from '../Core.js';
import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';
import MainView from '../views/MainView.js';

const decimals = Number.parseInt(AppSettings.currency?.decimals, 10) || 2;
const locale = document.documentElement.lang || undefined;
const amountFormatter = new Intl.NumberFormat(locale, {
    minimumFractionDigits: decimals,
    maximumFractionDigits: decimals,
});
const denominationFormatter = new Intl.NumberFormat(locale, {
    maximumFractionDigits: decimals,
});

const SessionController = {
    updateCashCount(input) {
        const form = MainView.closeSessionForm();
        const quantity = Math.max(0, Math.trunc(Number(input.value) || 0));
        const value = Number(input.dataset.value) || 0;
        const subtotal = value * quantity;
        const row = input.closest('[data-cash-count-row]');

        if (input.value !== '' && Number(input.value) !== quantity) {
            input.value = String(quantity);
        }
        const subtotalElement = row?.querySelector('[data-cash-subtotal]');
        if (subtotalElement) {
            subtotalElement.textContent = amountFormatter.format(subtotal);
        }

        const total = Array.from(form.querySelectorAll('[data-cash-count-input]'))
            .reduce((sum, element) => {
                const count = Math.max(0, Math.trunc(Number(element.value) || 0));
                return sum + (Number(element.dataset.value) || 0) * count;
            }, 0);

        const totalElement = document.getElementById('cashCountedTotal');
        if (totalElement) {
            totalElement.textContent = amountFormatter.format(total);
        }

        EventManager.emit('session:cash-count:changed', {
            code: input.dataset.code,
            quantity,
            subtotal,
            total,
        });
    },

    async closeSession() {
        MainView.toggleLoadingModal();
        const formData = new FormData(MainView.closeSessionForm());
        const result = await Core.postRequest(formData);

        if (result.error) {
            MainView.toggleLoadingModal();
            Core.showMessages(result);

            return;
        }

        if (result.success === false) {
            MainView.toggleLoadingModal();
            Core.showMessages(result);
            return;
        }

        await Core.printerServerRequest(result);
        Core.reloadApp();
    },

    async cashEntry() {
        const formData = new FormData(MainView.cashEntryForm());
        await Core.postRequest(formData);

        MainView.cashEntryForm().reset();
    },

    async cashWithdraw() {
        const formData = new FormData(MainView.cashWithdrawForm());
        await Core.postRequest(formData);

        MainView.cashWithdrawForm().reset();
    },

    async printSessionReportX() {
        MainView.toggleLoadingModal();
        try {
            const data = new FormData();
            data.set('action', 'print:report:x');

            const response = await Core.postRequest(data);
            await Core.printerServerRequest(response);
        } finally {
            MainView.toggleLoadingModal();
        }
    },

    init() {
        dispatcher.register('session:cash:entry', this.cashEntry);
        dispatcher.register('session:cash:withdraw', this.cashWithdraw);
        dispatcher.register('session:close', this.closeSession);
        dispatcher.register('session:report:x', this.printSessionReportX);

        const closeSessionForm = MainView.closeSessionForm();
        closeSessionForm.addEventListener('submit', function (e) {
            e.preventDefault();
            return false;
        });
        closeSessionForm.addEventListener('input', event => {
            if (event.target.matches('[data-cash-count-input]')) {
                this.updateCashCount(event.target);
            }
        });

        closeSessionForm.querySelectorAll('[data-cash-denomination]').forEach(element => {
            element.textContent = denominationFormatter.format(Number(element.dataset.value) || 0);
        });
    }
};

export default SessionController;
