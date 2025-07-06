import EventManager from "../core/EventManager.js";

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
        return this.total - this.getPaymentsTotal();
    }

    getPaymentAmount(method) {
        return this.payments.reduce((sum, element) => {
            if (element.method === method) {
                return sum + parseFloat(element.amount);
            }
            return sum;
        }, 0);
    }

    getPaymentsTotal() {
        return this.payments.reduce((sum, element) => {
            return sum + parseFloat(element.amount);
        }, 0);
    }

    deletePayment(index) {
        this.payments.splice(index, 1);
        this.updateMoneyChange();
        this.updateCheckoutEvent();
    }

    setPayment({amount, method, description}) {
        let balance = this.getOutstandingBalance();
        let isCashMethod = (method === this.cashMethod);

        amount = parseFloat(amount);

        if (!isCashMethod) {
            if (balance < 0 && amount < 0) {
                amount = 0;
                return;
            }

            if (amount > balance) {
                amount = balance;
            }
        }

        // Intentar sumar al método existente
        const existing = this.payments.find(p => p.method === method);
        if (existing) {
            existing.amount += amount;
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
        const changeValue = (this.getPaymentsTotal() - this.total).toFixed(2);
        this.change = parseFloat(changeValue) || 0;

        this.payments.forEach(payment => {
            if (payment.method === this.cashMethod) {
                payment.change = this.change;
            }
        });
    }

    updateTotal(total = 0) {
        this.total = total;
        this.clear();
    }

    updateCheckoutEvent() {
        EventManager.emit('onCheckoutUpdate');
    }
}

const model = new CheckoutModel({cashMethod: AppSettings.cash});
export default model;
