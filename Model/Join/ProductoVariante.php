<?php

namespace FacturaScripts\Plugins\POS\Model\Join;

use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Core\Model\Base\TaxRelationTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\ProductoImagen;

/**
 * @property bool $isOutOfStock
 * @property mixed|null $allow_no_stock
 */
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
     *
     * /**
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
            'atribute4' => 'A4.descripcion',
            'image_file' => ' MIN(IMG.idfile)',
            /*'image_referencia' => ' MIN(IMG.referencia)',
            'image_product' => 'MIN(IMG.idproducto)',
            'image' => 'MIN(IMG.id)',*/
            'allow_no_stock' => 'P.ventasinstock',
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
            . ' LEFT JOIN stocks S ON V.referencia = S.referencia'
            . ' LEFT JOIN productos_imagenes IMG ON IMG.idproducto = P.idproducto AND (IMG.referencia IS NULL OR IMG.referencia = V.referencia)';
    }

    protected function loadFromData($data): void
    {
        foreach ($data as $field => $value) {
            $this->{$field} = $value;
        }

        $this->priceWithTax = $this->price * (100 + $this->getTax()->iva) / 100;
        $this->priceWithFormat = Tools::number($this->priceWithTax);

        $this->isOutOfStock = (int)$this->stock === 0 && (int)$this->allow_no_stock !== 1;

        self::addThumbnail();
    }

    protected function addThumbnail(): void
    {
        $this->thumbnail = '';

        if (!empty($this->image_file)) {
            /* $image = new ProductoImagen();
             $image->id = $this->image;
             $image->idfile = $this->image_file;
             $image->idproducto = $this->image_product;
             $image->referencia = $this->image_reference;*/

            $imageFile = new AttachedFile();
            if ($imageFile->load($this->image_file)) {
                $this->thumbnail = FS_ROUTE . $imageFile->url('download-permanent');
            }

            //$this->thumbnail = FS_ROUTE . $image->getThumbnail(150, 150, true);
        }
    }

    /**
     * @return ProductoImagen[]
     */
    public static function getImages(string $id, string $code): array
    {
        $where = [Where::eq('referencia', $code)];

        if (!empty($id) && ($id !== 'undefined')) {
            $where[] = Where::orEq('referencia', null);
            $where[] = Where::eq('idproducto', $id);
        }

        return ProductoImagen::all($where);
    }

    public function __set($name, $value)
    {
        $this->{$name} = $value;
    }
}
