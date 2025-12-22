<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;

/**
 * Operaciones realizadas terminales POS.
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class TipoDocumentoPuntoVenta extends ModelClass
{
    use ModelTrait;

    /**
     * @var string
     */
    public $codserie;

    /**
     * @var int
     */
    public $idterminal;

    /**
     * @var bool
     */
    public $preferido;

    public $tipodoc;

    public $descripcion;

    public function clear(): void
    {
        parent::clear();
        $this->tipodoc = false;
        $this->preferido = false;
    }

    public function loadFromData(array $data = [], array $exclude = [], bool $sync = true): void
    {
        parent::loadFromData($data, $exclude, $sync);

        if (empty($this->descripcion)) {
            $this->descripcion = Tools::trans($this->tipodoc);
        }
    }

    public static function primaryColumn(): string
    {
        return 'id';
    }

    public static function tableName(): string
    {
        return 'tiposdocpos';
    }

    public function primaryDescription(): string
    {
        return $this->descripcion ?? '';
    }
}
