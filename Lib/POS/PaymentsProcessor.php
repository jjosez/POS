<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Lib\POS;

use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Plugins\POS\Model\OperacionPOS;
use FacturaScripts\Plugins\POS\Model\PagoPOS;
use FacturaScripts\Plugins\POS\Model\SesionPOS;

/**
 * Class to manage POS payments.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PaymentsProcessor
{
    private $cashPaymentAmount;
    private $cashPaymentMethod;
    private $operation;
    private $payments;
    private $session;
    private $totalPaymentAmount;

    /**
     * PaymentsProcessor constructor.
     *
     * @param array $payments
     */
    public function __construct(array $payments)
    {
        $this->cashPaymentMethod = AppSettings::get('pointofsale', 'fpagoefectivo');
        $this->cashPaymentAmount = 0;
        $this->totalPaymentAmount = 0;

        $this->setPayments($payments);
    }

    /**
     * @param array $payments
     */
    private function setPayments(array $payments)
    {
        $this->payments = $payments;
        foreach ($payments as $payment) {
            if ($payment['method'] == $this->cashPaymentMethod) {
                $this->cashPaymentAmount += $payment['amount'] - $payment['change'];
                $this->totalPaymentAmount += $this->cashPaymentAmount;
            } else {
                $this->totalPaymentAmount += $payment['amount'];
            }
        }
    }

    /**
     * Return the total amount of cash payment method
     *
     * @return float
     */
    public function getCashPaymentAmount(): float
    {
        return $this->cashPaymentAmount;
    }

    /**
     * Return the total payment amount
     *
     * @return float
     */
    public function getTotalPaymentAmount(): float
    {
        return $this->totalPaymentAmount;
    }

    public function savePayments(OperacionPOS $operation, SesionPOS $session)
    {
        foreach ($this->payments as $payment) {
            $pago = new PagoPOS();
            $pago->cantidad = $payment['amount'];
            $pago->cambio = $payment['change'];
            $pago->codpago = $payment['method'];
            $pago->idoperacion = $operation->idoperacion;
            $pago->idsesion = $session->idsesion;

            $pago->save();
        }
    }
}
