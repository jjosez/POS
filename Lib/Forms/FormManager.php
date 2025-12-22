<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib\Forms;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Lib\Widget\VisualItemLoadEngine;
use FacturaScripts\Dinamic\Model\PageOption;
use FacturaScripts\Plugins\POS\Model\OpcionesTerminalPuntoVenta;

/**
 * Infrastructure Service for managing POS form configurations.
 * Handles form field options and customization per terminal.
 */
class FormManager
{
    const FIELD_OPTIONS_VIEW = 'EditTerminalFieldOption';

    private static $options = [];

    /**
     * Returns the columns available by user access.
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
     * Returns the count of visible cart columns for a user.
     *
     * @param string $nick User nickname
     * @return int Count of visible cart columns
     */
    public static function getCartColumnCount(string $nick): int
    {
        $count = 0;
        $excludedColumns = ['reference', 'description', 'quantity'];
        $fields = self::getFormsGrid($nick);

        foreach ($fields as $column) {
            if (in_array($column['name'], $excludedColumns)) {
                continue;
            }

            if ($column['carrito']) {
                $count++;
            }
        }

        return $count;
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

        /** @var FormColumn $column */
        foreach (self::getColumns($model->columns) as $column) {
            $item = [
                'name' => $column->name,
                'data' => $column->fieldname,
                'type' => $column->type,
                'readonly' => $column->readonly,
                'carrito' => $column->onCart,
                'eneabled' => $column->eneabled,
                'tittle' => Tools::lang()->trans($column->name),
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
            Where::eq('nick', NULL),
        ];

        if ($options->loadWhere($where)) {
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
            Where::eq('nick', $nick),
        ];

        if ($options->loadWhere($where)) {
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

    protected static function getFields(array $elements, string $name): FormColumn
    {
        $fields = [];

        foreach ($elements as $element) {
            $fields = new FormColumn($element, $name);
        }

        return $fields;
    }
}
