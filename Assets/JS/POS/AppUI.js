/*
 * This file is part of POS plugin for FacturaScripts
 * Copyright (c) 2021.  Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
import {getElement} from "./AppCore.js";

export const Checkout = {
    holdButton: document.getElementById('button-order-hold'),
    saveButton: document.getElementById('button-checkout-save'),
    inputAmount: document.getElementById('paymentAmount'),
    selectMethod: document.getElementById('paymentMethod'),
    textChange: document.getElementById('paymentReturn'),
    textReceived: document.getElementById('paymentOnHand')
}

export const barcodeInput = getElement("productBarcodeInput");

export const holdOrderButton = getElement('button-order-hold');
export const saveOrderButton = getElement('button-checkout-save');
export const paymentAmountInput = getElement('paymentAmount');
export const paymentMethodSelect = getElement('paymentMethod');

export const customerNameInput = getElement('customerSearchBox');
export const searchCustomerInput = getElement('customerSerachInput');
export const saveCustomerButton = getElement('save-customer-button');

export const searchProductInput = getElement('productSerachInput');

export const saveCashupButton = getElement('saveCashupButton');

