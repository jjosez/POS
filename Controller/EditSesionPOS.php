<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
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
        $pagedata['icon'] = 'fas fa-money-bill-alt';
        $pagedata['showonmenu'] = false;

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        parent::createViews();

        $this->addListView('ListOperacionPOS', 'OperacionPOS', 'till-session-operations', 'fas fa-balance-scale');

        $this->setSettings('EditSesionPOS', 'btnNew', false);
        $this->setSettings('ListOperacionPOS', 'btnNew', false);
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
            default:
                parent::loadData($viewName, $view);
                break;
        }
    }

}
