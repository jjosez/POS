<?php

namespace FacturaScripts\Plugins\POS\Model\Join;

use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Model\AttachedFile;
use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use JsonSerializable;

class ProductoVariante extends JoinModel implements JsonSerializable
{
    public float $priceWithTax = 0.0;
    public string $priceWithFormat = '0,00';
    public bool $isOutOfStock = false;
    public string $thumbnail = '';

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
            'codimpuesto' => 'MIN(P.codimpuesto)',
            'barcode' => 'V.codbarras',
            'description' => 'P.descripcion',
            'price' => 'V.precio',
            'stock' => 'SUM(S.disponible)',
            'detail' => 'CONCAT_WS(" - ", A1.descripcion, A2.descripcion, A3.descripcion, A4.descripcion)',
            'atribute1' => 'A1.descripcion',
            'atribute2' => 'A2.descripcion',
            'atribute3' => 'A3.descripcion',
            'atribute4' => 'A4.descripcion',
            'image_file' => 'MIN(IMG.idfile)',
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

    /**
     * Returns the current tax or the default one
     *
     * @return Impuesto
     */
    public function getTax(): Impuesto
    {
        return Impuestos::get($this->codimpuesto);
    }

    protected function loadFromData($data): void
    {
        parent::loadFromData($data);

        $iva = $this->getTax()->iva;

        $this->priceWithTax = $this->price * (100 + $iva) / 100;
        $this->priceWithFormat = Tools::number($this->priceWithTax);

        $this->isOutOfStock = ((int)$this->stock === 0)
            && ((int)$this->allow_no_stock !== 1);

        $this->addThumbnail();
    }

    protected function addThumbnail(): void
    {
        $this->thumbnail = '';

        if (!empty($this->image_file)) {
            $imageFile = new AttachedFile();
            if ($imageFile->load($this->image_file)) {
                $this->thumbnail = FS_ROUTE . $imageFile->url('download-permanent');
            }
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


    public function toArray(bool $withCalculated = true): array
    {
        $data = [];
        foreach (array_keys($this->getFields()) as $field_name) {
            $data[$field_name] = $this->{$field_name} ?? null;
        }

        if ($withCalculated) {
            $data['priceWithTax'] = $this->priceWithTax;
            $data['priceWithFormat'] = $this->priceWithFormat;
            $data['isOutOfStock'] = $this->isOutOfStock;
            $data['thumbnail'] = $this->thumbnail;
        }

        return $data;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray(true);
    }
}
