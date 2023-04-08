<?php

namespace FacturaScripts\Plugins\POS\Extension\Model\Base;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Lib\ReceiptGenerator;
use FacturaScripts\Dinamic\Model\DocTransformation;
use FacturaScripts\Dinamic\Model\FacturaCliente;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Plugins\POS\Lib\PointOfSalePayments;

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

            $pagos = $order->getPayments();

            $whereTransformation = [
                new DataBaseWhere('model1', $this->modelClassName()),
                new DataBaseWhere('iddoc1', $this->primaryColumnValue())
            ];

            $transformation = new DocTransformation();
            $transformation->loadFromCode('', $whereTransformation);

            if (! $transformation->model2 && ! $transformation->iddoc2) {
                return;
            }

            //Si es una Factura generamos los recibos correspondientes.
            if (('FacturaCliente' === $transformation->model2) && $transformation->iddoc2) {
                $factura = new FacturaCliente();

                if (false === $factura->loadFromCode($transformation->iddoc2)) {
                    return;
                }

                //Eliminamos el recibo generado automáticamente.
                PointOfSalePayments::cleanInvoiceReceipts($factura);

                //Generamos los nuevos recibos con base en los pagos.
                $numero = 1;
                foreach ($pagos as $pago) {
                    PointOfSalePayments::saveInvoiceReceipt($factura, $pago, $numero++);
                }

                //Generamos el recibo por el saldo pendiente si hubiese y actualizamos la factura.
                $generator = new ReceiptGenerator();
                $generator->generate($factura);
                $generator->update($factura);
            }
        };
    }
}
