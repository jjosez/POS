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
class EditSesionPOS extends ExtendedController\EditController
{
    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'till-session';
        $pagedata['menu'] = 'admin';
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

        $this->addListView('ListOperacionPOS', 'OperacionPOS', 'till-session-operations', 'fas fa-list-ol');
        $this->addListView('ListPagosPOS', 'PagoPOS', 'till-session-payments', 'fas fa-money-check-alt');

        $this->setSettings('EditSesionPOS', 'btnNew', false);
        $this->setSettings('ListOperacionPOS', 'btnNew', false);
        $this->setSettings('ListOperacionPOS', 'btnDelete', false);
        $this->setSettings('ListPagosPOS', 'btnNew', false);
        $this->setSettings('ListPagosPOS', 'btnDelete', false);
        $this->setTabsPosition('top');  
    }

    /**
     * Returns the model name
     */
    public function getModelClassName()
    {
        return 'SesionPOS';
    }

    protected function loadData($viewName, $view)
    {
        switch ($viewName) {
            case 'ListOperacionPOS':
                $idsesion = $this->getViewModelValue('EditSesionPOS', 'idsesion');
                $where = [new DataBaseWhere('idsesion', $idsesion)];
                $view->addOrderBy(['fecha','hora'], 'Fecha',2);
                $view->loadData('', $where);                
                //('ListGrupoClientes', ['nombre'], 'name', 1);
                break;
            case 'ListPagosPOS':
                $idsesion = $this->getViewModelValue('EditSesionPOS', 'idsesion');
                $where = [new DataBaseWhere('idsesion', $idsesion)];
                $view->addOrderBy(['total'], 'Total',2);
                $view->addOrderBy(['idoperacion'], 'No. operacion',2);
                $view->loadData('', $where);
                //('ListGrupoClientes', ['nombre'], 'name', 1);
                break;
            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

}
