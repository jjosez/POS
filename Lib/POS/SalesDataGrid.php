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
namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Dinamic\Model\User;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\Widget\VisualItemLoadEngine;

/**
 * A set of tools to manage sessions and user acces.
 *
 * @author Carlos García Gómez   <carlos@facturascripts.com>
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class SalesDataGrid
{
    /**
     * Returns the columns available by user acces.
     *
     * @param User $user
     * @return array
     */
    public static function getDataGrid(User $user)
    {
        $data = [
            'headers' => [],
            'columns' => []
        ];
        $columns = self::loadPageOptions($user);

        foreach (self::getColumns($columns) as $column) {
            if ($column->hidden()) {
                continue;
            }

            $item = [
                'data' => $column->widget->fieldname,
                'type' => $column->widget->getType(),
                'readonly' => ($column->widget->readonly == 'true') ? 'readonly' : ''
            ];

            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'number';
                $item['numericFormat'] = $column->widget->gridFormat();;
            } else {
                $item['type'] = 'text';
            }

            $data['columns'][] = $item;
            $data['headers'][] = ToolBox::i18n()->trans($column->title);
        }

        AssetManager::clear();
        return $data;
    }

    /**
     *
     * @param User|false $user
     * @return array
     */
    private static function loadPageOptions($user)
    {
        $viewName = 'EditConfiguracionPOS';
        $columns = [];
        $pageOption = new PageOption();

        $orderby = ['nick' => 'ASC'];
        $where = [
            new DataBaseWhere('name', $viewName),
            new DataBaseWhere('nick', $user->nick),
            new DataBaseWhere('nick', null, 'IS', 'OR'),
        ];

        if (!$pageOption->loadFromCode('', $where, $orderby)) {
            VisualItemLoadEngine::installXML($viewName, $pageOption);
        }

        //VisualItemLoadEngine::loadArray($columns, $modals, $rows, $pageOption);
        self::getGroupsColumns($pageOption->columns, $columns);

        return $columns;
    }

    /**
     * Load the column structure from the JSON
     *
     * @param array $columns
     * @param array $target
     */
    private static function getGroupsColumns($columns, &$target)
    {
        $namespace = '\\FacturaScripts\\Dinamic\\Lib\\Widget\\';
        $groupClass = $namespace . 'GroupItem';
        $newGroupArray = [
            'children' => [],
            'name' => 'main',
            'tag' => 'group',
        ];

        foreach ($columns as $key => $item) {
            if ($item['tag'] === 'group') {
                $groupItem = new $groupClass($item);
                $target[$groupItem->name] = $groupItem;
            } else {
                $newGroupArray['children'][$key] = $item;
            }
        }

        /// is there are loose columns, then we put it on a new group
        if (!empty($newGroupArray['children'])) {
            $groupItem = new $groupClass($newGroupArray);
            $target[$groupItem->name] = $groupItem;
        }
    }

    /**
     *
     * @param array $columns
     * @return array
     */
    private static function getColumns(array $columns)
    {
        foreach ($columns as $group) {
            return $group->columns;
        }

        return [];
    }
}
