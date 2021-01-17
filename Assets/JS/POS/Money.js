/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
const ROUND_RATE = 1000;

export function priceWithTax(base, rate) {
    return roundDecimals(base * (1 + rate / 100));
}

export function priceWithoutTax(base, rate) {
    return roundDecimals(base / (1 + rate / 100));
}

export function roundDecimals(amount, decimals) {
    return Math.round(amount * ROUND_RATE) / ROUND_RATE;
}