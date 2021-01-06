import * as POS from "./ShoppingCartTools";
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
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

function getPaymentAmount(method) {
    let total = 0;

    payments.forEach(element => function () {
        if (element.method === method) {
            total += element.amount;
        }
    });

    return total;
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