<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
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
        foreach (self::getColumnsData($columns) as $column) {
            $item = [
                'data' => $column['fieldname'],
                'type' => $column['type'],
                'readonly' => ($column['readonly'] === 'true') ? 'readonly' : '',
                'carrito' => $column['carrito']
            ];

            if ($item['type'] === 'number' || $item['type'] === 'money') {
                $item['type'] = 'number';
            }

            $data['columns'][] = $item;
            $data['headers'][] = ToolBox::i18n()->trans($column['tittle']);
        }

        return $data;
    }

    /**
     *
     * @param User|false $user
     * @return array
     */
    private static function loadPageOptions($user): array
    {
        $view = 'EditConfiguracionPOS';
        $columns = [];
        $model = new PageOption();

        $where = [
            new DataBaseWhere('name', $view),
            new DataBaseWhere('nick', $user->nick),
            //new DataBaseWhere('nick', null, 'IS', 'OR'),
        ];

        if (false === $model->loadFromCode('', $where)) {
            VisualItemLoadEngine::installXML($view, $model);
        }

        //VisualItemLoadEngine::loadArray($columns, $modals, $rows, $model);
        //self::getGroupsColumns($model->columns, $columns);
        return $model->columns;
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

    protected static function getColumnsData(array $columns)
    {
        $data = [];
        $count = 0;

        foreach ($columns as $column) {
            $data[$count]['tittle'] = $column['name'];
            foreach ($column['children'][0] as $key => $value) {
                if ($key === 'tag' || $key === 'children') {
                    continue;
                }
                $data[$count][$key] = $value;
            }
            $count++;
        }

        return $data;
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
