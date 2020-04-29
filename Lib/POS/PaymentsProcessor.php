<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use FacturaScripts\Core\App\App;
use FacturaScripts\Core\App\AppSettings;
use FacturaScripts\Plugins\EasyPOS\Model\OperacionPOS;
use FacturaScripts\Plugins\EasyPOS\Model\PagoPOS;
use FacturaScripts\Plugins\EasyPOS\Model\SesionPOS;

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
    private $paymentsData;
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
        $this->paymentsData = $payments;
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
        foreach ($this->paymentsData as $p) {
            $payment = new PagoPOS();
            $payment->cantidad = $p['amount'];
            $payment->cambio = $p['change'];
            $payment->codpago = $p['method'];
            $payment->idoperacion = $operation->idoperacion;
            $payment->idsesion = $session->idsesion;

            $payment->save();
        }
    }
}
