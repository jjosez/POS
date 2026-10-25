<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Session;
use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Immutable audit record for a Payment Source applied to a POS operation.
 */
class FuentePagoPuntoVenta extends ModelClass
{
    use ModelTrait;

    public $id;
    public $idoperacion;
    public $idsesion;
    public $code;
    public $label;
    public $reference;
    public $requested_amount;
    public $consumed_amount;
    public $metadata;
    public $nick;
    public $createdat;

    public function clear(): void
    {
        parent::clear();
        $this->requested_amount = 0.0;
        $this->consumed_amount = 0.0;
        $this->nick = Session::user()->nick;
        $this->createdat = Tools::dateTime();
    }

    public function install(): string
    {
        new OrdenPuntoVenta();
        new SesionPuntoVenta();

        return parent::install();
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pos_payment_source_entries';
    }

    public function metadataData(): array
    {
        if (!is_string($this->metadata) || $this->metadata === '') {
            return [];
        }

        $decoded = json_decode($this->metadata, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function descripcion(): string
    {
        return empty($this->reference)
            ? (string)$this->label
            : $this->label . ' (' . $this->reference . ')';
    }
}
