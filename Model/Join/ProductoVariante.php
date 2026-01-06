<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2022-2026 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model\Join;

use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Lib\MyFilesToken;
use FacturaScripts\Core\Model\Base\JoinModel;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;
use FacturaScripts\Dinamic\Model\Impuesto;
use FacturaScripts\Dinamic\Model\ProductoImagen;
use FacturaScripts\Dinamic\Model\Tarifa;
use FacturaScripts\Dinamic\Model\Variante;
use FacturaScripts\Plugins\TarifasAvanzadas\Model\TarifaFamilia;
use JsonSerializable;

class ProductoVariante extends JoinModel implements JsonSerializable
{
    public float $priceWithTax = 0.0;
    public string $priceWithFormat = '0,00';
    public bool $isOutOfStock = false;
    public string $thumbnail = '';

    /**
     * @inheritDoc
     */
    protected function getTables(): array
    {
        return [
            'variantes',
            'productos',
            'familias',
            'fabricantes',
            'attached_files'
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
            'cost' => 'V.coste',
            'stock' => 'SUM(S.disponible)',
            'detail' => 'CONCAT_WS(" - ", A1.descripcion, A2.descripcion, A3.descripcion, A4.descripcion)',
            'atribute1' => 'A1.descripcion',
            'atribute2' => 'A2.descripcion',
            'atribute3' => 'A3.descripcion',
            'atribute4' => 'A4.descripcion',
            'image_file' => 'MIN(IMG.idfile)',
            'image_path' => 'MIN(AF.path)',
            'image_filename' => 'MIN(AF.filename)',
            'allow_no_stock' => 'P.ventasinstock',
            'codfamilia' => 'P.codfamilia',
            'family' => 'F.descripcion',
            'brandname' => 'B.nombre'
        ];
    }

    /**
     * @inheritDoc
     */
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
            . ' LEFT JOIN productos_imagenes IMG ON IMG.idproducto = P.idproducto'
            . ' AND (IMG.referencia IS NULL OR IMG.referencia = V.referencia)'
            . ' LEFT JOIN attached_files AF ON AF.idfile = IMG.idfile'
            . ' LEFT JOIN familias F ON F.codfamilia = P.codfamilia'
            . ' LEFT JOIN fabricantes B ON B.codfabricante = P.codfabricante';
    }

    /**
     * Returns the current product variant model.
     *
     * @return Variante|null
     */
    public function getVariant(): ?Variante
    {
        $variant = new Variante();

        if ($variant->loadWhereEq('referencia', $this->code ?? '')) {
            return $variant;
        }

        return null;
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

    /**
     * Applies a customer rate to the product and recalculates custom fields.
     *
     * @param Tarifa $rate Customer rate to apply
     */
    public function applyRate($rate): void
    {
        $this->price = $rate->apply($this->cost ?? 0.0, $this->price ?? 0.0);
        $this->recalculateCustomPriceFields();
    }

    /**
     * Applies a family rate to the product and recalculates custom fields.
     *
     * @param TarifaFamilia $rate Family rate to apply
     */
    public function applyFamilyRate($rate): void
    {
        $this->price = $rate->apply($this->cost ?? 0.0, $this->price ?? 0.0);
        $this->recalculateCustomPriceFields();
    }

    /**
     * Recalculates custom fields (priceWithTax, priceWithFormat).
     * Should be called after modifying the price.
     */
    public function recalculateCustomPriceFields(): void
    {
        $iva = $this->getTax()->iva;
        $this->priceWithTax = $this->price * (100 + $iva) / 100;
        $this->priceWithFormat = Tools::number($this->priceWithTax);
    }

    protected function loadFromData($data): void
    {
        parent::loadFromData($data);

        $this->recalculateCustomPriceFields();

        $this->isOutOfStock = (int)$this->stock === 0 && (int)$this->allow_no_stock !== 1;

        $this->setThumbnail();
    }

    protected function setThumbnail(): void
    {
        $this->thumbnail = '';

        if (!empty($this->image_path) && !empty($this->image_filename)) {
            $this->thumbnail = $this->image_path . '?myft=' . MyFilesToken::get($this->image_path ?? '', true);
        }

        /*if (!empty($this->image_file)) {
            $imageFile = new AttachedFile();
            if ($imageFile->load($this->image_file)) {
                $this->thumbnail = FS_ROUTE . $imageFile->url('download-permanent');
            }
        }*/
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
