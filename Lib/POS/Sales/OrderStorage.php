<?php
/*
 * This file is part of PrintTicket plugin for FacturaScripts
 * Copyright (c) 2021.  Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace FacturaScripts\Plugins\POS\Lib\POS\Sales;

use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Plugins\POS\Model\OperacionPausada;
use FacturaScripts\Plugins\POS\Model\OrdenPuntoVenta;

class OrderStorage
{
    /**
     * @var SesionPuntoVenta
     */
    private $session;

    /**
     * @var OrdenPuntoVenta
     */
    private $currentOrder;

    public function __construct(SesionPuntoVenta $session)
    {
        $this->session = $session;
    }

    /**
     * @param string $code
     * @return bool
     */
    public function updateOrderOnHold(string $code): bool
    {
        $orderOnHold = new OperacionPausada();

        if ($code && $orderOnHold->loadFromCode($code)) {
            $orderOnHold->idestado = 3;

            return $orderOnHold->save();
        }
        return false;
    }

    /**
     * @param string $code
     * @return array
     */
    public function getOrderOnHold(string $code): array
    {
        $order = new OperacionPausada();
        $order->loadFromCode($code);

        $result = [
            'doc' => $order->toArray(),
            'lines' => $order->getLines(),
        ];

        return $result;
    }

    /**
     * @return OperacionPausada[]
     */
    public function getOrdersOnHold(): array
    {
        $order = new OperacionPausada();

        return $order->allOpen();
    }

    /**
     * @return OrdenPuntoVenta[]
     */
    public function getLastOrders(): array
    {
        $order = new OrdenPuntoVenta();

        return $order->allFromSession($this->session->idsesion);
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function placeOrder(Order $order): bool
    {
        if (false === $order->save()) {
            return false;
        }

        $this->currentOrder = new OrdenPuntoVenta();
        $document = $order->getDocument();

        $this->currentOrder->codigo = $document->codigo;
        $this->currentOrder->codcliente = $document->codcliente;
        $this->currentOrder->fecha = $document->fecha;
        $this->currentOrder->iddocumento = $document->primaryColumnValue();
        $this->currentOrder->idsesion = $this->session->idsesion;
        $this->currentOrder->tipodoc = $document->modelClassName();
        $this->currentOrder->total = $document->total;

        if ($this->currentOrder->save()) {
            if ($document->idpausada) {
                $this->updateOrderOnHold($document->idpausada);
            }

            return true;
        }

        return false;
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    public function placeOrderOnHold(Order $order): bool
    {
        return $order->hold();
    }
}
