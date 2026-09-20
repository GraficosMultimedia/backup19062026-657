# Colibrí Print · Comprobantes de pago V1

## Flujo
Cliente:
`seguimiento.php?t=TOKEN` → sube comprobante → `cp_payment_receipts` en estado `pending`.

Administración:
`admin/orden.php?id=...` → revisa comprobante → `pending/reviewing/confirmed/rejected` → opcionalmente lo vincula a un pago existente en `cp_payments`.

Cliente:
`Comprobante de pago ↗` → abre el comprobante mediante `comprobante_pago.php` usando el token del seguimiento.

## No confirma dinero automáticamente
Subir un comprobante no crea ni confirma un pago. Solo registra evidencia documental. La confirmación continúa en Administración.

## Seguridad
- Requiere token de seguimiento activo.
- Archivo almacenado con nombre aleatorio basado en token criptográfico.
- Validación MIME real.
- Máximo 10 MB.
- JPG, PNG, WEBP o PDF.
- Enlaces públicos con `noindex,nofollow`.

## Instalación
1. Ejecutar `database/sql/020_payment_receipts.sql`.
2. Subir `includes/payment_receipts.php`.
3. Subir `api/comprobante_pago_cliente.php`.
4. Subir `comprobante_pago.php`.
5. Reemplazar `seguimiento.php`.
6. Reemplazar `includes/seguimiento.php`.
7. Reemplazar `admin/orden.php`.
8. Subir `assets/css/payment-receipts.css`.

No requiere otra dependencia.
