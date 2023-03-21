class CheckoutClass {
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

    deletePayment(index) {
        this.payments.splice(index, 1);
        this.updateMoneyChange();
        this.updateCheckoutEvent();
    }

    setPayment({amount, method, description}) {
        let balance = this.getOutstandingBalance();
        amount = parseFloat(amount);

        if (method !== this.cashMethod) {
            if (balance < 0 && amount < 0) {
                amount = 0;
                return;
            }

            if (amount > balance) {
                amount = balance;
            }
        }

        if (false === this.payments.some(element => {
            if (element.method === method) {
                element.amount += amount;
                return true;
            }
            return false;
        }) && amount !== 0) {
            this.payments.push({amount: amount, method: method, description: description, change: 0});
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

    updateTotal(total = 0) {
        this.total = total;
        this.clear();
    }

    updateCheckoutEvent() {
        document.dispatchEvent(new Event('onCheckoutUpdate'));
    }
}

export default CheckoutClass;
