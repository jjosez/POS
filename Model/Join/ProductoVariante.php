<?php

namespace FacturaScripts\Plugins\POS\Model\Join;

use FacturaScripts\Core\Model\Base\JoinModel;
use JsonSerializable;

//class ProductoVariante extends JoinModel implements JsonSerializable
class ProductoVariante extends JoinModel
{
    /**
    * @property-read $name
    * @property-read $description
    * @property-read $stock
    * @property-read $price
    * @property-read $atribute1
    * @property-read $atribute2
    * @property-read $atribute3
    * @property-read $atribute4
    *

    /**
     * @inheritDoc
     */
    protected function getTables(): array
    {
        return [
            'variantes',
            'productos'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getFields(): array
    {
        return [
            'code' => 'V.referencia',
            'description' => 'P.descripcion',
            'price' => 'V.precio',
            'stock' => 'S.disponible',
            'atribute1' => 'A1.descripcion',
            'atribute2' => 'A2.descripcion',
            'atribute3' => 'A3.descripcion',
            'atribute4' => 'A4.descripcion'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getSQLFrom(): string
    {
         return 'variantes V LEFT JOIN productos P ON V.idproducto = P.idproducto'
             . ' LEFT JOIN atributos_valores A1 ON V.idatributovalor1 = A1.id'
             . ' LEFT JOIN atributos_valores A2 ON V.idatributovalor2 = A2.id'
             . ' LEFT JOIN atributos_valores A3 ON V.idatributovalor3 = A3.id'
             . ' LEFT JOIN atributos_valores A4 ON V.idatributovalor4 = A4.id'
             . ' LEFT JOIN stocks S ON V.referencia = S.referencia';
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
