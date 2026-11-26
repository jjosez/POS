<?php

namespace FacturaScripts\Plugins\POS\Lib\Services;

use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Dinamic\Model\OrdenPuntoVenta;
use FacturaScripts\Dinamic\Model\PagoPuntoVentaSeguimiento;
use FacturaScripts\Plugins\POS\Model\PagoPuntoVenta;
use RuntimeException;

/**
 * Preserves the allocation of POS payments through document transformations.
 */
final class PaymentTracking
{
    public function registerInitial(SalesDocument $document, PagoPuntoVenta $payment): PagoPuntoVentaSeguimiento
    {
        $documentRows = PagoPuntoVentaSeguimiento::allForDocument(
            $document->modelClassName(),
            (int)$document->id()
        );
        foreach ($documentRows as $row) {
            if ((int)$row->idpagopos === (int)$payment->idpago && empty($row->idparent)) {
                return $row;
            }
        }

        $tracking = new PagoPuntoVentaSeguimiento();
        $tracking->cantidad = $payment->pagoNeto();
        $tracking->codpago = $payment->codpago;
        $tracking->idpagopos = $payment->idpago;
        $tracking->idmodelfrom = $document->id();
        $tracking->idmodelto = $document->id();
        $tracking->modelfrom = $document->modelClassName();
        $tracking->modelto = $document->modelClassName();

        if (!$tracking->save()) {
            throw new RuntimeException('payment-tracking-save-error');
        }

        return $tracking;
    }

    /**
     * Propagates the source allocations to a transformed document.
     *
     * @return PagoPuntoVentaSeguimiento[]
     */
    public function propagate(SalesDocument $source, SalesDocument $target): array
    {
        $sourceRows = $this->ensureDocumentTracking($source);
        if ($sourceRows === []) {
            return [];
        }

        $ratio = self::documentRatio((float)$source->total, (float)$target->total);
        $decimals = (int)Tools::settings('default', 'decimals', 2);
        $result = [];

        foreach ($sourceRows as $sourceRow) {
            $existing = PagoPuntoVentaSeguimiento::findTransition(
                (int)$sourceRow->id,
                $target->modelClassName(),
                (int)$target->id()
            );
            if ($existing !== null) {
                $result[] = $existing;
                continue;
            }

            $allocated = 0.0;
            foreach (PagoPuntoVentaSeguimiento::allFromParent((int)$sourceRow->id) as $child) {
                if (!$this->targetDocumentExists($child)) {
                    if (!$child->delete()) {
                        throw new RuntimeException('payment-tracking-delete-error');
                    }
                    continue;
                }
                $allocated += abs((float)$child->cantidad);
            }

            $available = max(0.0, abs((float)$sourceRow->cantidad) - $allocated);
            $amount = min(
                $available,
                round(abs((float)$sourceRow->cantidad) * $ratio, $decimals)
            );
            if ($amount <= 0.0) {
                continue;
            }

            $tracking = new PagoPuntoVentaSeguimiento();
            $tracking->cantidad = (float)$sourceRow->cantidad < 0 ? -$amount : $amount;
            $tracking->codpago = $sourceRow->paymentMethodCode();
            $tracking->idpagopos = $sourceRow->idpagopos;
            $tracking->idparent = $sourceRow->id;
            $tracking->idmodelfrom = $source->id();
            $tracking->idmodelto = $target->id();
            $tracking->modelfrom = $source->modelClassName();
            $tracking->modelto = $target->modelClassName();

            if (!$tracking->save()) {
                throw new RuntimeException('payment-tracking-save-error');
            }
            $result[] = $tracking;
        }

        return $result;
    }

    /**
     * Creates initial tracking on demand for POS documents saved before
     * payment tracking was enabled.
     *
     * @return PagoPuntoVentaSeguimiento[]
     */
    public function ensureDocumentTracking(SalesDocument $document): array
    {
        $rows = PagoPuntoVentaSeguimiento::allForDocument(
            $document->modelClassName(),
            (int)$document->id()
        );

        $order = new OrdenPuntoVenta();
        if (!$order->loadFromDocument($document->modelClassName(), (string)$document->id())) {
            return $rows;
        }

        // Complete partially initialized tracking too. registerInitial() is
        // idempotent and skips payments that already have an initial row.
        foreach ($order->getPayments() as $payment) {
            $this->registerInitial($document, $payment);
        }

        return PagoPuntoVentaSeguimiento::allForDocument(
            $document->modelClassName(),
            (int)$document->id()
        );
    }

    private function targetDocumentExists(PagoPuntoVentaSeguimiento $tracking): bool
    {
        if (empty($tracking->modelto) || empty($tracking->idmodelto)) {
            return false;
        }

        $className = '\\FacturaScripts\\Dinamic\\Model\\' . $tracking->modelto;
        if (!class_exists($className)) {
            return false;
        }

        $document = new $className();
        return $document instanceof SalesDocument
            && $document->load($tracking->idmodelto);
    }

    public function forget(SalesDocument $document): void
    {
        foreach (PagoPuntoVentaSeguimiento::allForDocument(
            $document->modelClassName(),
            (int)$document->id()
        ) as $row) {
            if (!$row->delete()) {
                throw new RuntimeException('payment-tracking-delete-error');
            }
        }
    }

    public static function documentRatio(float $sourceTotal, float $targetTotal): float
    {
        if (abs($sourceTotal) < 0.000001) {
            return 1.0;
        }

        return min(1.0, abs($targetTotal / $sourceTotal));
    }

    /**
     * @param PagoPuntoVentaSeguimiento[] $rows
     * @return array<string, float>
     */
    public function groupByPaymentMethod(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $method = $row->paymentMethodCode();
            if ($method === '') {
                throw new RuntimeException('payment-tracking-method-not-found');
            }
            $result[$method] = ($result[$method] ?? 0.0) + (float)$row->cantidad;
        }

        return $result;
    }
}
