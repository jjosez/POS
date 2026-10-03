import eventManager from "../core/EventManager.js";

const configuredDecimals = parseInt(AppSettings.currency.decimals);
const CURRENCY_DECIMALS = Number.isInteger(configuredDecimals) ? configuredDecimals : 2;
const normalizeAmount = amount => parseFloat((parseFloat(amount) || 0).toFixed(CURRENCY_DECIMALS));

class CheckoutModel {
    constructor({cashMethod = ""}) {
        this.cashMethod = cashMethod;
        this.change = 0;
        this.total = 0;
        this.payments = [];
        this.paymentSources = [];
        this.paymentPolicy = AppSettings.document.payment_policy ?? 'required';
        this.documentType = '';
        this.documentSeries = '';
    }

    clear() {
        this.change = 0;
        this.payments = [];
        this.paymentSources = [];
        this.documentType = '';
        this.documentSeries = '';
        this.updateCheckoutEvent();
    }

    getDocument() {
        return {
            type: this.documentType,
            series: this.documentSeries,
        };
    }

    getState() {
        return {
            total: this.total,
            change: this.change,
            payments: this.payments,
            paymentSources: this.paymentSources,
            paymentsTotal: this.getPaymentsTotal(),
            sourcesTotal: this.getSourcesTotal(),
            paymentPolicy: this.paymentPolicy,
            collectedAmount: this.getCollectedAmount(),
            sourcesCoveredAmount: this.getSourcesCoveredAmount(),
            settledAmount: this.getSettledAmount(),
            pendingAmount: this.getPendingAmount(),
            outstandingBalance: this.getOutstandingBalance(),
            canFinalize: this.total > 0
                && (
                    this.paymentPolicy === 'optional'
                    || this.getPendingAmount() === 0
                )
        };
    }

    getOutstandingBalance() {
        return normalizeAmount(this.total - this.getPaymentsTotal() - this.getSourcesTotal());
    }

    getCollectedAmount() {
        const nativeCollected = this.payments.reduce((sum, payment) => sum + payment.amount - payment.change, 0);
        return normalizeAmount(nativeCollected);
    }

    getSourcesTotal() {
        return normalizeAmount(this.paymentSources.reduce((sum, payment) => sum + payment.amount, 0));
    }

    getSourcesCoveredAmount() {
        return this.getSourcesTotal();
    }

    getSettledAmount() {
        return normalizeAmount(this.getCollectedAmount() + this.getSourcesCoveredAmount());
    }

    getPendingAmount() {
        return normalizeAmount(this.total - this.getSettledAmount());
    }

    updateDocument(doc) {
        const config = AppSettings['supported-documents']?.find(item =>
            String(item.tipodoc) ===
            String(doc['tipo-documento'] ?? doc.generadocumento)
            && String(item.codserie) === String(doc.codserie)
        );

        this.paymentPolicy = config?.payment_policy ?? 'required';
        this.documentType = String(doc['tipo-documento'] ?? doc.generadocumento ?? '');
        this.documentSeries = String(doc.codserie ?? '');

        const total = normalizeAmount(doc.total);

        if (this.total !== total) {
            this.updateTotal(total);
        } else {
            this.updateCheckoutEvent();
        }
    }

    getPaymentAmount(method) {
        return normalizeAmount(this.payments.reduce((sum, element) => {
            if (element.method === method) {
                return sum + parseFloat(element.amount);
            }
            return sum;
        }, 0));
    }

    getPaymentsTotal() {
        const total = this.payments.reduce((sum, element) => {
            return sum + parseFloat(element.amount);
        }, 0);
        return parseFloat(total.toFixed(CURRENCY_DECIMALS));
    }

    deletePayment(index) {
        if (!Number.isInteger(index) || index < 0 || index >= this.payments.length) return;

        this.payments.splice(index, 1);
        this.updateMoneyChange();
        this.updateCheckoutEvent();
    }

    deletePaymentSource(code) {
        this.paymentSources = this.paymentSources.filter(source => source.code !== code);
        this.updateCheckoutEvent();
    }

    setPayment({amount, method, description}) {
        const balance = Math.max(0, this.getOutstandingBalance());
        const isCashMethod = (method === this.cashMethod);

        amount = normalizeAmount(amount);
        if (!Number.isFinite(amount) || amount <= 0) return;

        if (!isCashMethod) {
            if (amount > balance) {
                amount = balance;
            }
        }

        if (amount <= 0) return;

        const existing = this.payments.find(p => p.method === method);
        if (existing) {
            existing.amount = parseFloat((existing.amount + amount).toFixed(CURRENCY_DECIMALS));
        } else if (amount !== 0) {
            this.payments.push({
                amount: amount,
                method: method,
                description: description,
                change: 0,
                is_cash: isCashMethod,
                kind: 'native'
            });
        }

        this.updateMoneyChange();
        this.updateCheckoutEvent();
    }

    setPaymentSource({code, label, amount, description, maxAmount, status}) {
        if (!code || !label) return;
        amount = normalizeAmount(amount);
        if (!Number.isFinite(amount) || amount <= 0) return;

        const balance = Math.max(0, this.getOutstandingBalance());
        if (amount > balance) {
            amount = balance;
        }
        if (amount <= 0) return;

        if (typeof maxAmount === 'number' && Number.isFinite(maxAmount) && amount > maxAmount) {
            amount = maxAmount;
        }

        const existing = this.paymentSources.find(source => source.code === code);
        if (existing) {
            existing.amount = parseFloat((existing.amount + amount).toFixed(CURRENCY_DECIMALS));
        } else {
            this.paymentSources.push({
                code,
                label,
                description: description ?? '',
                amount,
                status: status ?? 'AVAILABLE',
                kind: 'source'
            });
        }

        this.updateCheckoutEvent();
    }

    updateMoneyChange() {
        const changeValue = Math.max(0, this.getPaymentsTotal() - this.total).toFixed(CURRENCY_DECIMALS);
        this.change = parseFloat(changeValue) || 0;

        this.payments.forEach(payment => {
            if (payment.method === this.cashMethod) {
                payment.change = this.change;
            }
        });
    }

    updateTotal(total = 0) {
        const nextTotal = normalizeAmount(total);
        if (this.total === nextTotal) return;

        this.total = nextTotal;
        this.clear();
    }

    updateCheckoutEvent() {
        eventManager.emit('checkout:update');
    }
}

const model = new CheckoutModel({cashMethod: AppSettings.cash});
export default model;
