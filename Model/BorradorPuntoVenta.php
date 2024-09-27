<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;

class BorradorPuntoVenta extends Base\SalesDocument
{
    use Base\ModelTrait;

    const NEWLINE_EXCLUDED_FIELDS = ['actualizastock', 'idlinea', 'idpausada'];

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
     * @var string
     */
    public $rowcolor;

    /**
     * @return BorradorPuntoVenta[]
     * @throws \Exception
     */
    public static function allOpened(?string $sessionID = null): array
    {
        //$where = [Where::eq('editable', true)];
        $query = [
            new DataBaseWhere('editable', true)
        ];

        if ($sessionID) {
            $query[] = new DataBaseWhere('idsesion', $sessionID);
            //$where[] = Where::eq('idsesion', $sessionID);
        }

        //return self::table()->where($where)->get() ?? [];
        return self::all($query);
    }

    public static function allCompleted(?string $sessionID = null): array
    {
        $query = [
            new DataBaseWhere('editable', false)
        ];

        if ($sessionID) {
            $query[] = new DataBaseWhere('idsesion', $sessionID);
        }

        return self::all($query, [], 0, 1000);
    }

    public function clear()
    {
        parent::clear();

        $this->fecharegistro = date(self::DATE_STYLE);
        $this->horaregistro = date(self::HOUR_STYLE);
    }

    public function loadFromData(array $data = [], array $exclude = [])
    {
        parent::loadFromData($data, $exclude);

        $this->setListRowColor();
    }

    public function setAsCompleted(): bool
    {
        foreach ($this->getAvailableStatus() as $status) {
            if ($status->nombre === 'Completado') {
                $this->idestado = $status->idestado;
                break;
            }
        }

        return $this->save();
    }

    /**
     * Returns the lines associated with the paused operation.
     *
     * @return array
     */
    public function getLines(): array
    {
        $lineaModel = new LineaBorradorPuntoVenta();
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
     * @return LineaBorradorPuntoVenta
     */
    public function getNewLine(array $data = [], array $exclude = self::NEWLINE_EXCLUDED_FIELDS): LineaBorradorPuntoVenta
    {
        $newLine = new LineaBorradorPuntoVenta();
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

    protected function setListRowColor()
    {
        $this->rowcolor = $this->total <= 0 ? 'yellow' : 'slate';

        $this->pipe('setListRowColor');
    }
}
