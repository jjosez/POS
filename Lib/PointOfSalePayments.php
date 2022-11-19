<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;

class PointOfSalePayments
{
    public static function saveOrderPayments($cashMethod, $order, $payments): float
    {
        $cashAmount = 0.0;

        foreach ($payments as $payment) {
            if ($payment['method'] === $cashMethod) {
                $cashAmount += $payment['amount'] - $payment['change'];
            }

            self::savePayment($payment, $order);
        }

        return $cashAmount;
    }

    protected static function savePayment(array $payment, OrdenPuntoVenta $order)
    {
        $pago = new PagoPuntoVenta();

        $pago->cantidad = $payment['amount'];
        $pago->cambio = $payment['change'];
        $pago->codpago = $payment['method'];
        $pago->idoperacion = $order->idoperacion;
        $pago->idsesion = $order->idsesion;

        $pago->save();
    }
}
