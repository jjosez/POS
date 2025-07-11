<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Base\DataBase;
use FacturaScripts\Core\DataSrc\Impuestos;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\ProductoImagen;

class PointOfSaleProductVariant
{
    static function search(string $query, array $filters = []): array
    {
        $database = new DataBase();
        $where = self::buildSql($query, $filters);

        Tools::log('database')->warning($where);
        $rows = $database->select($where);

        foreach ($rows as &$row) {
            // IVA
            $iva = self::getIvaValue($row['codimpuesto']);
            $row['priceWithTax'] = round($row['price'] * (1 + $iva / 100), 2);
            $row['priceWithFormat'] = Tools::money($row['priceWithTax']);

            // Stock
            $row['isOutOfStock'] = (int)$row['stock'] === 0 && (int)$row['allow_no_stock'] !== 1;

            // Miniatura
            $row['thumbnail'] = '';
            if (!empty($row['imagen'])) {
                $img = new ProductoImagen();
                $img->idfile = $row['imagen_file'];
                $img->idproducto = $row['imagen_producto'];
                $img->referencia = $row['imagen_referencia'];
                $row['thumbnail'] = FS_ROUTE . $img->getThumbnail(150, 150, true);
            }
        }

        return $rows;
    }

    protected static function getSelectClause(): string
    {
        return 'SELECT P.idproducto AS id'
            . ', V.referencia AS code'
            . ', P.codimpuesto'
            . ', V.codbarras AS barcode'
            . ', P.descripcion AS description'
            . ', V.precio AS price'
            . ', SUM(S.disponible) AS stock'
            . ', CONCAT_WS(" - ", A1.descripcion, A2.descripcion, A3.descripcion, A4.descripcion) AS detail'
            . ', A1.descripcion AS atribute1'
            . ', A2.descripcion AS atribute2'
            . ', A3.descripcion AS atribute3'
            . ', A4.descripcion AS atribute4'
            . ', MIN(IMG.idfile) AS imagen_file'
            . ', MIN(IMG.referencia) AS imagen_referencia'
            . ', MIN(IMG.idproducto) AS imagen_producto'
            . ', MIN(IMG.idfile) AS imagen'
            . ', P.ventasinstock AS allow_no_stock';
    }

    protected static function getFromClause(): string
    {
        return 'FROM variantes V'
            . ' LEFT JOIN productos P ON V.idproducto = P.idproducto'
            . ' LEFT JOIN atributos_valores A1 ON V.idatributovalor1 = A1.id'
            . ' LEFT JOIN atributos_valores A2 ON V.idatributovalor2 = A2.id'
            . ' LEFT JOIN atributos_valores A3 ON V.idatributovalor3 = A3.id'
            . ' LEFT JOIN atributos_valores A4 ON V.idatributovalor4 = A4.id'
            . ' LEFT JOIN stocks S ON V.referencia = S.referencia'
            . ' LEFT JOIN productos_imagenes IMG ON IMG.idproducto = P.idproducto AND (IMG.referencia IS NULL OR IMG.referencia = V.referencia)';
    }

    protected static function groupByClause(): string
    {
        return 'GROUP BY V.referencia LIMIT 30';
    }

    protected static function buildSql(string $query, array $filters): string
    {
        $where = '';
        $familyWhere = '';

        if (!empty($filters['families'])) {
            $codes = implode(',', array_map(static fn($f) => "'" . addslashes($f['code']) . "'", $filters['families']));
            $familyWhere = " AND codfamilia IN ($codes)";
        }

        $query = mb_strtolower($query, 'UTF-8');
        $query = addslashes($query);

        $where = "WHERE (LOWER(V.codbarras) LIKE LOWER('%$query%')"
            . " OR LOWER(V.referencia) LIKE LOWER('%$query%')"
            . " OR LOWER(P.descripcion) LIKE LOWER('%$query%'))"
            . $familyWhere;

        $select = self::getSelectClause();
        $from = self::getFromClause();
        $group = self::groupByClause();

        return "$select $from $where $group";
    }

    protected static function getIvaValue(string $code): float
    {
        return Impuestos::get($code)->iva;
    }
}
