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
    protected function createViews($viewName = 'ListSesionPuntoVenta')
    {
        $this->addView($viewName, 'SesionPuntoVenta', 'till-sessions', 'fas fa-money-bill-alt');
        $this->addSearchFields($viewName, ['nickusuario']);

        $this->addOrderBy($viewName, ['fechainicio','horainicio'], 'Fecha Inicio', 2);
        $this->addOrderBy($viewName, ['fechafin','horafin'], 'Fecha Fin');

        $this->disableButtons($viewName);
    }

    protected function disableButtons(string $viewName)
    {
        $this->setSettings($viewName, 'btnNew', false);

        if (false === $this->permissions->allowDelete){
            $this->setSettings($viewName, 'btnDelete', false);
        }
    }
}
