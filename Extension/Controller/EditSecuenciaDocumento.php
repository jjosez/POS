<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Extension\Controller;

use Closure;
use FacturaScripts\Core\Lib\BusinessDocumentCode;
use FacturaScripts\Core\Where;

/**
 * @method createViewsDocuments(string $viewName, string $modelo, string $icono)
 * @method mainTabModelValue(string $string)
 * @method tab($mainTabName)
 * @method mainTabName()
 */
class EditSecuenciaDocumento
{
    public function createViews(): Closure
    {
        return function () {
            $this->createViewsDocuments('ListBorradorPuntoVenta', 'BorradorPuntoVenta', 'draft-orders');
            $secuence = $this->addPointOfSaleDraftSequence();
        };
    }

    public function loadData(): Closure
    {
        return function ($viewName, $view) {
            if ($viewName === 'ListBorradorPuntoVenta') {
                $where = [
                    Where::eq('codserie', $this->mainTabModelValue('codserie')),
                    Where::eq('idempresa', $this->mainTabModelValue('idempresa'))
                ];

                if ($this->tab($this->mainTabName())->model->codejercicio) {
                    $where[] = Where::eq('codejercicio', $this->tab($this->mainTabName())->model->codejercicio);
                    $view->loadData('', $where);

                    return;
                }
                $other = implode(',', BusinessDocumentCode::getOtherExercises($this->tab($this->mainTabName())->model));
                if (!empty($other)) {
                    $where[] = Where::notIn('codejercicio', $other);
                }

                $view->loadData('', $where);
            }
        };
    }

    public function addPointOfSaleDraftSequence(): Closure
    {
        return function (): void {
            $column = $this->tab('EditSecuenciaDocumento')->columnForName('doc-type');

            if ($column && $column->widget->getType() === 'select') {
                $column->widget->setValuesFromArray(
                    array_merge($column->widget->values, [
                        [
                            'value' => 'BorradorPuntoVenta',
                            'title' => 'Borrador Punto de Venta'
                        ],
                    ])
                );
            }
        };
    }
}
