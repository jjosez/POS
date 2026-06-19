<?php

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;

class OpcionesTerminalPuntoVenta extends ModelClass
{
    use ModelTrait;

    public $id;
    public $idterminal;
    public $columns;
    public $nick;

    public function clear(): void
    {
        parent::clear();
        $this->idterminal = null;
        $this->nick = null;
    }

    /**
     * @return array
     */
    public function getColumnsAsArray(): array
    {
        $columns = json_decode($this->columns, true);

        return is_array($columns) ? $columns : [];
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'pos_terminal_options';
    }
}
