# POS
POS plugin for [Facturascripts 2021](https://www.facturascripts.com/) 

## Política de cobro

En los documentos disponibles de cada terminal, configure **Política de cobro**:

- **Cobro completo obligatorio** (`required`): comportamiento predeterminado, también para configuraciones anteriores.
- **Cobro opcional** (`optional`): permite guardar el documento sin cobro o con un cobro parcial.

La política se aplica por terminal, modelo y serie; no depende de un modelo documental específico.

El request conserva `payments` exclusivamente para cobros reales (`method`, `amount`, `change`). El servidor valida el cobro completo para `required` y evita que los pagos superen el total en documentos `optional`.

En facturas con política `optional` se registran los recibos cobrados y un recibo pendiente por el saldo restante. Los pagos y el efectivo esperado incluyen únicamente dinero realmente recibido. Los consumidores de reportes pueden usar `SesionPuntoVenta::getSettlementSummary()`; `getPaymentsAmount()` mantiene el desglose por forma de pago.

Tras actualizar el plugin, ejecute su actualización habitual en FacturaScripts y recargue el POS.
