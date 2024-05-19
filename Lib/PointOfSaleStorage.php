<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Model\OperacionPausada;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;

class PointOfSaleStorage
{
    /**
     * @param SalesDocument $document
     * @return bool
     */
    public static function completePausedDocument(SalesDocument $document): bool
    {
        $posDocument = new OperacionPausada();

        if (isset($document->idpausada) && $posDocument->loadFromCode($document->idpausada)) {
            return $posDocument->completeDocument();
        }

        return true;
    }

    /**
     * @param string $code
     * @return bool
     */
    public static function deletePausedDocument(string $code): bool
    {
        $document = new OperacionPausada();

        if ($code && $document->loadFromCode($code)) {
            return $document->delete();
        }

        return false;
    }

    public static function getOrder(string $code): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        $order->loadFromCode($code);

        return $order;
    }

    /**
     * @param string $code
     * @return OperacionPausada
     */
    public static function getPausedDocument(string $code): OperacionPausada
    {
        $document = new OperacionPausada();
        $document->loadFromCode($code);

        $document->codigo = null;
        $document->fecha = date($document::DATE_STYLE);
        $document->hora = date($document::HOUR_STYLE);

        return $document;
    }

    /**
     * @param string|null $sessionID
     * @return OperacionPausada[]
     */
    public static function getPausedDocuments(?string $sessionID = null): array
    {
        $document = new OperacionPausada();

        return $document->allOpened($sessionID);
    }

    public static function getOrders(string $sessionId = ''): array
    {
        $order = new OrdenPuntoVenta();

        if ('' !== $sessionId) {
            return $order->allFromSession($sessionId);
        }

        return $order->all();
    }

    public static function saveOrder(
        OrdenPuntoVenta $order,
        SalesDocument $document,
        SesionPuntoVenta $session
    ): bool {
        $order->codigo = $document->codigo;
        $order->codcliente = $document->codcliente;
        $order->fecha = $document->fecha;
        $order->iddocumento = $document->primaryColumnValue();
        $order->idsesion = $session->primaryColumnValue();
        $order->tipodoc = $document->modelClassName();
        $order->total = $document->total;

        return $order->save();
    }
}
