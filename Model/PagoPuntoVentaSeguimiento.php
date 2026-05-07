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

/**
 * Seguimiento de los pagos desde el POS y su ciclo de vida.
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
        return 'pagospos_tracking';
    }
}
