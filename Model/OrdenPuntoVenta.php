<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Model\Base;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\Cliente;
use FacturaScripts\Dinamic\Model\PagoPuntoVenta;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class OrdenPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    public $codcliente;

    public $codigo;

    public $fecha;

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
    public $nombrecliente;

    public function clear()
    {
        parent::clear();
        $this->fecha = date(self::DATE_STYLE);
        $this->hora = date(self::HOUR_STYLE);
    }

    public static function primaryColumn(): string
    {
        return 'idoperacion';
    }

    public static function tableName(): string
    {
        return 'operacionespos';
    }

    /**
     * @param string $doctype
     * @param string $code
     * @return bool
     */
    public function loadFromDocument(string $doctype, string $code): bool
    {
        $where = [
            new DataBaseWhere('iddocumento', $code),
            new DataBaseWhere('tipodoc', $doctype)
        ];

        return $this->loadFromCode('', $where);
    }

    public function loadFromData(array $data = [], array $exclude = [])
    {
        parent::loadFromData($data, $exclude);

        $this->descuadre = $this->testDescuadre();
        $this->tipodocumento = self::toolBox()::i18n()->trans($this->tipodoc);
        $this->nombrecliente = $this->getSubject()->nombre;
        $this->url = $this->url('edit');
    }

    /**
     * @return PagoPuntoVenta[]
     */
    public function getPayments(): array
    {
        $where = [new DataBaseWhere('idoperacion', $this->idoperacion)];

        return (new PagoPuntoVenta())->all($where);
    }

    public function getDocument(): SalesDocument
    {
        $className = '\\FacturaScripts\\Dinamic\\Model\\' . $this->tipodoc;

        /** @var SalesDocument $document */
        $document = new $className;

        return $document->get($this->iddocumento);
    }

    public function getSubject()
    {
        $cliente = new Cliente();
        $cliente->loadFromCode($this->codcliente);
        return $cliente;
    }

    /**
     * Returns all orders from given session ID.
     *
     * @param string $code
     *
     * @return OrdenPuntoVenta[]
     */
    public function allFromSession(string $code): array
    {
        $where = [new DataBaseWhere('idsesion', $code)];

        return $this->all($where);
    }

    protected function testDescuadre(): bool
    {
        $pagos = 0;

        foreach ($this->getPayments() as $payment) {
            $pagos += $payment->pagoNeto();
        }

        return self::toolBox()::utils()::floatcmp($this->total, $pagos);
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
