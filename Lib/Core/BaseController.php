<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2025 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Core;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ExtensionsTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Lib\Forms\FormManager;
use FacturaScripts\Plugins\POS\Lib\Hooks\Hook;
use FacturaScripts\Plugins\POS\Lib\Hooks\HookManager;
use FacturaScripts\Plugins\POS\Lib\Http\ResponseBuilder;
use FacturaScripts\Plugins\POS\Lib\Http\Validator;

/**
 * Base controller for Point of Sale operations.
 * Provides unified access to POS services through Context with lazy loading.
 */
abstract class BaseController extends Controller
{
    use ExtensionsTrait;

    protected SessionManager $session;
    protected Context $context;
    protected Validator $validator;
    protected ResponseBuilder $responseBuilder;
    protected HookManager $hookManager;

    /**
     * Initializes minimal services.
     */
    protected function setupServices(): void
    {
        $this->validator = new Validator($this);
        $this->responseBuilder = new ResponseBuilder($this);
        $this->hookManager = new HookManager();
    }

    /**
     * Initializes context with lazy-loaded services.
     * Must be called after session is initialized.
     */
    protected function setupContext(): void
    {
        $this->context = new Context(
            $this->session->getSession(),
            $this->session->getTerminal()
        );
    }

    /**
     * Returns the Context instance for accessing POS services.
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    // ========================================================================
    // Response Management
    // ========================================================================

    public function getResponseObject(): Response
    {
        return $this->response;
    }

    protected function setNewToken(): void
    {
        $token = $this->multiRequestProtection->newToken();
        $this->responseBuilder->setToken($token);
    }

    protected function buildResponse(array $data = []): void
    {
        $this->responseBuilder->buildResponse($data);
    }

    protected function setSuccessResponse(array $data = []): void
    {
        $this->responseBuilder->setSuccessResponse($data);
    }

    protected function setErrorResponse(array $data = []): void
    {
        $this->responseBuilder->setErrorResponse($data);
    }

    protected function addResponseData(array $data = []): void
    {
        $this->responseBuilder->addResponseData($data);
    }

    protected function setResponse($content, bool $encode = true): void
    {
        $this->responseBuilder->setResponse($content, $encode);
    }

    /**
     * Adds a message directly to the response without saving to database.
     *
     * @param string $message The message text or translation key
     * @param string $type Message type: 'info', 'success', 'warning', 'error'
     */
    protected function addMessage(string $message, string $type = 'info', array $params = []): void
    {
        $message = Tools::lang()->trans($message, $params);
        $this->responseBuilder->addMessage($message, $type);
    }

    // ========================================================================
    // Validation
    // ========================================================================

    protected function validateRequest(): bool
    {
        if (!$this->validator->validateRequest()) {
            $this->setNewToken();
            $this->buildResponse();
            return false;
        }

        $this->setNewToken();
        return true;
    }

    protected function validateDelete(): bool
    {
        return $this->validator->validateDelete();
    }

    protected function validateFormToken(): bool
    {
        return parent::validateFormToken();
    }

    public function validateSettings(): bool
    {
        return $this->validator->validateSettings(
            $this->context->config()->getPaymentMethods(),
            $this->context->config()->getCashPaymentMethod(),
            $this->context->config()->getDefaultDocument()->tipodoc,
            $this->context->config()->getDenominations()
        );
    }

    // ========================================================================
    // Hook Management
    // ========================================================================

    public function getCustomDocumentFields(string $hook): array
    {
        return $this->hookManager->getCustomDocumentFields($hook);
    }

    public function getCustomMenuElements(string $hook): array
    {
        return $this->hookManager->getCustomMenuElements($hook);
    }

    protected function getHookActions(string $hook): array
    {
        return $this->hookManager->getHookActions($hook);
    }

    public function getPrintSaleTicketActions(): array
    {
        return $this->hookManager->getPrintSaleTicketActions();
    }

    public function getPrintDraftTicketActions(): array
    {
        return $this->hookManager->getPrintDraftTicketActions();
    }

    public function getPrintClosingTicketActions(): array
    {
        return $this->hookManager->getPrintClosingTicketActions();
    }

    public function addHookAction(Hook $hook, array $action): void
    {
        $this->hookManager->addHookAction($hook, $action);
    }

    protected function loadCustomDocumentFields(): void
    {
        $this->pipe('loadCustomDocumentFields');
    }

    protected function loadCustomMenuElements(): void
    {
        $this->pipe('loadCustomMenuElements');
    }

    protected function loadPointOfSaleHooks(): void
    {
        $this->pipe('loadPointOfSaleHooks');
    }

    // ========================================================================
    // Form Management
    // ========================================================================

    public function getFieldOptions(): array
    {
        return FormManager::getFormsGrid($this->user->nick) ?? [];
    }

    public function getCartColumnCount(): int
    {
        return FormManager::getCartColumnCount($this->user->nick);
    }

    // ========================================================================
    // Utility Methods
    // ========================================================================

    public function getCustomButtons(): array
    {
        return [];
    }

    public function getNewToken(): string
    {
        return $this->multiRequestProtection->newToken();
    }
}
