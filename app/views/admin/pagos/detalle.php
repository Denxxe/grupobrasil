<?php
// Variables esperadas: $periodo, $pagos
?>
<div class="container py-4">
    <h2 class="mb-3">Detalle del periodo: <?= htmlspecialchars($periodo['nombre'] ?? 'Periodo') ?></h2>
    <p><strong>Beneficio:</strong> <?= htmlspecialchars($periodo['nombre_beneficio'] ?? '—') ?></p>
    <p><strong>Fechas:</strong> <?= htmlspecialchars($periodo['fecha_inicio']) ?> — <?= htmlspecialchars($periodo['fecha_limite']) ?></p>

    <div class="mb-3 d-flex justify-content-between align-items-center">
        <div>
            <a href="./index.php?route=admin/pagos/periodos" class="btn btn-secondary">&larr; Volver</a>
            <a id="exportCsvBtn" href="./index.php?route=admin/pagos/export&id=<?= intval($periodo['id_periodo']) ?>" class="btn btn-success ms-2">Exportar CSV</a>
        </div>
        <form id="filtersForm" method="get" class="form-inline">
            <input type="hidden" name="route" value="admin/pagos/detalle">
            <input type="hidden" name="id" value="<?= intval($periodo['id_periodo']) ?>">
            <div class="input-group me-2">
                <select name="estado" class="form-select">
                    <option value="">Todos</option>
                    <option value="en_espera">En espera</option>
                    <option value="cancelado">Cancelado</option>
                    <option value="rechazado">Rechazado</option>
                </select>
            </div>
            <div class="input-group me-2">
                <input type="date" name="desde" class="form-control" placeholder="Desde">
            </div>
            <div class="input-group me-2">
                <input type="date" name="hasta" class="form-control" placeholder="Hasta">
            </div>
            <button type="submit" class="btn btn-primary me-2">Aplicar</button>
            <a href="?route=admin/pagos/detalle&id=<?= intval($periodo['id_periodo']) ?>" class="btn btn-outline-secondary">Limpiar</a>
        </form>
    </div>

    <?php if (!empty($pagination) && isset($pagination['totalPages'])): ?>
        <nav aria-label="Paginación" class="mt-3">
            <ul class="pagination">
                <?php $cur = max(1, intval($pagination['page'] ?? 1)); ?>
                <?php
                    $qsBase = 'route=admin/pagos/detalle&id=' . intval($periodo['id_periodo']);
                    if (!empty($_GET['estado'])) $qsBase .= '&estado=' . urlencode($_GET['estado']);
                    if (!empty($_GET['desde'])) $qsBase .= '&desde=' . urlencode($_GET['desde']);
                    if (!empty($_GET['hasta'])) $qsBase .= '&hasta=' . urlencode($_GET['hasta']);
                ?>
                <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $qsBase ?>&page=<?= max(1, $cur-1) ?>">Anterior</a>
                </li>
                    <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                    <li class="page-item <?= $p === $cur ? 'active' : '' ?>"><a class="page-link" href="?<?= $qsBase ?>&page=<?= $p ?>"><?= $p ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?= $cur >= $pagination['totalPages'] ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= $qsBase ?>&page=<?= min($pagination['totalPages'], $cur+1) ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID Pago</th>
                    <th>Jefe / Persona</th>
                    <th>Monto</th>
                    <th>Metodo</th>
                    <th>Referencia</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pagos)): ?>
                    <tr><td colspan="8">No hay pagos para este periodo.</td></tr>
                <?php else: ?>
                    <?php foreach ($pagos as $p): ?>
                        <tr>
                            <td><?= intval($p['id_pago']) ?></td>
                            <td><?= htmlspecialchars(trim(($p['nombres'] ?? '') . ' ' . ($p['apellidos'] ?? ''))) ?></td>
                            <td><?= htmlspecialchars($p['monto']) ?></td>
                            <td><?= htmlspecialchars($p['metodo_pago'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($p['referencia'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($p['estado_actual'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($p['created_at'] ?? $p['fecha_creacion'] ?? '—') ?></td>
                            <td>
                                <?php if (!empty($p['evidencias'])): ?>
                                    <?php foreach ($p['evidencias'] as $e): ?>
                                        <button class="btn btn-sm btn-outline-primary me-1" type="button" onclick="previewEvidence('./<?= htmlspecialchars($e['ruta']) ?>', '<?= htmlspecialchars(basename($e['ruta'])) ?>')">Ver</button>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin evidencias</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
    (function(){
        const form = document.getElementById('filtersForm');
        const exportBtn = document.getElementById('exportCsvBtn');
        function updateExportHref(){
            if(!form || !exportBtn) return;
            const params = new URLSearchParams(new FormData(form));
            const base = exportBtn.getAttribute('href').split('?')[0];
            exportBtn.setAttribute('href', base + '?' + params.toString() + '&id=<?= intval($periodo['id_periodo']) ?>');
        }
        if(form){
            // set initial values from GET
            <?php if (!empty($_GET['estado'])): ?> document.querySelector('select[name="estado"]').value = '<?= htmlspecialchars($_GET['estado']) ?>'; <?php endif; ?>
            <?php if (!empty($_GET['desde'])): ?> document.querySelector('input[name="desde"]').value = '<?= htmlspecialchars($_GET['desde']) ?>'; <?php endif; ?>
            <?php if (!empty($_GET['hasta'])): ?> document.querySelector('input[name="hasta"]').value = '<?= htmlspecialchars($_GET['hasta']) ?>'; <?php endif; ?>
            form.addEventListener('change', updateExportHref);
            updateExportHref();
        }
    })();
</script>
