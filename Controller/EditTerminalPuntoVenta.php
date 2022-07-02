<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to edit a single item from the Divisa model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class EditTerminalPuntoVenta extends ExtendedController\EditController
{

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return 'TerminalPuntoVenta';
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'cash-register';
        $pagedata['menu'] = 'point-of-sale';
        $pagedata['icon'] = 'fas fa-cash-register';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }


    protected function createViews()
    {
        parent::createViews();
        $this->setTabsPosition('left');

        $this->createPaymenthMethodView();
        $this->createDocumentTypeView();
    }

    protected function createDocumentTypeView(string $viewName='EditTipoDocumentoPuntoVenta')
    {
        $this->addEditListView($viewName, 'TipoDocumentoPuntoVenta', 'doc-type', 'fas fa-file-invoice');
    }

    protected function createPaymenthMethodView(string $viewName = 'EditFormaPagoPuntoVenta')
    {
        $this->addEditListView($viewName, 'FormaPagoPuntoVenta', 'payment-methods', 'fas fa-credit-card');
        $this->views[$viewName]->disableColumn('codpago', false, 'false');
    }

    /**
     * @return bool
     */
    protected function insertAction()
    {
        if (parent::insertAction()) {
            return true;
        }

        if ($this->active === 'EditFormaPagoPuntoVenta') {
            $this->views['EditFormaPagoPuntoVenta']->disableColumn('codpago', false, 'false');
        }

        return false;
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'EditFormaPagoPuntoVenta':
                $where = [new DataBaseWhere('idterminal', $this->getModel()->primaryColumnValue())];
                $view->loadData('', $where);
                break;
                case 'EditTipoDocumentoPuntoVenta':
                $where = [new DataBaseWhere('idterminal', $this->getModel()->primaryColumnValue())];
                $view->loadData('', $where);
                break;

            default:
                parent::loadData($viewName, $view);
                break;
        }
    }
}
