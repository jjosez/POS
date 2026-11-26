<?php

namespace FacturaScripts\Plugins\POS\Extension\Lib;

use Closure;
use FacturaScripts\Core\Model\Base\SalesDocument;
use FacturaScripts\Core\Tools;
use FacturaScripts\Plugins\POS\Lib\Services\Payments;
use FacturaScripts\Plugins\POS\Lib\Services\PaymentTracking;
use Throwable;

final class BusinessDocumentGenerator
{
    public function generateTrue(): Closure
    {
        return function ($source, $lines, $quantity, $properties, $target): bool {
            if (!$source instanceof SalesDocument || !$target instanceof SalesDocument) {
                return true;
            }

            try {
                $tracking = new PaymentTracking();
                $documents = [
                    $source->modelClassName() . ':' . $source->id() => $source,
                ];
                if (method_exists($target, 'parentDocuments')) {
                    foreach ($target->parentDocuments() as $parent) {
                        if ($parent instanceof SalesDocument) {
                            $documents[$parent->modelClassName() . ':' . $parent->id()] = $parent;
                        }
                    }
                }

                $rows = [];
                foreach ($documents as $parent) {
                    foreach ($tracking->propagate($parent, $target) as $row) {
                        $rows[(int)$row->id] = $row;
                    }
                }

                if ($target->modelClassName() === 'FacturaCliente' && $rows !== []) {
                    (new Payments())->replaceInvoiceReceipts(
                        $target,
                        $tracking->groupByPaymentMethod(array_values($rows)),
                        true
                    );
                }

                return true;
            } catch (Throwable $error) {
                try {
                    (new PaymentTracking())->forget($target);
                } catch (Throwable $cleanupError) {
                    Tools::log('POS')->warning($cleanupError->getMessage());
                }
                if ($target->exists()) {
                    $target->delete();
                }
                Tools::log('POS')->warning($error->getMessage());
                throw $error;
            }
        };
    }
}
