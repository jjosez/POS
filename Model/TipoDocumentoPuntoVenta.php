<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Model\Base;
use FacturaScripts\Dinamic\Model\FormaPago;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class TipoDocumentoPuntoVenta extends Base\ModelClass
{
    use Base\ModelTrait;

    public $codeserie;

    public $idterminal;
    public $preferido;

    public $tipodoc;

    public $descripcion;

    public function clear()
    {
        parent::clear();
        $this->tipodoc = false;
        $this->preferido = false;
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'tiposdocpos';
    }

    public function primaryDescription()
    {
        return $this->descripcion ?: self::toolBox()::i18n()->trans($this->tipodoc);
    }
}
