<?php

namespace FacturaScripts\Plugins\POS\Model\Join;

use FacturaScripts\Core\Base\DataBase\DataBaseWhere;
use FacturaScripts\Core\Base\ToolBox;
use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Core\Model\Base\TaxRelationTrait;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use JsonSerializable;

//class ProductoVariante extends JoinModel implements JsonSerializable

class ProductoVariante extends JoinModel
{
    use TaxRelationTrait;

    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $code;

    /**
     * @var float
     */
    public $price;

    /**
     * @var float
     */
    public $priceWithTax;

    /**
     * @var string
     */
    public $priceWithFormat;

    /**
     * @var string
     */
    public $thumbnail;

    /**
    * @property-read $name
    * @property-read $barcode
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
            'id' => 'P.idproducto',
            'code' => 'V.referencia',
            'codimpuesto' => 'P.codimpuesto',
            'barcode' => 'V.codbarras',
            'description' => 'P.descripcion',
            'price' => 'V.precio',
            'stock' => 'SUM(S.disponible)',
            'detail' => 'CONCAT_WS(" - ", A1.descripcion, A2.descripcion, A3.descripcion, A4.descripcion)',
            'atribute1' => 'A1.descripcion',
            'atribute2' => 'A2.descripcion',
            'atribute3' => 'A3.descripcion',
            'atribute4' => 'A4.descripcion'
        ];
    }

    protected function getGroupFields(): string
    {
        return 'V.referencia';
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

        $this->priceWithTax = $this->price  * (100 + $this->getTax()->iva) / 100;
        $this->priceWithFormat = ToolBox::coins()::format($this->priceWithTax);
        $this->thumbnail = self::getThumbnailUrl($this->id, $this->code);
    }

    /**
     * @return ProductoImagen[]
     */
    public static function getImages(string $id, string $code): array
    {
        $where = [
            new DataBaseWhere('idproducto', $id),
            new DataBaseWhere('referencia', null, 'IS', 'AND'),
            new DataBaseWhere('referencia', $code, '=', 'OR')
        ];

        return (new ProductoImagen())->all($where);
    }

    public static function getThumbnailUrl(string $id, string $code): string
    {
        $productImages = self::getImages($id, $code);
        return $productImages ? $productImages[0]->url('download-permanent') : '';
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
