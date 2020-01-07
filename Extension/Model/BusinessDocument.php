<?php
/**
 * This file is part of POS plugin for FacturaScripts
 * Copyright (C) 2019 Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */
namespace FacturaScripts\Plugins\POS\Extension\Model;

/**
 * Controller to process Point of Sale Operations
 *
 * @author Juan José Prieto Dzul <juanjoseprieto88@gmail.com>
 */
class BusinessDocument
{
    public function getNewProductLine($reference)
    {
        return function() {
            $this->toolBox()->log()->notice('Test Extension');
        };  
    }

    public function getNewProductLine($reference)
    {
        $newLine = $this->getNewLine();

        $variant = new Variante();
        $where = [new DataBaseWhere('codbarras', $this->toolBox()->utils()->noHtml($reference))];
        if ($variant->loadFromCode('', $where)) {
            $product = $variant->getProducto();
            $impuesto = $product->getImpuesto();

            $newLine->cantidad = 1;
            $newLine->codimpuesto = $impuesto->codimpuesto;
            $newLine->descripcion = $variant->description();
            $newLine->idproducto = $product->idproducto;
            $newLine->iva = $impuesto->iva;
            $newLine->pvpunitario = isset($this->tarifa) ? $this->tarifa->apply($variant->coste, $variant->precio) : $variant->precio;
            $newLine->recargo = $impuesto->recargo;
            $newLine->referencia = $variant->referencia;

            /// allow extensions
            $this->pipe('getNewProductLine', $newLine, $variant, $product);
        }

        return $newLine;
    }
}