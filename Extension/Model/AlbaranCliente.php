<?php

namespace FacturaScripts\Plugins\POS\Extension\Model;

use Closure;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Dinamic\Lib\PointOfSalePayments;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

/**
 * @mixin TransformerDocument
 */
class AlbaranCliente
{
    public function saveUpdate(): Closure
    {
        return function () {

            $payments = [];
            foreach ($this->parentDocuments() as $document) {
                $order = new OrdenPuntoVenta();

                if (false === $order->loadFromDocument($document->modelClassName(), $document->primaryColumnValue())) {
                    continue;
                }

                foreach ($order->getPayments() as $pago) {
                    if (array_key_exists($pago->codpago, $payments)) {
                        $payments[$pago->codpago] += $pago->pagoNeto();
                        continue;
                    }

                    $payments[$pago->codpago] = $pago->pagoNeto();
                }
            }
        };
    }
}
