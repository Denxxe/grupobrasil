<?php
// Vista administrador para Eventos: reusa la vista pública de events/index.php
$page_title = $page_title ?? 'Eventos (Admin)';
// Incluimos la vista común de events para evitar duplicar lógica
// Ruta relativa desde app/views/admin/events -> ../../events/index.php
require_once __DIR__ . '/../../events/index.php';

?>
