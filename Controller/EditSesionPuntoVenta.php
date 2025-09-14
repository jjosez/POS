<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the SesionPOS model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditSesionPuntoVenta extends ExtendedController\EditController
{
    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'SesionPuntoVenta';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'till-session';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-suitcase';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews(): void
    {
        parent::createViews();
        $this->setTabsPosition('top');

        $this->createOrdenesView();
        $this->createPagosView();
        $this->createMovimientosView();
        $this->createDraftView();
    }

    protected function setSettingsSesionView(): void
    {
        $this->setSettings('EditSesionPuntoVenta', 'btnNew', false);

        if ($this->user->admin) {
            $this->views[$this->getMainViewName()]->disableColumn('opened');
        }
    }

    protected function createMovimientosView(string $viewName = 'ListMovimientoPuntoVenta'): void
    {
        $this->addListView($viewName, 'MovimientoPuntoVenta', 'till-session-cash-movments', 'fas fa-wallet')
            ->addOrderBy(['fecha', 'hora'], 'date');

        $this->setSettings($viewName, 'clickable', false);
        $this->disableButtons($viewName);
    }

    protected function createOrdenesView(string $viewName = 'ListOrdenPuntoVenta'): void
    {
        $this->addListView($viewName, 'OrdenPuntoVenta', 'till-session-operations')
            ->addOrderBy(['fecha', 'hora'], 'Fecha', 2);

        $this->disableButtons($viewName);
    }

    private function createDraftView(string $viewName = 'ListBorradorPuntoVenta'): void
    {
        $this->addListView($viewName, 'BorradorPuntoVenta', 'pos-drafts')
            ->addOrderBy(['fecha', 'hora'], 'Fecha', 2);

        $this->disableButtons($viewName);
    }

    protected function createPagosView(string $viewName = 'ListPagoPuntoVenta'): void
    {
        $formaspago = $this->codeModel->all('formaspago', 'codpago', 'descripcion');

        $this->addListView($viewName, 'PagoPuntoVenta', 'till-session-payments', 'fas fa-credit-card')
            ->addOrderBy(['total'], 'Total', 2)
            ->addOrderBy(['idoperacion'], 'No. operacion', 2)
            ->addFilterSelect('formapago', 'Metodo de pago', 'codpago', $formaspago);

        $this->setSettings($viewName, 'clickable', true);
        $this->disableButtons($viewName);
    }

    protected function disableButtons(string $viewName): void
    {
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnNew', false);

        if (false === $this->permissions->allowDelete) {
            $this->setSettings($viewName, 'btnDelete', false);
        }
    }

    protected function loadData($viewName, $view): void
    {
        switch ($viewName) {
            case 'ListPagoPuntoVenta':
            case 'ListMovimientoPuntoVenta':
            case 'ListOrdenPuntoVenta':
                $where = [new DataBaseWhere('idsesion', $this->getModel()->primaryColumnValue())];
                $view->loadData('', $where);
                break;
            case 'ListBorradorPuntoVenta':
                $view->loadData();
                break;
            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
