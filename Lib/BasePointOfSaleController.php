<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\Controller;
use FacturaScripts\Core\Response;
use FacturaScripts\Core\Template\ExtensionsTrait;

/**
 * Base controller for Point of Sale operations with service injection.
 */
abstract class BasePointOfSaleController extends Controller
{
    use ExtensionsTrait;
    use PointOfSaleDataAccessTrait;

    protected PointOfSaleSession $session;
    protected PointOfSaleValidator $validator;
    protected PointOfSaleResponseBuilder $responseBuilder;
    protected PointOfSaleHookManager $hookManager;

    /**
     * Initializes services for Point of Sale operations.
     */
    protected function setupServices(): void
    {
        $this->validator = new PointOfSaleValidator($this);
        $this->responseBuilder = new PointOfSaleResponseBuilder($this);
        $this->hookManager = new PointOfSaleHookManager();
    }

    /**
     * Public accessor for the response object.
     * Allows services to access the response even if extensions modify it.
     */
    public function getResponseObject(): Response
    {
        return $this->response;
    }

    /**
     * Sets a new token and updates the response builder.
     */
    protected function setNewToken(): void
    {
        $token = $this->multiRequestProtection->newToken();
        $this->responseBuilder->setToken($token);
    }

    /**
     * Validates request and builds response on failure.
     * Always generates a new token on successful validation.
     */
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

    /**
     * Validates delete permission.
     */
    protected function validateDelete(): bool
    {
        return $this->validator->validateDelete();
    }

    /**
     * Validates form token.
     */
    protected function validateFormToken(): bool
    {
        return parent::validateFormToken();
    }

    /**
     * Validates POS settings configuration.
     */
    public function validateSettings(): bool
    {
        return $this->validator->validateSettings(
            $this->getPaymentMethods(),
            $this->getCashPaymentMethod(),
            $this->getDefaultDocument()->tipodoc,
            $this->getDenominations()
        );
    }

    /**
     * Builds the complete response including messages and token.
     */
    protected function buildResponse(array $data = []): void
    {
        $this->responseBuilder->buildResponse($data);
    }

    /**
     * Sets a success response with data.
     */
    protected function setSuccessResponse(array $data = []): void
    {
        $this->responseBuilder->setSuccessResponse($data);
    }

    /**
     * Adds data to the response.
     */
    protected function addResponseData(array $data = []): void
    {
        $this->responseBuilder->addResponseData($data);
    }

    /**
     * Sends a response to the client.
     */
    protected function setResponse($content, bool $encode = true): void
    {
        $this->responseBuilder->setResponse($content, $encode);
    }

    /**
     * Gets custom document fields for a specific hook.
     */
    public function getCustomDocumentFields(string $hook): array
    {
        return $this->hookManager->getCustomDocumentFields($hook);
    }

    /**
     * Gets custom menu elements for a specific hook.
     */
    public function getCustomMenuElements(string $hook): array
    {
        return $this->hookManager->getCustomMenuElements($hook);
    }

    /**
     * Gets hook actions for a specific hook.
     */
    protected function getHookActions(string $hook): array
    {
        return $this->hookManager->getHookActions($hook);
    }

    /**
     * Gets actions for sale ticket printing.
     */
    public function getPrintSaleTicketActions(): array
    {
        return $this->hookManager->getPrintSaleTicketActions();
    }

    /**
     * Gets actions for draft ticket printing.
     */
    public function getPrintDraftTicketActions(): array
    {
        return $this->hookManager->getPrintDraftTicketActions();
    }

    /**
     * Gets actions for closing ticket printing.
     */
    public function getPrintClosingTicketActions(): array
    {
        return $this->hookManager->getPrintClosingTicketActions();
    }

    /**
     * Adds a custom field to document view.
     */
    protected function addCustomDocumentField(string $hook, array $element): void
    {
        $this->hookManager->addCustomDocumentField($hook, $element);
    }

    /**
     * Adds a custom menu element.
     */
    protected function addCustomMenuElement(string $hook, array $element): void
    {
        $this->hookManager->addCustomMenuElement($hook, $element);
    }

    /**
     * Adds a hook action.
     */
    public function addHookAction(string $hook, array $action): void
    {
        $this->hookManager->addHookAction($hook, $action);
    }

    /**
     * Loads custom document fields from extensions.
     */
    protected function loadCustomDocumentFields(): void
    {
        $this->pipe('loadCustomDocumentFields');
    }

    /**
     * Loads custom menu elements from extensions.
     */
    protected function loadCustomMenuElements(): void
    {
        $this->pipe('loadCustomMenuElements');
    }

    /**
     * Loads Point of Sale hooks from extensions.
     */
    protected function loadPointOfSaleHooks(): void
    {
        $this->pipe('loadPointOfSaleHooks');
    }
}
