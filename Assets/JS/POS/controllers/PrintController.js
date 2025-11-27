import dispatcher from '../core/EventDispatcher.js';
import * as Core from '../Core.js';
import MainView from '../views/MainView.js';

const PrintController = {
    async handleSaleContextAction(el) {
        const {type} = el.dataset;

        switch (type) {
            case 'link':
                await this.handleSaleLinkAction(el);
                break;
            case 'action':
                await this.handleSaleExecAction(el);
                break;
            default:
                console.table(el.dataset);
        }
    },

    async handleDraftContextAction(el) {
        const {type} = el.dataset;

        switch (type) {
            case 'link':
                await this.handleDraftLinkAction(el);
                break;
            case 'action':
                await this.handleDraftExecAction(el);
                break;
            default:
                console.table(el.dataset);
        }
    },

    async handleSaleLinkAction(el) {
        const {controller, code, document, name, order} = el.dataset;

        Core.openLinkAction(controller, {
            action: name,
            document: document,
            code: code,
            order: order
        });

        MainView.togglePrintSelectionModal();
    },

    async handleSaleExecAction(el) {
        const {code, document, name, order, params} = el.dataset;

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

    async handleDraftLinkAction(el) {
         const {controller, code, document, name} = el.dataset;

        Core.openLinkAction(controller, {
            action: name,
            document: document,
            code: code
        });

        MainView.togglePrintSelectionModal();
    },

    async handleDraftExecAction(el) {
        const {controller, code, document, name, params, type} = el.dataset;

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
        const {code, model, order} = el.dataset;

        MainView.showPrintOrderContextModal({
            code: code,
            model: model,
            order: order
        });
    },

    printDraftContext(el) {
        const {code, model, order} = el.dataset;

        MainView.showPrintDraftContextModal({
            code: code,
            document: model,
            order: order
        });
    },

    init() {
        dispatcher.register('print:order:ticket', this.handleSaleContextAction.bind(this));
        dispatcher.register('print:draft:ticket', this.handleDraftContextAction.bind(this));
        dispatcher.register('print:order:context', this.printOrderContext);
        dispatcher.register('print:draft:context', this.printDraftContext);
    }
};

export default PrintController;
