/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2018-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
import CartController from "./controllers/CartController.js";
import CheckoutController from "./controllers/CheckoutController.js";
import CustomerController from "./controllers/CustomerController.js";
import KeyboardController from "./controllers/KeyboardController.js";
import OrderController from "./controllers/OrderController.js";
import PrintController from "./controllers/PrintController.js";
import ProductController from "./controllers/ProductController.js";
import SessionController from "./controllers/SessionController.js";
import eventDispatcher from "./core/EventDispatcher.js";
import "./View.js";

document.addEventListener("DOMContentLoaded", () => {
    /* global onScan */
    onScan.attachTo(document);

    SessionController.init();
    CheckoutController.init();
    CartController.init();
    CustomerController.init();
    KeyboardController.init();
    OrderController.init();
    PrintController.init();
    ProductController.init();

    eventDispatcher.listen();
});
