<?php

namespace FacturaScripts\Plugins\POS\Lib;

use Exception;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Dinamic\Model\BorradorPuntoVenta;
use FacturaScripts\Dinamic\Model\MovimientoPuntoVenta;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

class PointOfSaleStorage
{
    /**
     * @param SalesDocument $document
     * @return bool
     */
    public static function completePausedDocument(SalesDocument $document): bool
    {
        $draft = new BorradorPuntoVenta();

        if (isset($document->idpausada) && $draft->loadFromCode($document->idpausada)) {
            return $draft->setAsCompleted();
        }

        return true;
    }

    /**
     * @param string $code
     * @return bool
     */
    public static function deleteDraftDocument(string $code): bool
    {
        $draft = new BorradorPuntoVenta();

        if ($code && $draft->loadFromCode($code)) {
            return $draft->delete();
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
     * @return BorradorPuntoVenta
     */
    public static function getDraftDocument(string $code): BorradorPuntoVenta
    {
        $document = new BorradorPuntoVenta();
        $document->loadFromCode($code);

        $document->codigo = null;
        $document->fecha = date($document::DATE_STYLE);
        $document->hora = date($document::HOUR_STYLE);

        return $document;
    }

    /**
     * @param string|null $sessionID
     * @return BorradorPuntoVenta[]
     * @throws Exception
     */
    public static function getDraftDocuments(?string $sessionID = null): array
    {
        return BorradorPuntoVenta::allOpened($sessionID);
    }

    public static function getOrders(string $sessionID = ''): array
    {
        if ('' !== $sessionID) {
            return OrdenPuntoVenta::allFromSession($sessionID);
        }

        return OrdenPuntoVenta::all();
    }

    public static function saveOrder(
        OrdenPuntoVenta $order,
        SalesDocument $document
    ): bool {
        $order->codigo = $document->codigo;
        $order->codcliente = $document->codcliente;
        $order->fecha = $document->fecha;
        $order->iddocumento = $document->primaryColumnValue();
        $order->idsesion = PointOfSaleSession::getSessionID();
        $order->tipodoc = $document->modelClassName();
        $order->total = $document->total;

        return $order->save();
    }

    public static function saveCashMovment(
        float $amount,
        string $description
    ): bool {
        $sessionID = PointOfSaleSession::getSessionID();
        $nick = Session::user()->nick;

        $movment = new MovimientoPuntoVenta();

        $movment->idsesion = $sessionID;
        $movment->nickusuario = $nick;
        $movment->descripcion = $description;
        $movment->total = $amount;

        return $movment->save();
    }
}
