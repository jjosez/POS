<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Extension\Controller;

use Closure;

/**
 * @method createViewsDocuments(string $viewName, string $modelo, string $icono)
 */
class EditSecuenciaDocumento
{
    public function createViews(): Closure
    {
        return function () {
            $column = $this->views[$this->getMainViewName()]->columnForName('doc-type');

            if ($column && $column->widget->getType() === 'select') {
                $values = array_merge($column->widget->values, [
                    [
                        'value' => 'BorradorPuntoVenta',
                        'title' => 'pos-draft'
                    ],
                ]);

                $column->widget->setValuesFromArray($values);
            }

            $this->createViewsDocuments('ListBorradorPuntoVenta', 'BorradorPuntoVenta', 'cash-register');
        };
    }
}
