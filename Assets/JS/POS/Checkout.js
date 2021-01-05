/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
export var payments = [];

export function setPayment(amount, method) {
    payments.push({amount: amount, method:method});
}