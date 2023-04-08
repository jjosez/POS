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
class EditPagoPuntoVenta extends ExtendedController\EditController
{
    /**
     * Returns the model name
     */
    public function getModelClassName(): string
    {
        return 'PagoPuntoVenta';
    }
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'pos-payments';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-donate';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /*protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditPagoPuntoVenta':

        }
        parent::loadData($viewName, $view);
    }*/

    /**
     * Load views
     */
    /*protected function createViews()
    {
        parent::createViews();

        $this->createOrdenesView();
        $this->createPagosView();
        $this->createMovimientosView();

        $this->setSettings('EditSesionPuntoVenta', 'btnNew', false);
        $this->setTabsPosition('top');
    }

    protected function createPagosView(string $viewName = 'EditPagoPuntoVenta')
    {
        $formaspago = $this->codeModel->all('formaspago', 'codpago', 'descripcion');

        $this->addListView($viewName, 'PagoPuntoVenta', 'till-session-payments', 'fas fa-credit-card');
        $this->views[$viewName]->addOrderBy(['total'], 'Total', 2);
        $this->views[$viewName]->addOrderBy(['idoperacion'], 'No. operacion', 2);
        $this->views[$viewName]->addFilterSelect('formapago', 'Metodo de pago', 'codpago', $formaspago);

        $this->disableButtons($viewName);
        $this->setSettings($viewName, 'clickable', false);
    }*/
}
