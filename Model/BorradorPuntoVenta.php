<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2020 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */

namespace FacturaScripts\Plugins\POS\Model;

use Exception;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Model\ProductoImagen;
use FacturaScripts\Core\Template\ModelTrait;
use FacturaScripts\Core\Tools;
use FacturaScripts\Core\Where;

class BorradorPuntoVenta extends SalesDocument
{
    use ModelTrait;

    const NEWLINE_EXCLUDED_FIELDS = ['actualizastock', 'idlinea', 'idpausada'];

    /**
     * Primary key. Integer.
     *
     * @var int
     */
    public $idpausada;

    /**
     * ID of the session that created this document.
     *
     * @var int
     */
    public $idsesion;


    /**
     * @var string
     */
    public $fecharegistro;


    /**
     * @var string
     */
    public $horaregistro;

    /**
     * @var string
     */
    public $generadocumento;

    /**
     * @var string
     */
    public $rowcolor;

    /**
     * @return BorradorPuntoVenta[]
     * @throws Exception
     */
    public static function allOpened(?string $sessionID = null): array
    {
        $where = [Where::eq('editable', true)];

        if ($sessionID) {
            $where[] = Where::eq('idsesion', $sessionID);
        }

        return self::all($where, ['fecha' => 'DESC', 'hora' => 'DESC', 'codigo' => 'DESC']);
    }

    public static function allCompleted(?string $sessionID = null): array
    {
        $where = [Where::eq('editable', false)];

        if ($sessionID) {
            $where[] = Where::eq('idsesion', $sessionID);
        }

        return self::all($where);
    }

    public function clear(): void
    {
        parent::clear();

        $this->fecharegistro = Tools::date();
        $this->horaregistro = Tools::hour();
    }

    public function loadFromData(array $data = [], array $exclude = [], bool $sync = true): void
    {
        parent::loadFromData($data, $exclude, $sync);

        $this->setListRowColor();
    }

    /**
     * Override toArray to ensure critical fields are always included.
     *
     * @param bool $dynamic_attributes
     * @return array
     */
    public function toArray(bool $dynamic_attributes = false): array
    {
        $data = parent::toArray($dynamic_attributes);

        // Ensure primary key is always present even if not in model fields
        if (!isset($data['idpausada']) && $this->idpausada) {
            $data['idpausada'] = $this->idpausada;
        }

        return $data;
    }

    public function setAsCompleted(): bool
    {
        foreach ($this->getAvailableStatus() as $status) {
            if ($status->nombre === 'Completado') {
                $this->idestado = $status->idestado;
                break;
            }
        }

        return $this->save();
    }

    /**
     * Returns the lines associated with the paused operation.
     *
     * @return array
     */
    public function getLines(): array
    {
        $where = [Where::eq('idpausada', $this->idpausada)];
        $order = ['orden' => 'DESC', 'idlinea' => 'ASC'];

        $lines = LineaBorradorPuntoVenta::all($where, $order);
        $this->loadThumbnailsForLines($lines);

        return $lines;
    }

    /**
     * Returns a new line for the document.
     *
     * @param array $data
     * @param array $exclude
     *
     * @return LineaBorradorPuntoVenta
     */
    public function getNewLine(array $data = [], array $exclude = self::NEWLINE_EXCLUDED_FIELDS): LineaBorradorPuntoVenta
    {
        $newLine = new LineaBorradorPuntoVenta();
        $newLine->idpausada = $this->idpausada;
        $newLine->irpf = $this->irpf;
        $newLine->actualizastock = 0;

        $newLine->loadFromData($data, $exclude);
        return $newLine;
    }

    /**
     * Returns the name of the column that is the model's primary key.
     *
     * @return string
     */
    public static function primaryColumn(): string
    {
        return 'idpausada';
    }

    /**
     * Returns the name of the table that uses this model.
     *
     * @return string
     */
    public static function tableName(): string
    {
        return 'pos_drafts';
    }

    protected function setListRowColor(): void
    {
        $this->rowcolor = $this->total <= 0 ? 'yellow' : 'slate';

        $this->pipe('setListRowColor');
    }

    protected function loadThumbnailsForLines(array $lines): void
    {
        $productIds = [];

        foreach ($lines as $line) {
            if (empty($line->idproducto)) {
                continue;
            }

            $productIds[$line->idproducto] = $line->idproducto;
        }

        if (empty($productIds)) {
            return;
        }

        $where = [Where::in('idproducto', $productIds)];
        $images = ProductoImagen::all($where);

        $imagesByProduct = [];

        foreach ($images as $image) {
            $imagesByProduct[$image->idproducto] ??= $image;
        }

        foreach ($lines as $line) {
            $image = $imagesByProduct[$line->idproducto] ?? null;

            if ($image === null) {
                continue;
            }

            $line->thumbnail = FS_ROUTE . $image->getThumbnail(150, 150, true);
        }
    }
}
