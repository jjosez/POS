/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

import EventManager from '../core/EventManager.js';

/**
 * Discovers Payment Sources dynamically when the checkout requests it.
 *
 * The frontend does NOT cache a catalog. The backend (POS controller)
 * owns the registry of providers contributed by extensions. Every time
 * the user expands "Más formas de pago" or the checkout context changes,
 * this manager fires a discovery request and renders the response.
 */
const PaymentSourceManager = {
    state: 'idle',
    sources: [],
    loading: false,
    request: null,

    invalidate() {
        this.sources = [];
        this.state = 'idle';
    },

    async load(context = {}) {
        if (this.loading) {
            return this.request;
        }

        this.state = 'loading';
        this.loading = true;

        this.request = fetch('POS?action=checkout:payment-sources:get', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(context),
        })
            .then(this.handleResponse)
            .then(data => {
                this.sources = Array.isArray(data?.sources) ? data.sources : [];
                this.state = 'loaded';
                EventManager.emit('checkout:payment-sources:loaded', {sources: this.sources});
                return this.sources;
            })
            .catch(error => {
                this.state = 'error';
                EventManager.emit('checkout:payment-sources:error', {error});
                throw error;
            })
            .finally(() => {
                this.loading = false;
                this.request = null;
            });

        return this.request;
    },

    async handleResponse(response) {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        return response.json();
    },
};

export default PaymentSourceManager;
