<?php

namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Plugins\POS\Model\PagoPuntoVenta;

class PaymentStorage
{
    /**
     * @var SesionPuntoVenta
     */
    protected $session;

    protected $payments;

    /**
     * @var OrdenPuntoVenta
     */
    protected $order;

    public function __construct(OrdenPuntoVenta $order)
    {
        //$this->session = $session;
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
