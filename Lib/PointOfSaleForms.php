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
use FacturaScripts\Plugins\POS\Model\OpcionesTerminalPuntoVenta;

class PointOfSaleForms
{
    const FIELD_OPTIONS_VIEW = 'EditTerminalFieldOption';

    private static $options = [];

    /**
     * Returns the columns available by user acces.
     *
     * @param string $nick
     * @return array
     */
    public static function getFormsGrid(string $nick): array
    {
        if (self::getUserFieldOptions($nick) || self::getAllUsersFieldOptions()) {
            $fields = [];

            foreach (self::$options as $column) {
                $fields[] = $column;
            }

            return $fields;
        }

        return self::getDefaultFieldOptions();
    }

    /**
     *
     * @return array
     */
    private static function getDefaultFieldOptions(): array
    {
        $model = new PageOption();
        $fields = [];

        VisualItemLoadEngine::installXML(self::FIELD_OPTIONS_VIEW, $model);

        /** @var PointOfSaleFormColumn $column */
        foreach (self::getColumns($model->columns) as $column) {
            $item = [
                'name' => $column->name,
                'data' => $column->fieldname,
                'type' => $column->type,
                'readonly' => $column->readonly,
                'carrito' => $column->onCart,
                'eneabled' => $column->eneabled,
                'tittle' => ToolBox::i18n()->trans($column->name),
            ];

            $fields[] = $item;
        }

        return $fields;
    }

    protected static function getAllUsersFieldOptions(): bool
    {
        // 'Buscando campos terminal: ';
        $options = new OpcionesTerminalPuntoVenta();

        $where = [
            new DataBaseWhere('nick', NULL),
        ];

        if ($options->loadFromCode('', $where)) {
            self::$options = $options->getColumnsAsArray();
            return true;
        }

        return false;
    }

    protected static function getUserFieldOptions(string $nick): bool
    {
        // 'Buscando campos usuario: ' . $nick;
        $options = new OpcionesTerminalPuntoVenta();

        $where = [
            new DataBaseWhere('nick', $nick),
        ];

        if ($options->loadFromCode('', $where)) {
            self::$options = $options->getColumnsAsArray();
            return true;
        }

        return false;
    }

    protected static function getColumns(array $elements): array
    {
        $data = [];

        foreach ($elements as $element) {
            if ($element['tag'] !== 'column') {
                continue;
            }

            $data[] = self::getFields($element['children'], $element['name']);
        }

        return $data;
    }

    protected static function getFields(array $elements, string $name): PointOfSaleFormColumn
    {
        $fields = [];

        foreach ($elements as $element) {
            $fields = new PointOfSaleFormColumn($element, $name);
        }

        return $fields;
    }
}
