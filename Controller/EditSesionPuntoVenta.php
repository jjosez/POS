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
    protected function createViews()
    {
        parent::createViews();

        $this->createOrdenesView();
        $this->createPagosView();
        $this->createMovimientosView();

        $this->setSettings('EditSesionPuntoVenta', 'btnNew', false);
        $this->setTabsPosition('top');
    }

    protected function createMovimientosView(string $viewName = 'ListMovimientoPuntoVenta')
    {
        $this->addListView($viewName, 'MovimientoPuntoVenta', 'till-session-cash-movments', 'fas fa-wallet');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'date');
        $this->disableButtons($viewName);
        $this->setSettings($viewName, 'clickable', false);
    }

    protected function createOrdenesView(string $viewName = 'ListOrdenPuntoVenta')
    {
        $this->addListView($viewName, 'OrdenPuntoVenta', 'till-session-operations');
        $this->views[$viewName]->addOrderBy(['fecha', 'hora'], 'Fecha', 2);
        $this->disableButtons($viewName);
    }

    protected function createPagosView(string $viewName = 'ListPagoPuntoVenta')
    {
        $formaspago = $this->codeModel->all('formaspago', 'codpago', 'descripcion');

        $this->addListView($viewName, 'PagoPuntoVenta', 'till-session-payments', 'fas fa-credit-card');
        $this->views[$viewName]->addOrderBy(['total'], 'Total', 2);
        $this->views[$viewName]->addOrderBy(['idoperacion'], 'No. operacion', 2);
        $this->views[$viewName]->addFilterSelect('formapago', 'Metodo de pago', 'codpago', $formaspago);

        $this->disableButtons($viewName);
        $this->setSettings($viewName, 'clickable', false);
    }

    protected function disableButtons(string $viewName)
    {
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
        $this->setSettings($viewName, 'btnNew', false);
        $this->setSettings($viewName, 'btnDelete', false);
        //$this->setSettings($viewName, 'clickable', false);
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListPagoPuntoVenta':
            case 'ListMovimientoPuntoVenta':
            case 'ListOrdenPuntoVenta':
                $where = [new DataBaseWhere('idsesion', $this->getModel()->primaryColumnValue())];
                $view->loadData('', $where);
                break;
            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
