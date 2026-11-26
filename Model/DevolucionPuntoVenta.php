<?php

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

class DevolucionPuntoVenta extends ModelClass
{
    use ModelTrait;

    public const STATUS_OPEN = 'open';
    public const STATUS_COMPLETED = 'completed';

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
    public $estado;
    public $fechacompletado;
    public $horacompletado;

    public function clear(): void
    {
        parent::clear();
        $this->fecha = Tools::date();
        $this->hora = Tools::hour();
        $this->estado = self::STATUS_OPEN;
        $this->fechacompletado = null;
        $this->horacompletado = null;
    }

    public static function primaryColumn(): string
    {
        return 'iddevolucion';
    }

    public static function tableName(): string
    {
        return 'pos_refund_drafts';
    }

    public static function allFromSession(?string $sessionID = null): array
    {
        $where = empty($sessionID) ? [] : [Where::eq('idsesion', $sessionID)];

        return self::all($where, ['fecha' => 'DESC', 'hora' => 'DESC']);
    }

    public static function allOpenFromSession(?string $sessionID = null): array
    {
        $where = [Where::eq('estado', self::STATUS_OPEN)];
        if (!empty($sessionID)) {
            $where[] = Where::eq('idsesion', $sessionID);
        }

        return self::all($where, ['fecha' => 'DESC', 'hora' => 'DESC']);
    }

    public function isOpen(): bool
    {
        return self::STATUS_OPEN === $this->estado;
    }

    public function setAsCompleted(): bool
    {
        $this->estado = self::STATUS_COMPLETED;
        $this->fechacompletado = Tools::date();
        $this->horacompletado = Tools::hour();

        return $this->save();
    }
}
