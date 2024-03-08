<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Dinamic\Model\OperacionPausada;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

class PointOfSaleStorage
{
    /**
     * @param string $code
     * @return bool
     */
    public static function completePausedDocument(string $code): bool
    {
        $document = new OperacionPausada();

        if ($code && $document->loadFromCode($code)) {
            return $document->completeDocument();
        }
        return false;
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
}
