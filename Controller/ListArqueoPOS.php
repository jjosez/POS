<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2017-2018 Carlos Garcia Gomez <carlos@facturascripts.com>
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

use FacturaScripts\Core\Lib\ExtendedController;

/**
 * Controller to list the items in the FraccionMoneda model
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class ListArqueoPOS extends ExtendedController\ListController
{

    /**
     * Returns basic page attributes
     *
     * @return array
     */
    public function getPageData()
    {
        $pagedata = parent::getPageData();
        $pagedata['title'] = 'cash-counts';
        $pagedata['icon'] = 'fas fa-money-bill-alt';
        $pagedata['menu'] = 'point-of-sale';

        return $pagedata;
    }

    /**
     * Load views
     */
    protected function createViews()
    {
        $this->addView('ListArqueoPOS', 'ArqueoPOS', 'cash-counts', 'fas fa-money-bill-alt');
        $this->addSearchFields('ListArqueoPOS', ['nombreagente']);

        $this->addOrderBy('ListArqueoPOS', ['fechainicio'], 'Fecha Inicio');
        $this->addOrderBy('ListArqueoPOS', ['fechafin'], 'Fecha Fin');
    }
}
