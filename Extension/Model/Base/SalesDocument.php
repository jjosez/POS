<?php

namespace FacturaScripts\Plugins\POS\Extension\Model\Base;

use Closure;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

/**
 * Description of SalesDocument
 *
 * @property $posid
 * @property $codigo
 * @property $codcliente
 * @property $total
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 * @method primaryColumnValue()
 * @method primaryColumn()
 * @method modelClassName()
 */
class SalesDocument
{
    public function saveUpdate(): Closure
    {
        return function () {
            $order = new OrdenPuntoVenta();

            if (false === $order->loadFromDocument($this->modelClassName(), $this->primaryColumnValue())) {
                return;
            }

            $hasChange = false;

            if ($order->codcliente !== $this->codcliente) {
                $hasChange = true;
                $order->codcliente = $this->codcliente;
            }

            if ($order->codigo !== $this->codigo) {
                $hasChange = true;
                $order->codigo = $this->codigo;
            }

            if ($order->total !== $this->total) {
                $hasChange = true;
                $order->total = $this->total;
            }

            if ($hasChange) {
                $order->save();
            }
        };
    }
}
