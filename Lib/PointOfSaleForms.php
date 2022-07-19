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
    public static function getFormsGrid(User $user): array
    {
        $data = [
            'headers' => [],
            'columns' => []
        ];

        $options  = self::loadPageOptions($user);

        /** @var PointOfSaleFormColumn $column */
        foreach (self::getColumns($options) as $tittle => $column) {
            $item = [
                'data' => $column->fieldname,
                'type' => $column->type,
                'readonly' => $column->readonly,
                'carrito' => $column->onCart
            ];

            $data['columns'][] = $item;
            $data['headers'][] = ToolBox::i18n()->trans($tittle);
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
        $model = new PageOption();

        $where = [
            new DataBaseWhere('name', $view),
            new DataBaseWhere('nick', $user->nick),
            //new DataBaseWhere('nick', null, 'IS', 'OR'),
        ];

        if (false === $model->loadFromCode('', $where)) {
            VisualItemLoadEngine::installXML($view, $model);
        }

        return $model->columns;
    }

    protected static function getColumns(array $elements): array
    {
        $data = [];

        foreach ($elements as $element) {
            if ($element['tag'] !== 'column') {
                continue;
            }

            $data[$element['name']] = self::getFields($element['children']);
        }

        return $data;
    }

    protected static function getFields(array $elements): PointOfSaleFormColumn
    {
        $fields = [];

        foreach ($elements as $element) {
            $fields = new PointOfSaleFormColumn($element);
        }

        return $fields;
    }
}
