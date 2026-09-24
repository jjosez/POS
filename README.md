# POS
POS plugin for [Facturascripts 2021](https://www.facturascripts.com/) 

## Ventas a cuenta cliente

En los documentos disponibles de cada terminal, configure **Política de cobro**:

- **Cobro completo obligatorio** (`required`): comportamiento predeterminado, también para configuraciones anteriores.
- **Cuenta cliente** (`customer-account`): permite cobrar todo, una parte o nada. El checkout calcula automáticamente el importe restante a cuenta.

Para dejar un importe a cuenta debe seleccionarse un cliente existente distinto del cliente predeterminado de la terminal. La política se aplica por terminal, modelo y serie; no depende de un modelo documental específico.

El request conserva `payments` exclusivamente para cobros reales (`method`, `amount`, `change`) y envía `customerAccountAmount` por separado. El servidor valida:

```text
SUM(amount - change) + customerAccountAmount = total del documento
```

La operación guarda `customer_account_amount` como importe histórico originado, no como saldo vivo del cliente. Los pagos y el efectivo esperado no incluyen ese importe. La lista de operaciones muestra cobrado y cuenta cliente por separado. Los consumidores de reportes pueden usar `SesionPuntoVenta::getSettlementSummary()`; `getPaymentsAmount()` mantiene únicamente cobros reales.

En ventas directas de facturas se registran recibos cobrados y un recibo pendiente por el importe a cuenta, utilizando los modelos de FacturaScripts.

Esta versión no implementa conversiones de documentos ni cartera. Las devoluciones de operaciones con importe a cuenta se rechazan para evitar reembolsar importes no cobrados. Los tickets X/Z externos pueden incorporar el resumen informativo mediante la API indicada.

Tras actualizar el plugin, ejecute su actualización habitual en FacturaScripts para añadir las columnas nuevas y recargue el POS.
