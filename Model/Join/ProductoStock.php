<?php

namespace FacturaScripts\Plugins\POS\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;

//class ProductoVariante extends JoinModel implements JsonSerializable
class ProductoStock extends JoinModel
{
    /**
    * @property-read $codalmacen
    * @property-read $referencia
    * @property-read $almacen
    * @property-read $disponible
    */

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

    protected function loadFromData($data)
    {
        foreach ($data as $field => $value) {
            $this->{$field} = $value;
        }
    }

    public function __set($name, $value)
    {
        $this->{$name}= $value;
    }


    /**
     * @return array
     */
    /*public function jsonSerialize(): array
    {
        return $this->values;
    }*/
}
