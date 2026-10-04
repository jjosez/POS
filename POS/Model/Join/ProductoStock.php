<?php

namespace FacturaScripts\Plugins\POS\Model\Join;

use FacturaScripts\Core\Template\JoinModel;
use JsonSerializable;

/**
 * @property-read $code
 * @property-read $codewarehouse
 * @property-read $stock
 * @property-read $warehouse
 */
class ProductoStock extends JoinModel implements JsonSerializable
{
    /**
     * @inheritDoc
     */
    protected function getTables(): array
    {
        return [
            'stocks',
            'almacenes'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getFields(): array
    {
        return [
            'code' => 'S.referencia',
            'codewarehouse' => 'S.codalmacen',
            'stock' => 'S.disponible',
            'warehouse' => 'A.nombre'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getSQLFrom(): string
    {
        return 'stocks S LEFT JOIN almacenes A ON S.codalmacen = A.codalmacen';
    }

    public function toArray(bool $withCalculated = true): array
    {
        $data = [];
        foreach (array_keys($this->getFields()) as $field_name) {
            $data[$field_name] = $this->{$field_name} ?? null;
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
