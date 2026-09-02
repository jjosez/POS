<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Cliente;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class OrdenPuntoVenta extends ModelClass
{
    use ModelTrait;

    public $codcliente;

    public $codigo;

    public $esdevolucion;

    public $fecha;

    public $idoperacion_original;

    public $hora;

    public $iddocumento;

    public $idoperacion;

    public $idsesion;

    public $tipodoc;

    /**
     * @var string
     */
    public $total;

    /**
     * @var string
     */
    public $totalFormatted;

    /**
     * @var string
     */
    public $nombrecliente;

    /**
     * @var bool
     */
    public $descuadre;

    /**
     * @var string
     */
    public $tipodocumento;

    /**
     * @var string
     */
    public $url;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
    }

    public static function primaryColumn(): string
    {
        return 'idoperacion';
    }

    public static function tableName(): string
    {
        return 'pos_operations';
    }

    /**
     * @param string $modelClass
     * @param string $code
     * @return bool
     */
    public function loadFromDocument(string $modelClass, string $code): bool
    {
        $where = [
            Where::eq('iddocumento', $code),
            Where::eq('tipodoc', $modelClass)
        ];

        return $this->loadWhere($where);
    }

    public function loadFromData(array $data = [], array $exclude = [], bool $sync = true): void
    {
        parent::loadFromData($data, $exclude, $sync);

        $this->descuadre = $this->testDescuadre();
        $this->tipodocumento = Tools::trans($this->tipodoc);
        $this->nombrecliente = $this->getSubject()->nombre;
        $this->totalFormatted = Tools::number($this->total);
        $this->url = $this->url('edit');
    }

    /**
     * @return PagoPuntoVenta[]
     */
    public function getPayments(): array
    {
        return PagoPuntoVenta::all([
            Where::eq('idoperacion', $this->idoperacion),
        ]);
    }

    public function getDocument(): SalesDocument
    {
        $className = '\\FacturaScripts\\Dinamic\\Model\\' . $this->tipodoc;

        /** @var SalesDocument $document */
        $document = new $className();
        $document->load($this->iddocumento);

        return $document;
    }

    public function getSubject(): Cliente
    {
        $cliente = new Cliente();
        $cliente->load($this->codcliente);

        return $cliente;
    }

    /**
     * Returns all orders from given session ID.
     *
     * @param string $sessionID
     * @return OrdenPuntoVenta[]
     */
    public static function allFromSession(string $sessionID): array
    {
        return self::all([
            Where::eq('idsesion', $sessionID)
        ], ['fecha' => 'DESC', 'hora' => 'DESC']);
    }

    protected function testDescuadre(): bool
    {
        $pagos = 0;

        foreach ($this->getPayments() as $payment) {
            $pagos += $payment->pagoNeto();
        }

        return Tools::floatcmp($this->total, $pagos);
    }

    /**
     * Returns the url where to see / modify the data.
     *
     * @param string $type
     * @param string $list
     *
     * @return string
     */
    public function url(string $type = 'auto', string $list = 'List'): string
    {
        $value = $this->iddocumento;
        $model = $this->tipodoc;
        switch ($type) {
            case 'edit':
                return is_null($value) ? 'Edit' . $model : 'Edit' . $model . '?code=' . $value;

            case 'list':
                return $list . $model;

            case 'new':
                return 'Edit' . $model;
        }

        /// default
        return empty($value) ? $list . $model : 'Edit' . $model . '?code=' . $value;
    }
}
