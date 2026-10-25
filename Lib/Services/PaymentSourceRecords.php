<?php

declare(strict_types=1);

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Dinamic\Model\FuentePagoPuntoVenta;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Plugins\POS\Lib\Core\PaymentSource\PaymentSourceApplication;

final class PaymentSourceRecords
{
    /**
     * @param PaymentSourceApplication[] $applications
     */
    public function saveApplications(OrdenPuntoVenta $order, array $applications): bool
    {
        foreach ($applications as $application) {
            if (!$application->result->approved || $application->consumedAmount() <= 0.0) {
                continue;
            }

            $record = new FuentePagoPuntoVenta();
            $record->idoperacion = $order->idoperacion;
            $record->idsesion = $order->idsesion;
            $record->code = $application->entry->code;
            $record->label = $application->label;
            $record->reference = $application->entry->reference;
            $record->requested_amount = $application->entry->amount;
            $record->consumed_amount = $application->consumedAmount();
            $record->metadata = $application->result->metadata === []
                ? null
                : json_encode(
                    $application->result->metadata,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                );

            if (!$record->save()) {
                return false;
            }
        }

        return true;
    }
}
