<?php

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;

class OpcionesTerminalPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    public $id;
    public $idterminal;
    public $columns;
    public $nick;

    public function clear()
    {
        parent::clear();
        $this->idterminal = null;
        $this->nick = null;
    }

    /**
     * @return array|mixed
     */
    public function getColumnsAsArray(): array
    {
        return json_decode($this->columns, true) ?? [];
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'terminalespos_options';
    }
}
