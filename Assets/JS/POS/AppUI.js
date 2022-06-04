/*
 * This file is part of POSpro plugin for FacturaScripts
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
import {getElement} from "./Core.js";

export const toggleableElements = document.querySelectorAll('[data-toggle]');
export const alertsContainer = getElement("alert-container");
export const mainForm = getElement("mainOrderForm");

export const orderHoldButton = getElement('orderHoldButton');
export const orderSaveButton = getElement('orderSaveButton');
export const pausedOrdersList = getElement('pausedOrdersList');

export const customerNameLabel = getElement('customerNameLabel');
export const customerSearchBox = getElement('customerSearchBox');
export const customerSearchResult = getElement('customerSearchResult');
export const customerSaveButton = getElement('newCustomerSaveButton');
export const customerSearchModal = getElement('customerSearchModal');

export const productSearchBox = getElement('productSearchBox');
export const productSearchResult = getElement('productSearchResult');
export const productTagList = getElement('productTagList');

/* global Eta*/
const alertTemplate = Eta.compile(getElement('message-template').innerHTML);
const customerListTemplate = Eta.compile(getElement('customerListTemplate').innerHTML);
const pausedOrdersTemplate = Eta.compile(getElement('paused-orders-template').innerHTML);

export function updateCustomer(name) {
    customerNameLabel.textContent = name;
    toggleModal(customerSearchModal);
}

export function updateAlertList(data) {
    alertsContainer.innerHTML = alertTemplate(data, Eta.config);
}

export function updateCustomerListView(data) {
    customerSearchResult.innerHTML = customerListTemplate({items: data}, Eta.config);
}

export function updateProductListView(data) {
    productSearchResult.innerHTML = productListTemplate({items: data}, Eta.config);
}

export function updatePausedOrdersListView(data) {
    pausedOrdersList.innerHTML = pausedOrdersTemplate({items: data}, Eta.config);
}

export function toggleModal(element) {
    element.classList.toggle("flex");

    if (element.classList.toggle("hidden")) {
        document.querySelector('.modal-backdrop').remove();
    } else {
        let backdrop = document.createElement('div');
        backdrop.classList.add('modal-backdrop');
        document.querySelector('body').append(backdrop);
    }
}

export function toggle(target) {
    let element = getElement(target.dataset.target);

    element.classList.toggle('hidden');

    if(target.dataset.onhide) {
        getElement(target.dataset.onhide).classList.toggle('hidden');
    }
}
