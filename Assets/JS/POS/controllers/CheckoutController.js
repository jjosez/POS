import * as CheckoutView from '../views/CheckoutView.js';
import CheckoutModel from '../models/CheckoutModel.js';
import dispatcher from '../core/EventDispatcher.js';
import EventManager from '../core/EventManager.js';
import PaymentSourceManager from '../paymentSources/PaymentSourceManager.js';
import templates from '../views/TemplateManger.js';

const CheckoutController = {
    inputHandler: null,
    mountId: 'checkoutPaymentSourcesMount',
    lastContextSignature: '',

    deletePayment(el) {
        const index = Number(el.dataset.index);
        if (!Number.isFinite(index) || index < 0) return;

        const state = CheckoutModel.getState();
        const flat = this.flattenState(state);
        const entry = flat[index];
        if (!entry) return;

        if (entry.kind === 'source') {
            CheckoutModel.deletePaymentSource(entry.code);
        } else {
            CheckoutModel.deletePayment(this.nativeIndexFor(state, index));
        }
    },

    flattenState(state) {
        const native = state.payments.map(p => ({...p, kind: 'native'}));
        const sources = state.paymentSources.map(s => ({
            ...s,
            description: s.label,
            kind: 'source'
        }));
        return [...native, ...sources];
    },

    nativeIndexFor(state, flatIndex) {
        const flat = this.flattenState(state);
        let nativeCount = -1;
        for (let i = 0; i <= flatIndex; i += 1) {
            if (flat[i] && flat[i].kind === 'native') {
                nativeCount += 1;
            }
        }
        return nativeCount;
    },

    recalculatePayment(el) {
        if (el.dataset.value !== 'balance') return;

        CheckoutView.setPaymentInputValue(CheckoutModel.getOutstandingBalance());
        CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
        CheckoutView.focusPaymentInput();
    },

    setPayment(el) {
        let amount = CheckoutView.getPaymentInputValue();
        if (amount === 0) {
            amount = Math.max(0, CheckoutModel.getOutstandingBalance());
        }

        CheckoutView.setPaymentInputValue(0);
        CheckoutModel.setPayment({
            amount,
            method: el.dataset.code,
            description: el.dataset.description ?? el.dataset.label ?? '',
        });
        CheckoutView.focusPaymentInput();

        PaymentSourceManager.invalidate();
    },

    setPaymentSource(el) {
        const code = el.dataset.code;
        const label = el.dataset.label ?? code;
        const description = el.dataset.description ?? '';
        const maxAmount = el.dataset.availableAmount === '' || el.dataset.availableAmount === undefined
            ? null
            : Number(el.dataset.availableAmount);

        if (!code) return;

        let amount = CheckoutView.getPaymentInputValue();
        if (amount === 0) {
            amount = Math.max(0, CheckoutModel.getOutstandingBalance());
        }

        CheckoutView.setPaymentInputValue(0);
        CheckoutModel.setPaymentSource({
            code,
            label,
            description,
            amount,
            maxAmount,
            status: el.dataset.status,
        });

        EventManager.emit('checkout:payment-source:selected', {code, amount});
    },

    /**
     * Build the checkout context shared with the backend when
     * discovering Payment Sources.
     */
    buildSourceContext() {
        const state = CheckoutModel.getState();
        const document = CheckoutModel.getDocument();
        return {
            total: state.total,
            remaining: state.pendingAmount,
            customerCode: document.customerCode || AppSettings.customer?.codcliente || null,
            currency: AppSettings.currency?.divisa ?? null,
            documentType: document.type || null,
            codserie: document.series || null,
            terminal: AppSettings.terminal ?? null,
            idpausada: AppSettings.document?.draftDocument ?? null,
            payments: state.payments ?? [],
        };
    },

    /**
     * Render the latest Payment Sources snapshot into the modal mount.
     * Empty list renders the empty-state message from the bundled twig
     * data attributes.
     */
    renderSources(sources) {
        const mount = document.getElementById(this.mountId);
        if (!mount) return;

        const section = mount.closest('.payment-sources-section');
        const texts = {
            loading: section?.dataset.loadingText ?? '',
            error: section?.dataset.errorText ?? '',
            retry: section?.dataset.retryText ?? '',
            empty: section?.dataset.emptyText ?? '',
            available: section?.dataset.availableText ?? '',
        };

        const status = sources.length > 0 ? 'loaded' : 'empty';
        let currencyFormatter = null;
        try {
            currencyFormatter = new Intl.NumberFormat(document.documentElement.lang || undefined, {
                style: 'currency',
                currency: AppSettings.currency?.divisa,
            });
        } catch (error) {
            // Keep the source usable when the configured currency is not an ISO code.
        }

        const viewSources = sources.map(source => ({
            ...source,
            availableText: source.available && source.available_amount !== null
                ? `${texts.available}: ${currencyFormatter
                    ? currencyFormatter.format(source.available_amount)
                    : source.available_amount}`
                : '',
        }));

        templates.render('payment-sources-list', {
            sources: viewSources,
            status,
            texts,
        }, mount);

        this.markSelectedSources();
    },

    markSelectedSources() {
        const appliedCodes = new Set(CheckoutModel.getState().paymentSources
            .map(source => source.code));

        document.querySelectorAll('[data-payment-source-check]').forEach(icon => {
            const button = icon.closest('[data-payment-kind="source"]');
            if (!button) return;
            const selected = appliedCodes.has(button.dataset.code);
            button.setAttribute('aria-pressed', String(selected));
            icon.hidden = !selected;
            button.classList.toggle('border-blue-500', selected);
            button.classList.toggle('bg-blue-50', selected);
        });
    },

    computeContextSignature(context) {
        if (!context) return '';
        return JSON.stringify({
            total: Math.round((Number(context.total) || 0) * 1000),
            remaining: Math.round((Number(context.remaining) || 0) * 1000),
            customer: context.customerCode ?? null,
            documentType: context.documentType ?? null,
            codserie: context.codserie ?? null,
            payments: (context.payments || []).length,
        });
    },

    /**
     * Ask the backend for Payment Sources. Triggered exclusively when
     * the user expands "Más formas de pago". Cached while the signature
     * stays identical; invalidated when context changes (customer,
     * total, payments...).
     */
    async loadPaymentSources({force = false} = {}) {
        const context = this.buildSourceContext();
        const signature = this.computeContextSignature(context);

        if (!force && this.lastContextSignature === signature && PaymentSourceManager.state === 'loaded') {
            this.renderSources(PaymentSourceManager.sources);
            return PaymentSourceManager.sources;
        }

        this.lastContextSignature = signature;

        try {
            const sources = await PaymentSourceManager.load(context);
            this.renderSources(sources);
            return sources;
        } catch (error) {
            const mount = document.getElementById(this.mountId);
            if (mount) {
                this.renderSources([]);
            }
            return [];
        }
    },

    showCheckoutModal() {
        CheckoutView.showPaymentModal();
        CheckoutModel.updateCheckoutEvent();
        CheckoutView.render(CheckoutModel);
        CheckoutView.focusPaymentInput();
    },

    hideCheckoutModal() {
        CheckoutView.hidePaymentModal();
        PaymentSourceManager.invalidate();
        this.lastContextSignature = '';
    },

    getState() {
        return CheckoutModel.getState();
    },

    handleMoreMethods(el) {
        CheckoutView.toggleMoreMethods();
        this.loadPaymentSources();
    },

    handleRetry() {
        PaymentSourceManager.invalidate();
        this.lastContextSignature = '';
        this.loadPaymentSources({force: true});
    },

    init() {
        dispatcher.register('checkout:payment:delete', this.deletePayment.bind(this));
        dispatcher.register('checkout:payment:recalc', this.recalculatePayment);
        dispatcher.register('checkout:payment:add', this.setPayment.bind(this));
        dispatcher.register('checkout:payment-source:add', this.setPaymentSource.bind(this));
        dispatcher.register('checkout:payment:more', this.handleMoreMethods.bind(this));
        dispatcher.register('checkout:payment-sources:retry', this.handleRetry.bind(this));
        dispatcher.register('checkout:show', this.showCheckoutModal.bind(this));
        dispatcher.register('checkout:hide', this.hideCheckoutModal.bind(this));

        EventManager.on('keyboard:checkout:show', this.showCheckoutModal);
        EventManager.on('checkout:processing', processing => {
            CheckoutView.setProcessing(processing);
            if (CheckoutView.isPaymentModalVisible()) {
                CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
            }
        });

        EventManager.on('checkout:update', () => {
            if (CheckoutView.isPaymentModalVisible()) {
                CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
            }
        });

        EventManager.on('event:cart:updated', ({doc}) => {
            CheckoutModel.updateDocument(doc);
            CheckoutView.updateTitle(doc.title);
            PaymentSourceManager.invalidate();
            this.lastContextSignature = '';
        });

        EventManager.on('event:customer:changed', () => {
            PaymentSourceManager.invalidate();
            this.lastContextSignature = '';
        });

        EventManager.on('event:order:completed', () => {
            if (CheckoutView.isPaymentModalVisible()) {
                CheckoutView.hidePaymentModal();
            }
            CheckoutView.setPaymentInputValue(0);
            CheckoutModel.clear();
            PaymentSourceManager.invalidate();
            this.lastContextSignature = '';
        });

        const input = CheckoutView.getPaymentInput();
        if (input && !this.inputHandler) {
            this.inputHandler = () => CheckoutView.render(CheckoutModel, CheckoutView.getPaymentInputValue());
            input.addEventListener('input', this.inputHandler);
        }
    },
};

export default CheckoutController;
