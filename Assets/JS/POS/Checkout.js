export default class Checkout {
    constructor(cashMethod = "", total = 0) {
        this.cashMethod = cashMethod;
        this.change = 0;
        this.total = total;
        this.payments = [];
    }

    clear() {
        this.change = 0;
        this.payments = [];
        this.total = 0;

        this.updateCheckoutEvent();
    }

    getOutstandingBalance() {
        return this.total - this.getPaymentsTotal();
    }

    getPaymentAmount(method) {
        let total = 0;

        this.payments.forEach(element => function () {
            if (element.method === method) {
                total += element.amount;
            }
        });

        return total;
    }

    getPaymentsTotal() {
        let total = 0;

        this.payments.forEach(element => {
            total += parseFloat(element.amount);
        });

        return total;
    }

    setPayment(amount, method) {
        let balance = this.getOutstandingBalance();
        amount = parseFloat(amount);

        if (method !== this.cashMethod) {
            amount = (balance < 0) ? 0 : balance;
        }

        if (false === this.payments.some(element => {
            if (element.method === method) {
                element.amount += amount;
                return true;
            }
            return false;
        }) && amount > 0) {
            this.payments.push({amount: amount, method: method, change: 0});
        }

        this.updateMoneyChange();
        this.updateCheckoutEvent();
    }

    updateMoneyChange() {
        this.change = (this.getPaymentsTotal() - this.total).toFixed(2) || 0;

        this.payments.find(payment => {
            if (payment.method === this.cashMethod) {
                payment.change = this.change;
            }
        });
    }

    updateTotal(total) {
        this.total = total;
    }

    updateCheckoutEvent() {
        document.dispatchEvent(new Event('updateCheckout'));
    }
}
