<?php

namespace FacturaScripts\Plugins\POS\Extension\Model;

use Closure;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\TransformerDocument;
use FacturaScripts\Dinamic\Lib\PointOfSalePayments;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

/**
 * @mixin TransformerDocument
 */
class FacturaCliente
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

            PointOfSalePayments::saveInvoiceReceiptFromArray($this, $payments);

            //Generamos el recibo por el saldo pendiente si hubiese y actualizamos la factura.
            $generator = new ReceiptGenerator();
            $generator->generate($this);
            $generator->update($this);
        };
    }
}
