<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;

class LineaBorradorPuntoVenta extends Base\SalesDocumentLine
{

    use Base\ModelTrait;

    /**
     * Paused Operation ID of this line.
     *
     * @var int
     */
    public $idpausada;

    public $thumbnail;

    /**
     * 
     * @return string
     */
    public function documentColumn(): string
    {
        return 'idpausada';
    }

    /**
     * 
     * @return BorradorPuntoVenta
     */
    public function getDocument(): BorradorPuntoVenta
    {
        $document = new BorradorPuntoVenta();
        $document->loadFromCode($this->idpausada);

        return $document;
    }

    /**
     * This function is called when creating the model table. Returns the SQL
     * that will be executed after the creation of the table. Useful to insert values
     * default.
     *
     * @return string
     */
    public function install(): string
    {
        /// needed dependency
        new BorradorPuntoVenta();

        return parent::install();
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'lineaspausadaspos';
    }
}
