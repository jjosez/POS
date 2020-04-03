<?php
/**
 * This file is part of EasyPOS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\EasyPOS\Lib\POS;

use FacturaScripts\Core\Base\ToolBox;

/**
 * Class to manage POS payments.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PaymentsProcessor
{
    private $cashPaymentAmount;
    private $payments;
    private $totalPaymentAmount;

    /**
     * PaymentsProcessor constructor.
     * @param array $payments
     */
    public function __construct(array $paymentsData = [])
    {
        $this->setPayments($paymentsData);
    }

    /**
     * @param array $payments
     */
    private function setPayments(array $payments)
    {
        foreach ($payments as $payment)
        {
            print_r($payment);
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
}
