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

export const mainForm = getElement("mainOrderForm");
export const closingForm = getElement("closeSessionForm");
export const closeSessionButton = getElement('closeSessionButton');

export const orderHoldButton = getElement('orderHoldButton');
export const orderSaveButton = getElement('orderSaveButton');
export const paymentAmountInput = getElement('paymentAmount');
export const paymentMethodSelect = getElement('paymentMethod');
export const checkoutChangeDisplay = getElement('paymentReturn');
export const checkoutReceivedDisplay = getElement('paymentOnHand');

export const cartProductsList = getElement('cartContainer');

export const customerSearch = getElement('customerSearch');
export const customerSearchBox = getElement('customerSearchBox');
export const customerSearchResult = getElement('customerSearchResult');
export const customerSaveButton = getElement('newCustomerSaveButton');

export const productSearch = getElement('productSearch');
export const productSearchBox = getElement('productSearchBox');
export const productSearchResult = getElement('productSearchResult');
export const productBarcodeBox = getElement("productBarcodeBox");


