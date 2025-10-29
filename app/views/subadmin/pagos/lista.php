<?php
// Vista: subadmin/pagos/lista.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Pagos en veredas asignadas</h1>
    <form method="get" class="row g-2 align-items-center mt-3 mb-3">
        <input type="hidden" name="route" value="subadmin/pagos/lista">
        <div class="col-auto">
            <select name="estado" class="form-select">
                <option value="">Todos</option>
                <option value="en_espera" <?= (isset($_GET['estado']) && $_GET['estado']=='en_espera')? 'selected':'' ?>>En espera</option>
                <option value="cancelado" <?= (isset($_GET['estado']) && $_GET['estado']=='cancelado')? 'selected':'' ?>>Cancelado</option>
                <option value="rechazado" <?= (isset($_GET['estado']) && $_GET['estado']=='rechazado')? 'selected':'' ?>>Rechazado</option>
            </select>
        </div>
        <div class="col-auto">
            <input type="date" name="desde" class="form-control" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
        </div>
        <div class="col-auto">
            <input type="date" name="hasta" class="form-control" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
        </div>
        <div class="col-auto">
            <button class="btn btn-primary">Filtrar</button>
            <a class="btn btn-outline-secondary" href="?route=subadmin/pagos/lista">Limpiar</a>
        </div>
    </form>

    <div class="table-responsive mt-3">
    <table class="table table-striped table-hover align-middle">
        <thead>
            <tr>
                <th>Jefe</th>
                <th>Vereda</th>
                <th>Referencia</th>
            <select name="estado" class="form-select bg-white text-dark" aria-label="Filtro Estado">
                <th>Evidencias</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        // Vista: subadmin/pagos/lista.php
        ?>
        <div class="p-4">
            <h1 class="text-2xl font-bold">Pagos en veredas asignadas</h1>
            <form method="get" class="row g-2 align-items-center mt-3 mb-3">
                <input type="hidden" name="route" value="subadmin/pagos/lista">
                <div class="col-auto">
                    <select name="estado" class="form-select bg-white text-dark" aria-label="Filtro Estado">
                        <option value="">Todos</option>
                        <option value="en_espera" <?= (isset($_GET['estado']) && $_GET['estado']=='en_espera')? 'selected':'' ?>>En espera</option>
                        <option value="cancelado" <?= (isset($_GET['estado']) && $_GET['estado']=='cancelado')? 'selected':'' ?>>Cancelado</option>
                        <option value="rechazado" <?= (isset($_GET['estado']) && $_GET['estado']=='rechazado')? 'selected':'' ?>>Rechazado</option>
                    </select>
                </div>
                <div class="col-auto">
                    <input type="date" name="desde" class="form-control bg-white text-dark" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <input type="date" name="hasta" class="form-control bg-white text-dark" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Filtrar</button>
                    <a class="btn btn-outline-secondary" href="?route=subadmin/pagos/lista">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive mt-3">
            <table class="table table-striped table-hover align-middle">
                <thead>
                    <tr>
                        <th>Jefe</th>
                        <th>Vereda</th>
                        <th>Referencia</th>
                        <th>Estado</th>
                        <th>Evidencias</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach (($pagos ?? []) as $p): ?>
                    <tr>
                        <td><?=htmlspecialchars($p['nombres'] ?? ($p['nombre_completo'] ?? ''))?></td>
                        <td><?=htmlspecialchars($p['vereda'] ?? '')?></td>
                        <td><?=htmlspecialchars($p['referencia_pago'] ?? $p['referencia'] ?? '')?></td>
                        <td><span class="badge bg-secondary"><?=htmlspecialchars($p['estado_actual'] ?? $p['estado'] ?? '')?></span></td>
                        <td>
                            <?php if (!empty($p['evidencias'])): ?>
                                <?php foreach ($p['evidencias'] as $e): ?>
                                    <button class="btn btn-sm btn-outline-primary me-1" type="button" onclick="previewEvidence('./<?=htmlspecialchars($e['ruta'])?>','<?=htmlspecialchars(basename($e['ruta']))?>')">Ver</button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="verifyPago(<?=intval($p['id_pago'])?>,'aprobar', this)">Aprobar</button>
                            <button class="btn btn-sm btn-danger" onclick="verifyPago(<?=intval($p['id_pago'])?>,'rechazar', this)">Rechazar</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if (!empty($pagination) && isset($pagination['totalPages'])): ?>
                <nav aria-label="Paginación" class="mt-3">
                    <ul class="pagination">
                        <?php $cur = max(1, intval($pagination['page'] ?? 1));
                              $qs = 'route=subadmin/pagos/lista';
                              if (!empty($_GET['estado'])) $qs .= '&estado=' . urlencode($_GET['estado']);
                              if (!empty($_GET['desde'])) $qs .= '&desde=' . urlencode($_GET['desde']);
                              if (!empty($_GET['hasta'])) $qs .= '&hasta=' . urlencode($_GET['hasta']);
                        ?>
                        <li class="page-item <?= $cur <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= $qs ?>&page=<?= max(1, $cur-1) ?>">Anterior</a>
                        </li>
                            <?php for ($p = 1; $p <= $pagination['totalPages']; $p++): ?>
                            <li class="page-item <?= $p === $cur ? 'active' : '' ?>"><a class="page-link" href="?<?= $qs ?>&page=<?= $p ?>"><?= $p ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $cur >= $pagination['totalPages'] ? 'disabled' : '' ?>">
                            <a class="page-link" href="?<?= $qs ?>&page=<?= min($pagination['totalPages'], $cur+1) ?>">Siguiente</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>

            <script src="./js/pagos.js"></script>
            <script>
            async function verifyPago(id, accion, btn){
                try{
                    if(!confirm('Confirmar acción: '+accion)) return;
                    // Deshabilitar botones del row
                    if(btn) btn.disabled = true;
                    const payload = new FormData();
                    payload.append('id_pago', id);
                    payload.append('accion', accion);
                    const res = await fetch('./index.php?route=subadmin/pagos/verify', { method: 'POST', body: payload, credentials: 'same-origin' });
                    const j = await res.json();
                    if (window.showToast) window.showToast(j.message || (j.ok? 'Operación exitosa' : 'Error'), j.ok? 'success':'error');
                    else alert(j.message);
                    if (j.ok) setTimeout(()=> location.reload(), 800);
                }catch(e){
                    if(window.showToast) window.showToast('Error de red: '+e.message, 'error'); else alert('Error: '+e.message);
                    if(btn) btn.disabled = false;
                }
            }
            </script>
        </div>
