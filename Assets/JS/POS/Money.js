/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
const ROUND_RATE = 100000;
const DECIMALS_RATE = parseInt(AppSettings.currency.decimals);

export function priceWithTax(base, rate) {
    const subtotal = base * (1 + rate / 100);
    return roundFixed(subtotal);
}

export function priceWithoutTax(base, {cantidad, iva}) {
    const subtotal = base * cantidad;
    const totalWithTaxes = subtotal / (1 + iva / 100);
    return roundDecimals(totalWithTaxes / cantidad);
}

export function roundDecimals(amount) {
    return Math.round(amount * ROUND_RATE) / ROUND_RATE;
}

export function roundFixed(amount) {
    return roundDecimals(amount || 0).toFixed(DECIMALS_RATE);
}
