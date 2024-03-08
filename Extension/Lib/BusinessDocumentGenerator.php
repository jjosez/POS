<?php

namespace FacturaScripts\Plugins\POS\Extension\Lib;

use Closure;
use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base\BusinessDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Model\OrdenPuntoVenta;
use FacturaScripts\Plugins\POS\Model\PagoPuntoVenta;
use FacturaScripts\Plugins\POS\Model\PagoPuntoVentaSeguimiento;

/**
 *
 */
class BusinessDocumentGenerator
{

    public function cloneLines(): Closure
    {
        /**
         * @param BusinessDocument $prototype
         * @param BusinessDocument $newDoc
         * @param array $lines
         * @param array $quantity
         * @return void
         */
        return function (BusinessDocument $prototype, BusinessDocument $newDoc, array $lines, array $quantity) {

            foreach ($newDoc->parentDocuments() as $parent) {
                Tools::log()->warning('Parent Name');

                $POSOrder = new OrdenPuntoVenta();
                $POSOrder->loadFromDocument($parent->modelClassName(), $parent->primaryColumnValue());

                foreach ($POSOrder->getPayments() as $parentPayment) {
                    $paymentTracking = new PagoPuntoVentaSeguimiento();
                    $paymentTracking->idpagopos = $parentPayment->idpago;
                    $paymentTracking->cantidad = $parentPayment->cantidad;
                    $paymentTracking->modelfrom = $parent->modelClassName();
                    $paymentTracking->idmodelfrom = $parent->primaryColumnValue();
                    $paymentTracking->modelto = $newDoc->modelClassName();
                    $paymentTracking->idmodelto = $newDoc->primaryColumnValue();
                    $paymentTracking->save();
                }

                foreach ($this->getPosPaymentsTracking($parent) as $payment) {
                    $paymentTracking = new PagoPuntoVentaSeguimiento();
                    $paymentTracking->idpagopos = $payment->idpagopos;
                    $paymentTracking->cantidad = $payment->cantidad;
                    $paymentTracking->modelfrom = $parent->modelClassName();
                    $paymentTracking->idmodelfrom = $parent->primaryColumnValue();
                    $paymentTracking->modelto = $newDoc->modelClassName();
                    $paymentTracking->idmodelto = $newDoc->primaryColumnValue();
                    $paymentTracking->save();
                }
            }
        };
    }

    protected function getPosPaymentsTracking(): Closure
    {
        return function (BusinessDocument $parent) {
            $where = [
                new DataBaseWhere('idmodelto', $parent->primaryColumnValue()),
                new DataBaseWhere('modelto', $parent->modelClassName())
            ];

            return (new PagoPuntoVentaSeguimiento())->all($where, [], 0, 0);
        };
    }
}
