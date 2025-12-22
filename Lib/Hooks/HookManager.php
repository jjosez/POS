<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Hooks;

/**
 * Infrastructure Service for managing hooks and extension points.
 * Handles custom document fields, menu elements, and hook actions.
 */
class HookManager
{
    private array $customDocumentFields;
    private array $customMenuElements;
    private array $hookActions = [];

    public function __construct()
    {
        $this->customDocumentFields = ['detail' => [], 'cart' => []];
        $this->customMenuElements = ['navbar' => [], 'content-navbar' => []];
    }

    /**
     * Adds a custom field to the document view based on a specified hook.
     */
    public function addCustomDocumentField(Hook $hook, array $element): void
    {
        $this->customDocumentFields[$hook->value][] = $element;
    }

    /**
     * Gets custom document fields for a specific hook.
     */
    public function getCustomDocumentFields(string $hook): array
    {
        return $this->customDocumentFields[$hook] ?? [];
    }

    /**
     * Adds a custom top menu element based on a specified hook.
     */
    public function addCustomMenuElement(Hook $hook, array $element): void
    {
        $this->customMenuElements[$hook->value][] = $element;
    }

    /**
     * Gets custom menu elements for a specific hook.
     */
    public function getCustomMenuElements(string $hook): array
    {
        return $this->customMenuElements[$hook] ?? [];
    }

    /**
     * Adds a hook action.
     */
    public function addHookAction(Hook $hook, array $action): void
    {
        if (!isset($this->hookActions[$hook->value])) {
            $this->hookActions[$hook->value] = [];
        }
        $this->hookActions[$hook->value][] = $action;
    }

    /**
     * Gets hook actions for a specific hook.
     */
    public function getHookActions(string $hook): array
    {
        return $this->hookActions[$hook] ?? [$hook => []];
    }

    /**
     * Gets actions for sale ticket printing hook.
     */
    public function getPrintSaleTicketActions(): array
    {
        return $this->getHookActions(Hook::OnSaleTicketPrinting->value);
    }

    /**
     * Gets actions for draft ticket printing hook.
     */
    public function getPrintDraftTicketActions(): array
    {
        return $this->getHookActions(Hook::OnDraftTicketPrinting->value);
    }

    /**
     * Gets actions for closing ticket printing hook.
     */
    public function getPrintClosingTicketActions(): array
    {
        return $this->getHookActions(Hook::OnClosingTicketPrinting->value);
    }
}
