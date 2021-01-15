/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2021 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
export default class Checkout {
    constructor(total = 0, cashMethod = "") {
        this.cashMethod = cashMethod;
        this.total = total;
        this.payments = [];
    }

    setPayment(amount, method) {
        let change = (amount - this.total) || 0;
        if (method !== cashMethod) {
            if (change > 0) {
                change = 0;
                amount = this.total;
            }
        }

        this.payments.push({amount: amount, method:method});
        return change;
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
}


export var payments = [];
export var total = 0;
export var cashMethod = false;

export function setPayment(amount, method) {
    if (false === isSetCashMethod() || false === isSetTotal()) {
        return false;
    }

    let change = (amount - total) || 0;
    if (method !== cashMethod) {
        if (change > 0) {
            change = 0;
            amount = total;
        }
    }

    payments.push({amount: amount, method:method});
    return change;
}


function getPaymentsTotal() {
    let total = 0;

    payments.forEach(element => function () {
        total += element.amount;
    });

    return total;
}

/*function getPaymentsTotal() {
    $scope.sum = function(items, prop){
        return items.reduce( function(a, b){
            return a + b[prop];
        }, 0);
    };

    $scope.travelerTotal = $scope.sum($scope.traveler, 'Amount');
}*/

function isSetCashMethod() {
    return false !== cashMethod;
}

function isSetTotal() {
    return total > 0;
}