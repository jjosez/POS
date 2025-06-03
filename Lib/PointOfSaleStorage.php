<?php

namespace FacturaScripts\Plugins\POS\Lib;

use Exception;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Session;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\BorradorPuntoVenta;
use FacturaScripts\Dinamic\Model\MovimientoPuntoVenta;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;

class PointOfSaleStorage
{
    /**
     * @param SalesDocument $document
     * @return bool
     */
    public static function completeDraftDocument(SalesDocument $document): bool
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

    public static function getOrder(string $code): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        $order->loadFromCode($code);

        return $order;
    }

    public static function getOrderFromDocument(string $modelClass, string $code): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();

        Tools::log('POS')->debug('get-order-from-document' . $modelClass . ' ' . $code);
        if (false ===$order->loadFromDocument($modelClass, $code))
        {
            Tools::log('POS')->warning('order-not-found');
        }

        return $order;
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
    ): bool
    {
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
    ): bool
    {
        $sessionID = PointOfSaleSession::getSessionID();
        $session = PointOfSaleSession::getSessionModel();
        $nick = Session::user()->nick;

        $movment = new MovimientoPuntoVenta();

        $movment->idsesion = $session->idsesion;
        $movment->nickusuario = $session->nickusuario;
        $movment->descripcion = $description;
        $movment->total = $amount;

        if (false === $movment->save()) {
            return false;
        }

        $session->saldoesperado += $amount;

        return $session->save();
    }
}
