<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\LineaOperacionPausada as LineaOperacion;

class OperacionPausada extends Base\SalesDocument
{

    use Base\ModelTrait;

    /**
     * Primary key. Integer.
     *
     * @var int
     */
    public $idpausada;

    /**
     * @var string
     */
    public $generadocumento;

    /**
     * @return OperacionPausada[]
     */
    public function allOpen()
    {
        $where = [new DataBaseWhere('editable', true)];

        return $this->all($where);
    }

    /**
     * Returns the lines associated with the paused operation.
     *
     * @return array
     */
    public function getLines(): array
    {
        $lineaModel = new LineaOperacion();
        $where = [new DataBaseWhere('idpausada', $this->idpausada)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        return $lineaModel->all($where, $order, 0, 0);
    }

    /**
     * Returns a new line for the document.
     * 
     * @param array $data
     * @param array $exclude
     * 
     * @return LineaOperacion
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idpausada'])
    {
        $newLine = new LineaOperacion();
        $newLine->idpausada = $this->idpausada;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = 0;

        $newLine->loadFromData($data, $exclude);
        return $newLine;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idpausada';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'pausadaspos';
    }
}
