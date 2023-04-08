<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Lib\PrintingService;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\OperacionPausada;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;

trait PointOfSaleActionsTrait
{
    /**
     * @param string $code
     * @return bool
     */
    protected static function completePausedDocument(string $code): bool
    {
        $document = new OperacionPausada();

        if ($code && $document->loadFromCode($code)) {
            $document->idestado = 3;

            return $document->save();
        }
        return false;
    }

    /**
     * @param string $code
     * @return bool
     */
    protected static function deletePausedDocument(string $code): bool
    {
        $document = new OperacionPausada();

        if ($code && $document->loadFromCode($code)) {
            return $document->delete();
        }

        return false;
    }

    protected static function getOrder(string $code): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        $order->loadFromCode($code);

        return $order;
    }

    /**
     * @param string $code
     * @return OperacionPausada
     */
    protected static function getPausedDocument(string $code): OperacionPausada
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
    protected static function getPausedDocuments(?string $sessionID = null): array
    {
        $document = new OperacionPausada();

        return $document->allOpened($sessionID);
    }

    /**
     * @param string $sessionID
     * @return OrdenPuntoVenta[]
     */
    protected static function getSessionOrders(string $sessionID): array
    {
        $order = new OrdenPuntoVenta();

        return $order->allFromSession($sessionID);
    }

    public static function printCashupTicket(SesionPuntoVenta $session, Empresa $company)
    {
        $ticketBuilder = new PointOfSaleClosingVoucher($session, $company);

        $cashupTicket = new PrintingService($ticketBuilder);
        $cashupTicket->savePrintJob();

        return $cashupTicket->getMessage();
    }

    /**
     * @param SalesDocument $document
     * @param array $payments
     * @return mixed|string
     */
    public static function printDocumentTicket(SalesDocument $document, array $payments)
    {
        $ticketBuilder = new PointOfSaleVoucher($document, $payments);

        $ticket = new PrintingService($ticketBuilder);
        $ticket->savePrintJob();

        return $ticket->getMessage();
    }

    public static function printOrderTicket(string $code)
    {
        $order = self::getOrder($code);

        return self::printDocumentTicket($order->getDocument(), []);
    }
}
