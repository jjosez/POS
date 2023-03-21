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
 * @property $posorder
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

            $pagos = $order->getPayments();

            $whereTransformation = [
                new DataBaseWhere('model1', $this->modelClassName()),
                new DataBaseWhere('iddoc1', $this->primaryColumnValue())
            ];

            $transformation = new DocTransformation();
            $transformation->loadFromCode('', $whereTransformation);

            if (!$transformation->model2 && !$transformation->iddoc2) {
                return;
            }

            //Si es una Factura generamos los recibos correspondientes.
            if ('FacturaCliente' === $transformation->model2 && $transformation->iddoc2) {
                $factura = new FacturaCliente();

                if (false === $factura->loadFromCode($transformation->iddoc2)) {
                    return;
                }

                //Eliminamos el recibo generado automaticamente.
                PointOfSalePayments::cleanInvoiceReceipts($factura);

                //Generamos los nuevos recibos en base a los pagos.
                $numero = 1;
                foreach ($pagos as $pago) {
                    PointOfSalePayments::saveInvoiceReceipt($factura, $pago, $numero++);
                }

                //Generamos el recibo por el saldo pendiente si ubiese y actualizamos la factura.
                $generator = new ReceiptGenerator();
                $generator->generate($factura);
                $generator->update($factura);
            }
        };
    }
}
