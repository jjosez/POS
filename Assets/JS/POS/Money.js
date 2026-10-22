/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
const ROUND_RATE = 100000;
const DECIMALS_RATE = parseInt(AppSettings.currency.decimals);
const CURRENCY_RATE = 10 ** DECIMALS_RATE;

export function priceWithTax(base, rate) {
    const subtotal = base * (1 + rate / 100);
    return roundFixed(subtotal);
}

export function priceWithoutTax(base, {iva}) {
    const price = roundLinePrice(Number(base) || 0);
    const taxRate = Number(iva) || 0;
    return price / (1 + taxRate / 100);
}

export function roundLinePrice(amount, config = AppSettings.lineRounding || {}) {
    const number = Number(amount) || 0;
    const mode = config.mode || 'none';
    const decimals = Math.max(0, Math.min(6, Number.parseInt(config.decimals ?? 2, 10)));
    const factor = 10 ** decimals;

    if (mode === 'nearest') {
        return Math.round((number + Number.EPSILON) * factor) / factor;
    }

    if (mode === 'up') {
        return Math.ceil(number * factor - 1e-9) / factor;
    }

    if (mode === 'precision') {
        const candidate = Math.round((number + Number.EPSILON) * factor) / factor;
        const tolerance = 10 ** (-decimals - 3);
        return Math.abs(number - candidate) <= tolerance ? candidate : number;
    }

    return number;
}

export function roundDecimals(amount) {
    return Math.round(amount * ROUND_RATE) / ROUND_RATE;
}

export function roundCurrency(amount) {
    const number = Number(amount) || 0;
    const rounded = Math.round((Math.abs(number) + Number.EPSILON) * CURRENCY_RATE) / CURRENCY_RATE;
    return Math.sign(number) * rounded;
}

export function roundFixed(amount) {
    return roundCurrency(amount).toFixed(DECIMALS_RATE);
}
