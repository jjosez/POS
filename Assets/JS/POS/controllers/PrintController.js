import dispatcher from '../core/EventDispatcher.js';
import * as Core from '../Core.js';
import MainView from '../views/MainView.js';

const PrintController = {
    async printOrderTicket(el) {
        const {controller, code, document, name, order, params, type } = el.dataset;

        if (type === 'link') {
            Core.openLinkAction(controller, params);
            return;
        }

        const formData = new FormData();
        const parsedParams = JSON.parse(params || '{}');

        formData.set('action', 'print-sales-ticket');
        formData.set('action-name', name);
        formData.set('action-params', JSON.stringify(parsedParams));
        formData.set('document-code', code);
        formData.set('document-model', document);
        formData.set('document-order', order);

        const response = await Core.postRequest(formData);
        Core.printerServerRequest(response);

        MainView.togglePrintSelectionModal();
    },

    async printDraftTicket(el) {
        const {controller, code, document, name, order, params, type } = el.dataset;

        if (type === 'link') {
            Core.openLinkAction(controller, params);
            return;
        }

        const formData = new FormData();
        const parsedParams = JSON.parse(params || '{}');

        formData.set('action', 'print-draft-ticket');
        formData.set('code', code);
        formData.set('action-name', name);
        formData.set('action-params', JSON.stringify(parsedParams));
        formData.set('document', document);

        const response = await Core.postRequest(formData);
        Core.printerServerRequest(response);

        MainView.togglePrintSelectionModal();
    },

    printOrderContext(el) {
        const { code, model, order } = el.dataset;

        MainView.showPrintOrderSelectionModal({
            document_code: code,
            document_model: model,
            document_order: order
        });
    },

    printDraftContext(el) {
        const { code, model, order } = el.dataset;

        MainView.showPrintDraftSelectionModal({
            code: code,
            document: model,
            order: order
        });
    },

    init() {
        dispatcher.register('printOrderTicketAction', this.printOrderTicket);
        dispatcher.register('printDraftTicketAction', this.printDraftTicket);
        dispatcher.register('printOrderContextAction', this.printOrderContext);
        dispatcher.register('printDraftContextAction', this.printDraftContext);
    }
};

export default PrintController;
