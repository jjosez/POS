<?php

namespace FacturaScripts\Plugins\POS\Extension\Controller;

use Closure;

/**
 * @property $views
 */
class EditEstadoDocumento
{
    public function createViews(): Closure
    {
        return function () {
            $column = $this->views['EditEstadoDocumento']->columnForName('doc-type');

            if ($column && $column->widget->getType() === 'select') {
                $column->widget->setValuesFromArray(
                    array_merge($column->widget->values, [
                        ['value' => 'BorradorPuntoVenta', 'title' => 'Borrador Punto de Venta'],
                    ])
                );
            }
        };
    }
}
