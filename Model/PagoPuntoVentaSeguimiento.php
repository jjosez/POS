<?php
/**
 * This file is part of FacturaScripts
 * Copyright (C) 2013-2022 Carlos Garcia Gomez <carlos@facturascripts.com>
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

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;

/**
 * Tracks payments made from the POS and their lifecycle.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class PagoPuntoVentaSeguimiento extends ModelClass
{
    use ModelTrait;

    /**
     * @var float
     */
    public $cantidad;

    /**
     * Payment method snapshot.
     *
     * @var string|null
     */
    public $codpago;

    /**
     * @var int
     */
    public $id;

    /**
     * id of document from
     *
     * @var int
     */
    public $idmodelfrom;

    /**
     * id of document to
     *
     * @var int
     */
    public $idmodelto;

    /**
     * id of the pos payment
     *
     * @var int
     */
    public $idpagopos;

    /**
     * Parent allocation in the document transformation chain.
     *
     * @var int|null
     */
    public $idparent;

    /**
     * Name of model from origin
     *
     * @var string
     */
    public $modelfrom;

    /**
     * Name of model to generate
     *
     * @var string
     */
    public $modelto;

    public function clear(): void
    {
        parent::clear();
        $this->cantidad = 0.0;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pos_payment_tracking';
    }

    /**
     * @return static[]
     */
    public static function allForDocument(string $model, int $id): array
    {
        return static::all([
            Where::eq('modelto', $model),
            Where::eq('idmodelto', $id),
        ], ['id' => 'ASC']);
    }

    /**
     * @return static[]
     */
    public static function allFromParent(int $parentId): array
    {
        return static::all([
            Where::eq('idparent', $parentId),
        ], ['id' => 'ASC']);
    }

    public static function findTransition(int $parentId, string $model, int $id): ?self
    {
        $rows = static::all([
            Where::eq('idparent', $parentId),
            Where::eq('modelto', $model),
            Where::eq('idmodelto', $id),
        ], [], 0, 1);

        return $rows[0] ?? null;
    }

    public function paymentMethodCode(): string
    {
        if (!empty($this->codpago)) {
            return (string)$this->codpago;
        }

        $payment = new PagoPuntoVenta();
        return $payment->load($this->idpagopos) ? (string)$payment->codpago : '';
    }
}
