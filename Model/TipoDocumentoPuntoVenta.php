<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use FacturaScripts\Core\Template\ModelClass;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Lib\Services\PaymentPolicy;

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

    public $payment_policy;

    public function clear(): void
    {
        parent::clear();
        $this->tipodoc = false;
        $this->preferido = false;
        $this->payment_policy = PaymentPolicy::REQUIRED->value;
    }

    public function test(): bool
    {
        if (PaymentPolicy::tryFrom((string)$this->payment_policy) === null) {
            Tools::log()->warning('payment-policy-invalid');
            return false;
        }

        return parent::test();
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
        return 'pos_document_types';
    }

    public function primaryDescription(): string
    {
        return $this->descripcion ?? '';
    }
}
