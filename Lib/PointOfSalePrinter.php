<?php

namespace FacturaScripts\Plugins\POS\Lib;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Dinamic\Lib\PrintingService;
use FacturaScripts\Dinamic\Model\Empresa;
use FacturaScripts\Dinamic\Model\FormatoTicket;
use FacturaScripts\Dinamic\Model\SesionPuntoVenta;

class PointOfSalePrinter
{
    public static function printRequest(
        SalesDocument $document,
        array $payments,
        ?FormatoTicket $format = null
    ): array {
        $voucher = new PointOfSaleVoucher($document, $payments, $format);

        return [
            'print_job_id' => PrintingService::newPrintJob($voucher)
        ];
    }

    public static function printRawRequest(
        SalesDocument $document,
        array $payments,
        ?FormatoTicket $format = null
    ): string {
        $voucher = new PointOfSaleVoucherRaw($document, $payments, $format);

        return $voucher->getResult();
    }

     public static function printCashupRequest(
         SesionPuntoVenta $session,
         Empresa $company,
         ?FormatoTicket $format = null
     ): array {
        $voucher = new PointOfSaleClosingVoucher($session, $company, $format);

        return [
        'print_job_id' => PrintingService::newPrintJob($voucher)
        ];
    }
}
