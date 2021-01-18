/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
export default class Checkout {
    constructor(total = 0, cashMethod = "") {
        this.cashMethod = cashMethod;
        this.change = 0;
        this.total = total;
        this.payments = [];
        this.payment = 0;
    }

    recalculatePayment(amount, method) {
        this.change = (amount - this.total) || 0;
        this.payment = amount;

        if (method !== this.cashMethod) {
            if (this.change > 0) {
                this.change = 0;
                this.payment = this.total;
            }
        }

        return this.change;
    }

    setPayment(amount, method) {
        this.change = (amount - this.total) || 0;
        if (method !== this.cashMethod) {
            if (this.change > 0) {
                this.change = 0;
                amount = this.total;
            }
        }

        this.payments.push({amount: amount, method:method});
        return this.change;
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

        this.payments.forEach(element => function () {
            total += element.amount;
        });

        return total;
    }
}