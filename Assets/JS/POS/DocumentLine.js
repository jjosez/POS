/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
export function DocumentLine(args = {}) {
    let line = new Object(args);

    line.pvptotaliva = formatPrice(line.pvptotal * (1 + line.iva / 100));
    line.pvpunitarioiva = line.pvptotaliva / line.cantidad;
    return line;
}

function formatPrice(amount, factor = 2) {
    return parseFloat(amount).toFixed(factor);
}

