<?php
// Vista: subadmin/pagos/lista.php - versión mejorada visualmente
?>
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Pagos — Veredas asignadas</h1>
        <div>
            <a href="./index.php?route=subadmin/dashboard" class="btn btn-light me-2">Dashboard</a>
            <a href="?route=subadmin/pagos/lista" class="btn btn-outline-secondary">Limpiar filtros</a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <form method="get" class="row g-2 align-items-center">
                <input type="hidden" name="route" value="subadmin/pagos/lista">
                <div class="col-auto">
                    <label class="visually-hidden">Estado</label>
                    <select name="estado" class="form-select bg-white text-dark">
                        <option value="">Todos</option>
                        <option value="en_espera" <?= (isset($_GET['estado']) && $_GET['estado']=='en_espera')? 'selected':'' ?>>En espera</option>
                        <option value="cancelado" <?= (isset($_GET['estado']) && $_GET['estado']=='cancelado')? 'selected':'' ?>>Cancelado</option>
                        <option value="rechazado" <?= (isset($_GET['estado']) && $_GET['estado']=='rechazado')? 'selected':'' ?>>Rechazado</option>
                    </select>
                </div>
                <div class="col-auto">
                    <label class="visually-hidden">Desde</label>
                    <input type="date" name="desde" class="form-control bg-white text-dark" value="<?= htmlspecialchars($_GET['desde'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <label class="visually-hidden">Hasta</label>
                    <input type="date" name="hasta" class="form-control bg-white text-dark" value="<?= htmlspecialchars($_GET['hasta'] ?? '') ?>">
                </div>
                <div class="col-auto">
                    <button class="btn btn-primary">Aplicar</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Jefe</th>
                            <th>Vereda</th>
                            <th>Referencia</th>
                            <th>Estado</th>
                            <th>Evidencias</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pagos ?? [])): ?>
                            <tr><td colspan="6" class="text-center py-4">No hay pagos para mostrar.</td></tr>
                        <?php else: ?>
                            <?php foreach (($pagos ?? []) as $p): ?>
                                <tr>
                                    <td><?=htmlspecialchars($p['nombres'] ?? ($p['nombre_completo'] ?? ''))?></td>
                                    <td><?=htmlspecialchars($p['vereda'] ?? '')?></td>
                                    <td><?=htmlspecialchars($p['referencia_pago'] ?? $p['referencia'] ?? '')?></td>
                                    <td>
                                        <?php $estado = $p['estado_actual'] ?? $p['estado'] ?? '';
                                            $badge = 'bg-secondary';
                                            if ($estado === 'en_espera') $badge = 'bg-warning text-dark';
                                            if ($estado === 'procesado' || $estado === 'aprobado') $badge = 'bg-success';
                                            if ($estado === 'rechazado' || $estado === 'cancelado') $badge = 'bg-danger';
                                        ?>
                                        <span class="badge <?= $badge ?>"><?=htmlspecialchars($estado)?></span>
                                    </td>
                                    <td>
                                        <?php if (!empty($p['evidencias'])): ?>
                                            <?php foreach ($p['evidencias'] as $e): ?>
                                                <button class="btn btn-sm btn-outline-primary me-1" type="button" onclick="previewEvidence('./<?=htmlspecialchars($e['ruta'])?>','<?=htmlspecialchars(basename($e['ruta']))?>')">Ver</button>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-success" onclick="verifyPago(<?=intval($p['id_pago'])?>,'aprobar', this)">Aprobar</button>
                                            <button class="btn btn-sm btn-danger" onclick="verifyPago(<?=intval($p['id_pago'])?>,'rechazar', this)">Rechazar</button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($pagination) && isset($pagination['totalPages'])): ?>
        <nav aria-label="Paginación" class="mt-3">
            <ul class="pagination justify-content-center">
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
            if(btn) btn.disabled = true;
            const payload = new FormData();
            payload.append('id_pago', id);
            payload.append('accion', accion);
            const res = await fetch('./index.php?route=subadmin/pagos/verify', { method: 'POST', body: payload, credentials: 'same-origin' });
            const j = await res.json();
            if (window.showToast) window.showToast(j.message || (j.ok? 'Operación exitosa' : 'Error'), j.ok? 'success':'error');
            else alert(j.message || (j.ok? 'Operación exitosa' : 'Error'));
            if (j.ok) setTimeout(()=> location.reload(), 800);
        }catch(e){
            if(window.showToast) window.showToast('Error de red: '+e.message, 'error'); else alert('Error: '+e.message);
            if(btn) btn.disabled = false;
        }
    }
    </script>
</div>
