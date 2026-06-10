<?php

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

class DevolucionPuntoVenta extends ModelClass
{
    use ModelTrait;

    public $iddevolucion;
    public $idoperacion_original;
    public $idsesion;
    public $nickusuario;
    public $lineas;
    public $codigo;
    public $fecha;
    public $hora;
    public $total;
    public $codcliente;
    public $nombrecliente;
    public $codigo_original;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
    }

    public static function primaryColumn(): string
    {
        return 'iddevolucion';
    }

    public static function tableName(): string
    {
        return 'pos_refunds_drafts';
    }

    public static function allFromSession(string $sessionID): array
    {
        return self::all([/*
            Where::eq('idsesion', $sessionID)*/
        ], ['fecha' => 'DESC', 'hora' => 'DESC']);
    }
}
