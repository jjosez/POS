<?php

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;

class AjustePuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    public $id;
    public $idterminal;
    public $decimals;
    public $fieldcode;
    public $nick;
    public $readonly;
    public $type;
    public $tittle;

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'camposterminalspos';
    }
}
