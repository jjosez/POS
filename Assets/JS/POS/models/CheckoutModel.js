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
        this.paymentPolicy = AppSettings.document.payment_policy ?? 'required';
        this.customerCode = AppSettings.customer.codcliente;
        this.accountResult = null;
        this.accountMessage = '';
        this.accountChecking = false;
        this.accountRevision = 0;
    }

    clear() {
        this.change = 0;
        this.payments = [];
        this.updateCheckoutEvent();
    }

    getState() {
        const accountAmount = this.getCustomerAccountAmount();

        return {
            total: this.total,
            change: this.change,
            payments: this.payments,
            paymentsTotal: this.getPaymentsTotal(),
            paymentPolicy: this.paymentPolicy,
            collectedAmount: this.getCollectedAmount(),
            customerAccountAmount: accountAmount,
            settledAmount: this.getSettledAmount(),
            pendingAmount: this.getPendingAmount(),
            outstandingBalance: this.getOutstandingBalance(),

            accountResult: this.accountResult,
            accountMessage: this.accountMessage,
            accountChecking: this.accountChecking,

            canFinalize: this.total > 0
                && (
                    this.paymentPolicy === 'optional'
                    || this.getPendingAmount() === 0
                )
                && (
                    accountAmount === 0
                    || (
                        !this.accountChecking
                        && this.accountResult?.customer_account === true
                    )
                )
        };
    }

    getOutstandingBalance() {
        return normalizeAmount(this.total - this.getPaymentsTotal());
    }

    getCollectedAmount() {
        return normalizeAmount(this.payments.reduce((sum, payment) => sum + payment.amount - payment.change, 0));
    }

    getCustomerAccountAmount() {
        return this.paymentPolicy === 'customer-account'
            ? normalizeAmount(Math.max(0, this.total - this.getCollectedAmount()))
            : 0;
    }

    getSettledAmount() {
        return normalizeAmount(this.getCollectedAmount() + this.getCustomerAccountAmount());
    }

    getPendingAmount() {
        return normalizeAmount(this.total - this.getSettledAmount());
    }

    requiresCustomer() {
        return this.getCustomerAccountAmount() > 0
            && (!this.customerCode || String(this.customerCode) === String(AppSettings.customer.codcliente));
    }

    updateDocument(doc) {
        const config = AppSettings['supported-documents']?.find(item =>
            String(item.tipodoc) ===
            String(doc['tipo-documento'] ?? doc.generadocumento)
            && String(item.codserie) === String(doc.codserie)
        );

        this.paymentPolicy = config?.payment_policy ?? 'required';
        this.customerCode = doc.codcliente;

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
                is_cash: isCashMethod
            });
        }

        this.updateMoneyChange();
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
        this.accountResult = null;
        this.accountMessage = '';
        this.accountChecking = false;
        this.accountRevision++;

        eventManager.emit('checkout:update');
    }

    beginAccountCheck(revision) {
        if (revision !== this.accountRevision) return;

        this.accountChecking = true;
        this.accountResult = null;
        this.accountMessage = '';

        eventManager.emit('checkout:account:updated');
    }

    setAccountResult(result, revision, message = '') {
        if (revision !== this.accountRevision) return;

        this.accountResult = result;
        this.accountMessage = message || result?.message || '';
        this.accountChecking = false;

        eventManager.emit('checkout:account:updated');
    }
}

const model = new CheckoutModel({cashMethod: AppSettings.cash});
export default model;
