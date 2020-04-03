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

namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Core\Model\PageOption;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\User;

/**
 * A set of tools to manage sessions and user acces.
 *
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SalesLinesGrid
{
    /**
     * Returns the columns available by user acces.
     *
     * @param User $user
     * @return string
     */
    public static function getGridData(User $user)
    {
        $data = [
            'headers' => [],
            'columns' => [],
            'rows' => []
        ];

        $columns = self::loadPageOptions($user);

        foreach (self::getColumns($columns) as $col) {
            $item = [
                'data' => $col->widget->fieldname,
                'type' => $col->widget->getType(),
            ];

            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'numeric';
                $item['numericFormat'] = (new ToolBox)->coins()::gridMoneyFormat();
            } elseif ($item['type'] === 'autocomplete') {
                $item['source'] = $col->widget->getDataSource();
                $item['strict'] = false;
                $item['visibleRows'] = 5;
                $item['trimDropdown'] = false;
            }

            if (!$col->hidden()) {
                $data['columns'][] = $item;
                $data['headers'][] = (new ToolBox)->i18n()->trans($col->title);
            }
        }

        return json_encode($data);
    }

    /**
     *
     * @param User|false $user
     * @return array
     */
    private static function loadPageOptions($user)
    {
        $businessDocumentColumns = [];
        $modals = [];
        $rows = [];
        $pageOption = new PageOption();

        $orderby = ['nick' => 'ASC'];
        $where = [
            new DataBaseWhere('name', 'BusinessDocumentLine'),
            new DataBaseWhere('nick', $user->nick),
            new DataBaseWhere('nick', null, 'IS', 'OR'),
        ];;

        if (!$pageOption->loadFromCode('', $where, $orderby)) {
            $viewName = 'BusinessDocumentLine';
            VisualItemLoadEngine::installXML($viewName, $pageOption);
        }

        VisualItemLoadEngine::loadArray($businessDocumentColumns, $modals, $rows, $pageOption);

        return $businessDocumentColumns;
    }

    /**
     *
     * @param array $columns
     * @return array
     */
    private static function getColumns(array $columns)
    {
        $keys = array_keys($columns);
        if (empty($keys)) {
            return [];
        }

        $key = $keys[0];
        return $columns[$key]->columns;
    }
}
