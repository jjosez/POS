<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Dinamic\Lib\AssetManager;
use FacturaScripts\Dinamic\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Dinamic\Model\User;

class PointOfSaleForms
{
    /**
     * Returns the columns available by user acces.
     *
     * @param User $user
     * @return array
     */
    public static function getFormsGrid(User $user)
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

        //AssetManager::clear();
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
