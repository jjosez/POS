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
    }

    clear() {
        this.change = 0;
        this.payments = [];
        this.updateCheckoutEvent();
    }

    getState() {
        return {
            total: this.total,
            change: this.change,
            payments: this.payments,
            paymentsTotal: this.getPaymentsTotal(),
            outstandingBalance: this.getOutstandingBalance()
        };
    }

    getOutstandingBalance() {
        return normalizeAmount(this.total - this.getPaymentsTotal());
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

        // Intentar sumar al método existente
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
        eventManager.emit('checkout:update');
    }
}

const model = new CheckoutModel({cashMethod: AppSettings.cash});
export default model;
