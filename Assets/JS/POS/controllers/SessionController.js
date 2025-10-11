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

    cashEntry() {
        MainView.cashEntryForm().submit();
    },

    cashWithdraw() {
        MainView.cashWithdrawForm().submit();
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
        dispatcher.register('cashEntryAction', this.cashEntry);
        dispatcher.register('cashWithdrawAction', this.cashWithdraw);
        dispatcher.register('closeSessionAction', this.closeSession);
        dispatcher.register('printClosingTicketAction', this.printSessionReportX);

        MainView.closeSessionForm().addEventListener('submit', function (e) {
            e.preventDefault();
            return false;
        });
    }
};

export default SessionController;
