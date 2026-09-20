CONTROL DE METRAJE DE LONA · VERSION SIMPLE

Objetivo: registrar solamente metros lineales impresos y descontarlos del rollo utilizado.

Instalar:
1) Ejecutar database/migrations/017_control_metraje_lona.sql en colibrip_abcsistema.
2) Reemplazar/agregar:
   admin/impresiones.php
   includes/impresiones.php
   assets/css/impresiones.css
3) No ejecutar migraciones anteriores de impresiones para este módulo.

Flujo:
- Crear rollo: nombre + metros iniciales.
- Registrar trabajo: fecha, rollo, nombre Printexp, largo del Job Size (mm) o metros lineales y resultado.
- El sistema descuenta SIEMPRE los metros consumidos del rollo, independientemente del resultado.
- Si el resultado no es "Impresión buena", esos metros se contabilizan como merma.
- Cuando el rollo llega a 0 m queda como "empty".

Ejemplo Printexp:
Job Size 1536.31 mm x 3810.00 mm -> largo 3810.00 mm -> 3.810 m.
