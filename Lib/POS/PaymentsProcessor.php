<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

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
    private $paymentsData;
    private $session;
    private $totalPaymentAmount;

    /**
     * PaymentsProcessor constructor.
     * 
     * @param string $cashPaymentMethod
     * @param SesionPOS $session
     */
    public function __construct(string $cashPaymentMethod, SesionPOS $session)
    {
        $this->cashPaymentMethod = $cashPaymentMethod;
        $this->session = $session;
    }

    /**
     * @param array $payments
     */
    private function setPayments(array $payments)
    {
        foreach ($payments as $payment)
        {
        }
    }

    /**
     * Return the total amount of cash payment method
     *
     * @return float
     */
    public function getCashPaymentAmount() : float
    {
    }

    /**
     * Return the total payment amount
     *
     * @return float
     */
    public function getTotalPaymentAmount() : float
    {
    }

    public function savePayments(array $paymentsData)
    {
        ;
    }
}
