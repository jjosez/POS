<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Http;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Tools;

/**
 * Infrastructure Service for validating HTTP requests and permissions.
 * Handles request validation, token verification, and permission checks.
 */
class Validator
{
    private Controller $controller;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    /**
     * Validates if user has delete permissions.
     */
    public function validateDelete(): bool
    {
        if (false === $this->controller->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return false;
        }

        return true;
    }

    /**
     * Validates if user has update permissions.
     */
    public function validatePermissions(): bool
    {
        if (!$this->controller->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            return false;
        }
        return true;
    }

    /**
     * Validates the request token to prevent duplicate submissions.
     */
    public function validateToken(): bool
    {
        $token = $this->controller->request->get('token');

        if (empty($token) || !$this->controller->multiRequestProtection->validate($token)) {
            Tools::log()->warning('invalid-token');
            return false;
        }

        if ($this->controller->multiRequestProtection->tokenExist($token)) {
            Tools::log()->warning('duplicated-request');
            return false;
        }

        return true;
    }

    /**
     * Validates both permissions and token.
     */
    public function validateRequest(): bool
    {
        return $this->validatePermissions() && $this->validateToken();
    }

    /**
     * Validates Point of Sale settings configuration.
     */
    public function validateSettings(
        array $paymentMethods,
        string $cashPaymentMethod,
        string $defaultDocumentType,
        array $denominations
    ): bool {
        $isValid = true;

        $validations = [
            'no-payment-method-set' => empty($paymentMethods),
            'no-cash-payment-method-set' => trim($cashPaymentMethod) === '',
            'no-default-document-set' => empty($defaultDocumentType) === true,
            'no-currency-denominations' => empty($denominations)
        ];

        foreach ($validations as $message => $condition) {
            if ($condition) {
                Tools::log('POS')->warning($message);
                $isValid = false;
            }
        }

        return $isValid;
    }
}
