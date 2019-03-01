<?php
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;

class FraccionMoneda extends Base\ModelClass
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
        return 'fraccionesmoneda';
    }
}