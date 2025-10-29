<?php
// Vista: app/views/events/index.php
// Muestra calendario con eventos y botón para crear (roles 1 y 2)
?>
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($page_title) ?></h1>

  <?php if (isset($success_message) && $success_message): ?>
    <div class="alert alert-success mb-2"><?= htmlspecialchars($success_message) ?></div>
  <?php endif; ?>
  <?php if (isset($error_message) && $error_message): ?>
    <div class="alert alert-danger mb-2"><?= htmlspecialchars($error_message) ?></div>
  <?php endif; ?>

  <div class="mb-4">
    <?php if (
      (isset($_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === 1) ||
      (isset($_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === 2)
    ): ?>
      <a href="./index.php?route=eventos/create" class="btn btn-primary">Crear Evento</a>
    <?php endif; ?>
  </div>

  <div id='calendar'></div>
</div>

<!-- FullCalendar: preferir local (public/vendor/fullcalendar/) si está disponible, si no usar CDN v5.11.3 -->
<?php
$localCss = __DIR__ . '/../../../../public/vendor/fullcalendar/main.min.css';
$localJs = __DIR__ . '/../../../../public/vendor/fullcalendar/main.min.js';
if (file_exists($localCss) && file_exists($localJs)) {
  // Rutas relativas para el navegador
  echo "<link href='./vendor/fullcalendar/main.min.css' rel='stylesheet' />\n";
  echo "<script src='./vendor/fullcalendar/main.min.js'></script>\n";
} else {
  echo "<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />\n";
  echo "<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>\n";
}
?>
<?php require_once __DIR__ . '/../../helpers/CsrfHelper.php'; ?>
<script>
  // Exponer rol de usuario al frontend para controles básicos de UI
  window.USER_ROLE = <?= isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 'null' ?>;
  // Exponer token CSRF para uso en el modal AJAX
  window.CSRF_TOKEN = '<?= htmlspecialchars(\CsrfHelper::getToken(), ENT_QUOTES, 'UTF-8') ?>';
</script>

<!-- Modal para crear/editar evento desde el calendario -->
<div class="modal fade" id="eventModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventModalLabel">Crear Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <form id="eventModalForm">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\CsrfHelper::getToken(), ENT_QUOTES, 'UTF-8') ?>">
          <div class="mb-2">
            <label class="form-label">Título</label>
            <input name="titulo" class="form-control" required maxlength="255" />
          </div>
          <div class="mb-2">
            <label class="form-label">Fecha</label>
            <input name="fecha" type="date" class="form-control" required />
          </div>
          <div class="mb-2">
            <label class="form-label">Hora inicio</label>
            <input name="hora_inicio" type="time" class="form-control" />
          </div>
          <div class="mb-2">
            <label class="form-label">Hora fin</label>
            <input name="hora_fin" type="time" class="form-control" />
          </div>
          <div class="mb-2">
            <label class="form-label">Ubicación</label>
            <input name="ubicacion" class="form-control" maxlength="255" />
          </div>
          <div class="mb-2">
            <label class="form-label">Alcance</label>
            <select name="alcance" class="form-control"><option value="comunidad">Comunidad</option><option value="vereda">Vereda</option></select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" id="eventModalSubmit" class="btn btn-primary">Guardar</button>
      </div>
    </div>
  </div>
</div>

<script src="./js/events.js?v=2"></script>
