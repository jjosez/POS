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

/**
 * Resolve the configured display decimals from AppSettings, with a safe fallback.
 *
 * @returns {number}
 */
function getDisplayDecimals() {
    const cfg = (typeof AppSettings !== 'undefined' && AppSettings.lineRounding) || {};
    return clampDecimals(cfg.display);
}

/**
 * Clamp display decimals into the 0..6 range accepted by the backend.
 *
 * @param {*} value
 * @returns {number}
 */
function clampDecimals(value) {
    const parsed = Number.parseInt(value ?? 2, 10);
    if (Number.isNaN(parsed)) {
        return 2;
    }
    return Math.max(0, Math.min(6, parsed));
}

/**
 * Format a monetary amount using the configured display decimals and currency
 * separators. Unlike roundLinePrice this does not modify the underlying
 * numeric value, it just controls how many decimals appear in the resulting
 * string. Pads trailing zeros when displayDecimals > stored precision, and
 * trims extra digits when displayDecimals < stored precision.
 *
 * @param {number} amount
 * @param {number|null} [decimalsOverride=null]
 * @returns {string}
 */
export function formatDisplayedPrice(amount, decimalsOverride = null) {
    const number = Number(amount) || 0;
    const decimals = decimalsOverride !== null ? clampDecimals(decimalsOverride) : getDisplayDecimals();
    const sign = number < 0 ? '-' : '';
    const fixed = Math.abs(number).toFixed(decimals);
    const [intPart = '0', fracPart = ''] = fixed.split('.');
    const decimalSeparator = String(
        AppSettings.currency?.decimalSeparator ?? AppSettings.currency?.separator ?? '.'
    );
    const configuredThousandsSeparator = String(AppSettings.currency?.thousandsSeparator ?? ' ');
    const thousandsSeparator = configuredThousandsSeparator === decimalSeparator
        ? ''
        : configuredThousandsSeparator;
    const integer = thousandsSeparator
        ? intPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator)
        : intPart;
    const fraction = decimals > 0 && fracPart ? decimalSeparator + fracPart : '';
    return sign + integer + fraction;
}

export function roundCurrency(amount) {
    const number = Number(amount) || 0;
    const rounded = Math.round((Math.abs(number) + Number.EPSILON) * CURRENCY_RATE) / CURRENCY_RATE;
    return Math.sign(number) * rounded;
}

export function roundFixed(amount) {
    return roundCurrency(amount).toFixed(DECIMALS_RATE);
}

export function formatNumber(amount, {minimumFractionDigits = DECIMALS_RATE} = {}) {
    const number = Number(amount) || 0;
    const minimum = Math.max(0, Math.min(DECIMALS_RATE, Number.parseInt(minimumFractionDigits, 10) || 0));
    const decimalSeparator = String(
        AppSettings.currency?.decimalSeparator ?? AppSettings.currency?.separator ?? '.'
    );
    const configuredThousandsSeparator = String(AppSettings.currency?.thousandsSeparator ?? ' ');
    const thousandsSeparator = configuredThousandsSeparator === decimalSeparator
        ? ''
        : configuredThousandsSeparator;
    const roundedNumber = roundCurrency(number);
    const absolute = Math.abs(roundedNumber).toFixed(DECIMALS_RATE);
    let [integer, fraction = ''] = absolute.split('.');

    while (fraction.length > minimum && fraction.endsWith('0')) {
        fraction = fraction.slice(0, -1);
    }

    if (thousandsSeparator) {
        integer = integer.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator);
    }

    const sign = roundedNumber < 0 ? '-' : '';
    return sign + integer + (fraction ? decimalSeparator + fraction : '');
}
