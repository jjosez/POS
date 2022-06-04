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
    /**
     * @var array
     */
    protected $payments = [];

    /**
     * @var OrdenPuntoVenta
     */
    protected $order;

    public function __construct(OrdenPuntoVenta $order)
    {
        $this->order = $order;
    }

    public function savePayments(array $payments = [])
    {
        foreach ($payments as $payment) {
            $this->savePayment($payment);
        }
    }

    protected function savePayment(array $payment)
    {
        $pago = new PagoPuntoVenta();
        $pago->cantidad = $payment['amount'];
        $pago->cambio = 0;
        $pago->codpago = $payment['method'];
        $pago->idoperacion = $this->order->idoperacion;
        $pago->idsesion = $this->order->idsesion;

        $pago->save();
    }
}
