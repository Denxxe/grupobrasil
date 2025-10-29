IMPLEMENTACION - Pagos / Beneficios

Resumen rápido

Este documento describe cómo probar manualmente y usar la nueva sección de Pagos/Beneficios implementada en la aplicación.

Rutas principales

- Admin (Jefe del Consejo):
  - Listar periodos: /public/index.php?route=admin/pagos/periodos
  - Crear periodo: /public/index.php?route=admin/pagos/crear
  - Editar periodo: /public/index.php?route=admin/pagos/editar&id={id_periodo}
  - Cerrar periodo (POST): /public/index.php?route=admin/pagos/close (formulario desde la UI)
  - Exportar pagos por periodo: /public/index.php?route=admin/pagos/export&id={id_periodo}

- Usuario (Jefe de Familia):
  - Listar periodos: /public/index.php?route=user/pagos
  - Ver detalle y enviar pago: /public/index.php?route=user/pagos/detalle&id={id_periodo}

- Lider (Subadmin):
  - Lista de pagos por vereda: /public/index.php?route=subadmin/pagos/lista

Validaciones y reglas

- Solo usuarios con rol 1 pueden crear/editar/cerrar periodos.
- Al crear/editar un periodo se debe seleccionar un `Tipo de beneficio` existente (tabla `tipos_beneficio`). Si no existe, el formulario será rechazado.
- Envío de pagos: solo Jefes de Familia (habitante con `es_jefe_familia`) pueden enviar pagos.
- Archivos aceptados: image/jpeg, image/png, application/pdf. Límite por archivo por defecto: 5 MB.

Evidencias

Las evidencias se guardan en:
public/uploads/pagos/{id_periodo}/{id_pago}/

Nombres y referencia se guardan en la tabla `pagos_evidencias`.

Notificaciones

- Al crear un periodo, se notifica a todos los usuarios con rol 3 (Jefes de Familia).
- Al enviar un pago, se notifica a los líderes asignados a la vereda; si no hay líderes, se notifica a administradores.
- Al verificar (aprobar/rechazar) un pago, se notifica al usuario que registró el pago.

Export CSV

- Desde la vista de `admin/pagos/periodos` puede exportar los pagos asociados a un periodo (botón Exportar pagos). Genera CSV descargable.

Pruebas manuales recomendadas

1. Crear un periodo (como admin): completar nombre, tipo de beneficio, monto, fechas e instrucciones. Ver que aparece en la lista de activos.
2. Como Jefe de Familia enviar un pago para el periodo: subir captura (jpg/png/pdf), indicar referencia. Ver en BD `pagos` y `pagos_evidencias`.
3. Como Líder verificar: aprobar/rechazar. Ver que el estado cambia y que se crea registro en `pagos_estado_log`.
4. Exportar pagos: desde admin, usar Exportar pagos en un periodo con registros; abrir CSV.

Notas para desarrolladores

- Los modelos relevantes: `PagosPeriodos`, `Pago`, `Notificacion`, `TipoBeneficio`.
- Vistas: `app/views/admin/pagos/*`, `app/views/user/pagos/*`, `app/views/subadmin/pagos/*`.
- Controladores: `app/controllers/PagoController.php`, `app/controllers/AdminController.php` (acciones admin ahora implementadas internamente para evitar includes dinámicos).

Problemas conocidos

- Aún falta paginación y filtrado avanzado en las listas (pendiente).
- Tests automatizados no incluidos; se recomienda una ronda de QA manual.

Si encuentras errores en la ejecución local, pega aquí el contenido del log de Apache/PHP (error.log / php_error_log) y lo depuro.
