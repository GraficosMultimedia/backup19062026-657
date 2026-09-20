# Colibrí Compras desde cero

## Importante
Este paquete **no incluye `app/config/config.php`** porque ese archivo contiene las credenciales de MySQL del servidor.

La base de datos objetivo es `colibrip_compras` y las tablas respetan la estructura entregada para el proyecto: `usuarios`, `proveedores`, `productos`, `compras`, `conceptos`, `pagos`, `precios_web`, `cotizaciones_proveedor`, `evaluaciones_calidad`, `trabajos`, `trabajo_materiales`, `alertas` y `logs`.

## Instalación
1. Suba el contenido de `colibri_compras` a `/home2/colibrip/public_html/compras/`.
2. Copie `app/config/config.example.php` a `app/config/config.php`.
3. Edite **solo** `app/config/config.php` con usuario y contraseña de MySQL.
4. Si las tablas todavía no existen, ejecute `database/schema.sql` en la base `colibrip_compras`.
5. Abra `/compras/install.php` para crear el primer administrador.
6. Elimine `install.php` después de crear el administrador.
7. Entre en `/compras/public/login.php`.

## Módulos
- Dashboard con compras, pagado y saldo pendiente.
- Compras con búsqueda, filtros y detalle.
- Pagos con registro de pagos parciales o totales.
- Estado automático: pendiente, parcial o pagada según pagos reales.
- Importación de XML CFDI y conceptos.
- Productos.
- Proveedores.
- Trabajos / costos.
- Calidad.
- Precios web.

## Regla de pagos
El estado de una compra **no se determina por `MetodoPago=PUE`**. Se calcula con los registros reales de `pagos`:
- 0 pagado => pendiente
- pago menor al total => parcial
- pago igual o mayor al total => pagada

Esto evita que una compra aparezca como pagada cuando todavía no existe un pago registrado.

## Importación de clientes desde Akaunting

Se incluye `public/clientes.php` y `public/importar_clientes.php`. La base origen por defecto es `colibrip_akau488` y se busca la tabla `contacts`. Para conectar, configure el bloque `akaunting` de `app/config/config.php`. Si el mismo usuario MySQL tiene acceso a ambas bases, puede reutilizar las mismas credenciales.

La importación es de solo lectura sobre Akaunting y guarda una copia de los campos disponibles en `clientes.datos_extra` para conservar información adicional.
