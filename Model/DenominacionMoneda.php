<?php
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;

class DenominacionMoneda extends Base\ModelClass
{
    use Base\ModelTrait;

    public $clave;
    public $coddivisa;
    public $valor;

    public static function primaryColumn()
    {
        return 'clave';
    }

    public static function tableName()
    {
        return 'denominacionesmoneda';
    }
}
