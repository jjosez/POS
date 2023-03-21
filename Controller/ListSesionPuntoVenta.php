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
class ListSesionPuntoVenta extends ExtendedController\ListController
{
    protected function getClassName(): string
    {
        return parent::getClassName();
    }

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData(): array
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
        $this->addView('ListSesionPuntoVenta', 'SesionPuntoVenta', 'till-sessions', 'fas fa-money-bill-alt');
        $this->addSearchFields('ListSesionPuntoVenta', ['nombreagente']);

        $this->addOrderBy('ListSesionPuntoVenta', ['fechainicio','horainicio'], 'Fecha Inicio', 2);
        $this->addOrderBy('ListSesionPuntoVenta', ['fechafin','horafin'], 'Fecha Fin');

        $this->setSettings('ListSesionPuntoVenta', 'btnNew', false);
    }
}
