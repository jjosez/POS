<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Controller;

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the SesionPOS model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class ListSesionPOS extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'till-sessions';
        $pagedata['icon'] = 'fas fa-money-bill-alt';
        $pagedata['menu'] = 'point-of-sale';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListSesionPOS', 'SesionPOS', 'till-sessions', 'fas fa-money-bill-alt');
        $this->addSearchFields('ListSesionPOS', ['nombreagente']);

        $this->addOrderBy('ListSesionPOS', ['fechainicio','horainicio'], 'Fecha Inicio', 2);
        $this->addOrderBy('ListSesionPOS', ['fechafin','horafin'], 'Fecha Fin');

        $this->setSettings('ListSesionPOS', 'btnNew', false);
    }
}
