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

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Dinamic\Model\OperacionPausada;

class OrderStorage
{
    public function completeOrder(Order $order)
    {

    }

    public function getOrderOnHold(string $code)
    {
        $order = new OperacionPausada();
        $order->loadFromCode($code);

        $result = [
            'doc' => $order->toArray(),
            'lines' => $order->getLines(),
        ];

        return json_encode($result);
    }

    public function getOrdersOnHold(): array
    {
        $order = new OperacionPausada();
        $where = [new DataBaseWhere('editable', true)];

        return $order->all($where);
    }

    public function getLastOrders(): array
    {
        $order = new OperacionPOS();
        $where = [new DataBaseWhere('idsesion', $this->arqueo->idsesion)];

        return $order->all($where);
    }

    public function placeOrder(Order $order)
    {

    }

    public function placeOrderOnHold(Order $order)
    {

    }
}