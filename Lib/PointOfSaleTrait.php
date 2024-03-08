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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait PointOfSaleTrait
{
    /**
     * @var PointOfSaleSession
     */
    protected $session;

    protected $customMenuElements;

    /**
     * @var array|array[]
     */
    protected $customDocumentFields;

    protected function addResponseData(array $data = [])
    {
        $this->responseData = array_merge($this->responseData, $data);
    }

    /**
     * @return array
     */
    public function getParentFamilies(): array
    {
        $where = [new DataBaseWhere('madre', NULL, 'IS')];
        return (new Familia())->all($where);
    }

    /**
     * Returns the cash payment method ID.
     *
     * @return string
     */
    public function getCashPaymentMethod(): string
    {
        foreach ($this->getTerminal()->getPaymenthMethods() as $element) if ($element->recibecambio) {
            return $element->codpago;
        }
        return '';
    }

    /**
     * @return array
     */
    public function getCustomButtons(): array
    {
        return [];
    }

    /**
     * @param string $hook
     * @return array
     */
    public function getCustomDocumentFields(string $hook): array
    {
        return $this->customDocumentFields[$hook] ?? [];
    }

    public function getCustomMenuElements(string $hook): array
    {
        return $this->customMenuElements[$hook] ?? [];
    }

    /**
     * @return array
     */
    public function getCustomModals(): array
    {
        $extensionPath = join(DIRECTORY_SEPARATOR, ['Modal', 'POS', 'Extension']);
        $modalsPath = join(DIRECTORY_SEPARATOR, [FS_FOLDER, 'Dinamic', 'View', $extensionPath]);
        $modals = [];

        if (false === file_exists($modalsPath)) {
            return $modals;
        }

        $directoryIterator = new RecursiveDirectoryIterator($modalsPath);
        $fileIterator = new RecursiveIteratorIterator($directoryIterator);

        foreach ($fileIterator as $filename) {
            if ($filename->isDir()) continue;

            if (! strpos($filename->getFilename(), '.html.twig')) continue;

            $modals[] = $extensionPath . DIRECTORY_SEPARATOR . $filename->getFilename();
        }

        return $modals;
    }

    /**
     * @return Cliente
     */
    public function getDefaultCustomer(): Cliente
    {
        $customer = new Cliente();
        $customer->loadFromCode($this->getTerminal()->codcliente);

        return $customer;
    }

    /**
     * @return TipoDocumentoPuntoVenta
     */
    public function getDefaultDocument(): TipoDocumentoPuntoVenta
    {
        foreach ($this->getTerminal()->getDocumentTypes() as $element) if ($element->preferido) {
            return $element;
        }
        return new TipoDocumentoPuntoVenta();
    }

    /**
     * Returns all available denominations.
     *
     * @return array
     */
    public function getDenominations(): array
    {
        return (new DenominacionMoneda())->all([], ['valor' => 'ASC']);
    }

    /**
     * Returns fields available by user permissions.
     *
     * @return array
     */
    public function getFieldOptions(): array
    {
        return PointOfSaleForms::getFormsGrid($this->user->nick);
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
     * Return some products for initial view
     *
     * @param string $id
     * @param string $code
     * @return array
     */
    public function getProductImage(string $id, string $code): array
    {
        $product = new PointOfSaleProduct();

        return $product->getImages($id, $code);
    }

    /**
     * Return product images url list
     *
     * @param string $id
     * @param string $code
     * @return array
     */
    public function getProductImageList(string $id, string $code): array
    {
        $product = new PointOfSaleProduct();

        return $product->getImagesURL($id, $code);
    }

    /**
     * Returns all available payment methods.
     *
     * @return FormaPago[]
     */
    public function getPaymentMethods(): array
    {
        return $this->getTerminal()->getPaymenthMethods();
    }

    /**
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
        return $this->session->getTerminal()->allAvailable($this->user->idempresa);
    }

    protected function addCustomDocumentField(string $hook, array $element)
    {
        $this->customDocumentFields[$hook][] = $element;
    }

    protected function addCustomMenuElement(string $hook, array $element)
    {
        $this->customMenuElements[$hook][] = $element;
    }

    /**
     * Read the log.
     *
     * @return array
     */
    protected function getMessages(): array
    {
        $messages = [];
        $level = ['critical', 'warning', 'notice', 'info', 'error'];

        foreach (Tools::log()->read('master', $level) as $m) {
            if (in_array($m['level'], array('warning', 'critical', 'error'))) {
                $messages[] = ['type' => 'warning', 'message' => $m['message']];
                continue;
            }

            $messages[] = ['type' => $m['level'], 'message' => $m['message']];
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

    /**
     *
     */
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
        $this->token = $this->getNewToken();
    }

    /**
     * @param $content
     * @param bool $encode
     */
    protected function setResponse($content, bool $encode = true): void
    {
        $response = $encode ? json_encode($content) : $content;
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
            Tools::log()->warning('invalid-token' . $this->token);
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

    public function validateSettings(): bool
    {
        $result = true;

        $paymentMethod = $this->getPaymentMethods();
        if (empty($paymentMethod)) {
            Tools::log('POS')->warning('no-payment-method-established');
            $result = false;
        }

        $cashMethod = $this->getCashPaymentMethod();
        if (trim($cashMethod) === '') {
            Tools::log('POS')->warning('no-cash-payment-method-established');
            $result = false;
        }

        $defaultDocument = $this->getDefaultDocument();
        if ($defaultDocument->tipodoc === false) {
            Tools::log('POS')->warning('no-default-document-established');
            $result = false;
        }

        if (empty($this->getDenominations())) {
            Tools::log('POS')->warning('no-currency-denominations');
            $result = false;
        }

        return $result;
    }
}
