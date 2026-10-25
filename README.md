# POS
POS plugin for [Facturascripts 2021](https://www.facturascripts.com/) 

## Política de cobro

En los documentos disponibles de cada terminal, configure **Política de cobro**:

- **Cobro completo obligatorio** (`required`): comportamiento predeterminado, también para configuraciones anteriores.
- **Cobro opcional** (`optional`): permite guardar el documento sin cobro o con un cobro parcial.

La política se aplica por terminal, modelo y serie; no depende de un modelo documental específico.

El request conserva `payments` exclusivamente para cobros reales (`method`, `amount`, `change`). El servidor valida el cobro completo para `required` y evita que los pagos superen el total en documentos `optional`.

En facturas con política `optional` se registran los recibos cobrados y un recibo pendiente por el saldo restante. Los pagos y el efectivo esperado incluyen únicamente dinero realmente recibido. Los consumidores de reportes pueden usar `SesionPuntoVenta::getSettlementSummary()`; `getPaymentsAmount()` mantiene el desglose por forma de pago.

## Payment Sources

Los Payment Sources permiten que plugins externos cubran una venta mediante crédito, cupones, vales, gift cards u otros recursos sin convertirlos en formas de pago nativas ni afectar el efectivo esperado.

Cada plugin registra un provider:

```php
$this->context->paymentSources()->register(
    new CouponPaymentSource()
);
```

El provider puede publicar un saldo único:

```php
return PaymentSourceAvailability::allowed(300.00);
```

o varios recursos concretos que POS mostrará como botones independientes:

```php
return PaymentSourceAvailability::withOptions([
    new PaymentSourceOption(
        reference: 'X25CAMPA',
        label: 'CUPÓN 50.00',
        availableAmount: 50.00,
    ),
    new PaymentSourceOption(
        reference: 'VALE-1000',
        label: 'VALE 1000',
        availableAmount: 1000.00,
    ),
]);
```

Las opciones pueden compartir el mismo `code`: POS las distingue mediante `code + reference`. Cada selección genera un `PaymentSourceEntry` y el provider recibe la entrada completa en `apply()`.

Las aplicaciones aprobadas se registran en `pos_payment_source_entries` para mostrarlas en operaciones y sesiones. Este registro es únicamente de auditoría: no genera recibos, no modifica caja y no sustituye la persistencia de dominio del provider.

Manual completo:

- [Implementación de Payment Sources](.agents/readme/Payment%20Source/README.md)
- [Flujo interno de Payment Sources](.agents/payment-sources.md)

Tras actualizar el plugin, ejecute su actualización habitual en FacturaScripts y recargue el POS.
