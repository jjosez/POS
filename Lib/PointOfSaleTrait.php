<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\DenominacionMoneda;
use FacturaScripts\Dinamic\Model\Familia;
use FacturaScripts\Dinamic\Model\FormaPago;
use FacturaScripts\Dinamic\Model\FormatoTicket;
use FacturaScripts\Dinamic\Model\TerminalPuntoVenta;
use FacturaScripts\Plugins\POS\Model\TipoDocumentoPuntoVenta;

trait PointOfSaleTrait
{
    /**
     * @var PointOfSaleSession
     */
    protected $session;

    protected array $customMenuElements;

    /**
     * @var array|array[]
     */
    protected array $customDocumentFields;


    /**
     * @var PointOfSaleTicketFormat[]
     */
    protected array $ticketFormats = [];

    protected function addResponseData(array $data = [])
    {
        $this->responseData = array_merge($this->responseData, $data);
    }

    /**
     * @return array
     */
    public function getParentFamilies(): array
    {
        $where = [
            new DataBaseWhere('pos_shortcut', true)
        ];

        return Familia::all($where);
    }

    /**
     * Returns the cash payment method ID.
     *
     * @return string
     */
    public function getCashPaymentMethod(): string
    {
        return $this->getTerminal()->getCashPaymentMethod();
    }

    public function getCustomButtons(): array
    {
        return [];
    }

    public function getCustomDocumentFields(string $hook): array
    {
        return $this->customDocumentFields[$hook] ?? [];
    }

    public function getCustomMenuElements(string $hook): array
    {
        return $this->customMenuElements[$hook] ?? [];
    }

    public function getSaleTicketFormats()
    {
        return $this->ticketFormats['sale'] ?? [];
    }

    public function getClosingTicketFormats()
    {
        return $this->ticketFormats['closing'] ?? [];
    }

    public function getDefaultCustomer(): Cliente
    {
        $customer = new Cliente();
        $customer->loadFromCode($this->getTerminal()->codcliente);

        return $customer;
    }

    public function getDefaultDocument(): TipoDocumentoPuntoVenta
    {
        return $this->getTerminal()->getDefaultDocument();
    }

    /**
     * @return TipoDocumentoPuntoVenta[]
     */
    public function getSupportedDocuments(): array
    {
        return $this->getTerminal()->getSupportedDocuments();
    }

    /**
     * Returns all available denominations.
     *
     * @return DenominacionMoneda[]
     */
    public function getDenominations(): array
    {
        return DenominacionMoneda::all([], ['valor' => 'ASC']);
    }

    /**
     * Returns fields available by user permissions.
     */
    public function getFieldOptions(): array
    {
        return PointOfSaleForms::getFormsGrid($this->user->nick);
    }

    public function getCartColumnCount(): int
    {
        $count = 0;
        $excludedColumns = ['reference', 'description', 'quantity'];

        foreach ($this->getFieldOptions() as $column) {
            if (in_array($column['name'], $excludedColumns)) continue;

            if ($column['carrito']) $count++;
        }

        return $count;
    }

    /**
     * Return some products for initial view
     *
     * @return array
     */
    public function getHomeProducts(): array
    {
        return PointOfSaleProduct::search('');
    }

    /**
     * Returns a random token to use as transaction id.
     *
     * @return string
     */
    public function getNewToken(): string
    {
        return $this->multiRequestProtection->newToken();
    }

    /**
     * Returns all available payment methods.
     *
     * @return FormaPago[]
     */
    public function getPaymentMethods(): array
    {
        return $this->getTerminal()->getSupportedPaymenthMethods();
    }

    /**
     * Get default warehouse.
     *
     * @return string
     */
    public function getDefaultWarehouse(): string
    {
        return $this->getTerminal()->codalmacen ?: '';
    }

    /**
     * Get current user session.
     *
     * @return PointOfSaleSession
     */
    public function getSession(): PointOfSaleSession
    {
        return $this->session;
    }

    /**
     * Get current user session terminal.
     *
     * @return TerminalPuntoVenta
     */
    public function getTerminal(): TerminalPuntoVenta
    {
        return $this->session->getTerminal();
    }

    /**
     * Get current user session terminal.
     *
     * @return TerminalPuntoVenta[]
     */
    public function getTerminalFromCompany(): array
    {
        return $this->session->getTerminal()->getAvailable($this->user->idempresa);
    }

    /**
     * Adds a custom field to document view based on a specified hook.
     *
     * @param string $hook The hook name to associate with the custom field.
     * @param array $element The custom field data to be added.
     */
    protected function addCustomDocumentField(string $hook, array $element)
    {
        $this->customDocumentFields[$hook][] = $element;
    }

    /**
     * Adds a custom top menu view based on a specified hook.
     *
     * @param string $hook The hook name to associate with the custom element.
     * @param array $element The custom element data to be added.
     */
    protected function addCustomMenuElement(string $hook, array $element)
    {
        $this->customMenuElements[$hook][] = $element;
    }


    /**
     * Adds a closing ticket format to the available format list.
     *
     * @param array $format The format data to be added.
     */
    public function addClosingTicketFormat(array $format): void
    {
        $this->ticketFormats['closing'][] = $format;
    }

    /**
     * Adds a closing ticket format to the available format list.
     *
     * @param array $format The format data to be added.
     */
    public function addSaleTicketFormat(array $format): void
    {
        $this->ticketFormats['sale'][] = $format;
    }

    /**
     * Read the log messages.
     *
     * @return array
     */
    protected function getMessages(): array
    {
        $messages = [];
        $level = ['critical', 'warning', 'notice', 'info', 'error'];

        $masterChannel = Tools::log()->read('master', $level);
        $posChannel = Tools::log()->read('POS', $level);

        $currentMessages = array_merge($masterChannel, $posChannel);

        foreach ($currentMessages as $message) {
            if (in_array($message['level'], array('warning', 'critical', 'error'))) {
                $messages[] = ['type' => 'warning', 'message' => $message['message']];
                continue;
            }

            if ($message['level'] = 'notice') {
                $messages[] = ['type' => 'success', 'message' => $message['message']];
                continue;
            }

            $messages[] = ['type' => 'info', 'message' => $message['message']];
        }

        return $messages;
    }

    protected function getVoucherFormat(): FormatoTicket
    {
        $format = new FormatoTicket();
        $format->loadFromCode($this->getTerminal()->idformatoticket);

        return $format;
    }

    protected function loadCustomDocumentFields(): void
    {
        $this->customDocumentFields = ['detail' => [], 'cart' => []];
        $this->pipe('loadCustomDocumentFields');
    }

    protected function loadCustomMenuElements(): void
    {
        $this->customMenuElements = ['navbar' => [], 'content-navbar' => []];
        $this->pipe('loadCustomMenuElements');
    }

    protected function loadTicketFormats(): void
    {
        $this->ticketFormats = ['sale' => [], 'closing' => []];
        $this->pipe('loadTicketFormats');
    }

    public function setFamilyFilter(): void
    {
        $codfamilia = $this->request->request->get('code', '');

        $where = [new DataBaseWhere('madre', $codfamilia)];

        $familia = new Familia();
        $familia->loadFromCode($codfamilia);

        $result = [
            'madre' => $familia->codfamilia ? $familia : '',
            'children' => $codfamilia ? $familia->all($where) : $this->getParentFamilies()
        ];

        $this->setResponse($result);
    }

    protected function setNewToken(): void
    {
        $this->token = $this->multiRequestProtection->newToken();
    }

    /**
     * @param $content
     * @param bool $encode
     */
    protected function setResponse($content, bool $encode = true): void
    {
        if ($encode) {
            $response = json_encode($content);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Tools::log()->error('Error al serializar JSON: ' . json_last_error_msg());
                $response = json_encode(['error' => 'Error al generar respuesta JSON']);
            }
        } else {
            $response = $content;
        }

        $this->response->setContent($response);
    }

    protected function validateDelete(): bool
    {
        if (false === $this->permissions->allowDelete) {
            Tools::log()->warning('not-allowed-delete');
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    protected function validateRequest(): bool
    {
        if (false === $this->permissions->allowUpdate) {
            Tools::log()->warning('not-allowed-modify');
            $this->buildResponse();
            return false;
        }

        $this->token = $this->request->request->get('token');

        if (empty($this->token) || false === $this->multiRequestProtection->validate($this->token)) {
            Tools::log()->warning('invalid-request');
            Tools::log()->warning('invalid-token');
            $this->buildResponse();
            return false;
        }

        if ($this->multiRequestProtection->tokenExist($this->token)) {
            Tools::log()->warning('duplicated-request');
            $this->buildResponse();
            return false;
        }

        $this->setNewToken();
        return true;
    }

    /**
     * @return bool
     */
    public function validateSettings(): bool
    {
        $isValid = true;

        $validations = [
            'no-payment-method-set' => empty($this->getPaymentMethods()),
            'no-cash-payment-method-set' => trim($this->getCashPaymentMethod()) === '',
            'no-default-document-set' => $this->getDefaultDocument()->tipodoc === false,
            'no-currency-denominations' => empty($this->getDenominations())
        ];

        foreach ($validations as $message => $condition) if ($condition) {
            Tools::log('POS')->warning($message);
            $isValid = false;
        }

        return $isValid;
    }
}
