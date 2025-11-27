import * as Core from '../Core.js';
import dispatcher from '../core/EventDispatcher.js';
import MainView from '../views/MainView.js';

const SessionController = {
    async closeSession() {
        MainView.toggleLoadingModal();
        const formData = new FormData(MainView.closeSessionForm());
        const response = await Core.postRequest(formData);

        await Core.printerServerRequest(response);
        Core.reloadApp();
    },

    async cashEntry() {
        const formData = new FormData(MainView.cashEntryForm());
        const response = await Core.postRequest(formData);

        MainView.cashEntryForm().reset();
    },

    async cashWithdraw() {
        const formData = new FormData(MainView.cashWithdrawForm());
        const response = await Core.postRequest(formData);

        MainView.cashWithdrawForm().reset();
    },

    async printSessionReportX() {
        MainView.toggleLoadingModal();
        try {
            const data = new FormData();
            data.set('action', 'print-x-report');

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

        MainView.closeSessionForm().addEventListener('submit', function (e) {
            e.preventDefault();
            return false;
        });
    }
};

export default SessionController;
