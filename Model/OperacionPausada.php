<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\LineaOperacionPausada;

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
    public $fecharegistro;


    /**
     * @var string
     */
    public $horaregistro;

    /**
     * @var string
     */
    public $generadocumento;

    /**
     * @return OperacionPausada[]
     */
    public function allOpened(?string $sessionID = null): array
    {
        $where = [new DataBaseWhere('editable', true)];

        if ($sessionID) {
            $where[] = new DataBaseWhere('idsesion', $sessionID);
        }

        return $this->all($where);
    }

    public function clear()
    {
        parent::clear();

        $this->fecharegistro = date(self::DATE_STYLE);
        $this->horaregistro = date(self::HOUR_STYLE);
    }

    public function completeDocument(): bool
    {
        $this->idestado = 3;
        return $this->save();
    }

    /**
     * Returns the lines associated with the paused operation.
     *
     * @return array
     */
    public function getLines(): array
    {
        $lineaModel = new LineaOperacionPausada();
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
     * @return LineaOperacionPausada
     */
    public function getNewLine(array $data = [], array $exclude = ['actualizastock', 'idlinea', 'idpausada']): LineaOperacionPausada
    {
        $newLine = new LineaOperacionPausada();
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
