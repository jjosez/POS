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
     * @var float
     */
    protected $cashPaymentAmount = 0.0;

    /**
     * @var OrdenPuntoVenta
     */
    protected $order;
    /**
     * @var string
     */
    protected $cashPaymentMethod;

    public function __construct(OrdenPuntoVenta $order, string $cashPaymentMethod)
    {
        $this->cashPaymentMethod = $cashPaymentMethod;
        $this->order = $order;
    }

    /**
     * @return float
     */
    public function getCashPaymentAmount(): float
    {
        return $this->cashPaymentAmount;
    }

    public function savePayments(array $payments = [])
    {
        foreach ($payments as $payment) {
            if ($payment['method'] === $this->cashPaymentMethod) {
                $this->cashPaymentAmount += $payment['amount'] - $payment['change'];
            }
            $this->savePayment($payment);
        }
    }

    protected function savePayment(array $payment)
    {
        $pago = new PagoPuntoVenta();
        $pago->cantidad = $payment['amount'];
        $pago->cambio = $payment['change'];
        $pago->codpago = $payment['method'];
        $pago->idoperacion = $this->order->idoperacion;
        $pago->idsesion = $this->order->idsesion;

        $pago->save();
    }
}
