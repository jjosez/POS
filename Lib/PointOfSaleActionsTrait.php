<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Lib\PrintingService;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\OperacionPausada;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;
use FacturaScripts\Plugins\PrintTicket\Model\FormatoTicket;

trait PointOfSaleActionsTrait
{
    public static function printCashupTicket(
        SesionPuntoVenta $session,
        Empresa          $company,
        ?FormatoTicket   $format = null
    ) {
        $ticketBuilder = new PointOfSaleClosingVoucher($session, $company, $format);

        $cashupTicket = new PrintingService($ticketBuilder);
        $cashupTicket->savePrintJob();

        return $cashupTicket->getMessage();
    }

    public static function printDocumentTicketMobile(
        SalesDocument  $document,
        array          $payments,
        ?FormatoTicket $format = null
    ): string {
        $ticketBuilder = new PointOfSaleVoucherAndroid($document, $payments, $format);

        return $ticketBuilder->getResult();
    }

    public static function printOrderTicket(string $code, ?FormatoTicket $format = null): string
    {
        $order = self::getOrder($code);

        return self::printDocumentTicket($order->getDocument(), [], $format);
    }

    protected static function getOrder(string $code): OrdenPuntoVenta
    {
        $order = new OrdenPuntoVenta();
        $order->loadFromCode($code);

        return $order;
    }

    /**
     * @param SalesDocument $document
     * @param array $payments
     * @param FormatoTicket|null $format
     * @return string
     */
    public static function printDocumentTicket(
        SalesDocument  $document,
        array          $payments,
        ?FormatoTicket $format = null
    ): string {
        $ticketBuilder = new PointOfSaleVoucher($document, $payments, $format);

        $ticket = new PrintingService($ticketBuilder);
        $ticket->savePrintJob();

        return $ticket->getMessage();
    }

    /**
     * @param string $code
     * @return bool
     */
    protected static function completePausedDocument(string $code): bool
    {
        $document = new OperacionPausada();

        if ($code && $document->loadFromCode($code)) {
            return $document->completeDocument();
        }
        return true;
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
}
