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

    public function __construct(SesionPuntoVenta $session)
    {
        $this->session = $session;
    }

    public function saveOrderPayment($payment, OrdenPuntoVenta $order)
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
