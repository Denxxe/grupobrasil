<?php
// Vista: admin/pagos/periodos.php
?>
<div class="p-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Periodos de Pago</h1>
        <div>
            <a href="./index.php?route=admin/pagos/crear" class="btn btn-primary">Crear periodo</a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <h5 class="mb-2">Activos</h5>
            <?php if (empty($activos)): ?>
                <div class="alert alert-info">No hay periodos activos.</div>
            <?php else: ?>
                <?php foreach ($activos as $p): ?>
                    <div class="card mb-2">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1"><?=htmlspecialchars($p['nombre_periodo'])?></h6>
                                <p class="mb-0"><small class="text-muted"><?=htmlspecialchars($p['nombre_beneficio'] ?? '—')?> · Monto: <?=htmlspecialchars($p['monto'])?> · <?=htmlspecialchars($p['fecha_inicio'])?> → <?=htmlspecialchars($p['fecha_limite'])?></small></p>
                            </div>
                            <div>
                                <a class="btn btn-sm btn-outline-primary me-1" href="./index.php?route=admin/pagos/editar&id=<?=intval($p['id_periodo'])?>">Editar</a>
                                <a class="btn btn-sm btn-secondary me-1" href="./index.php?route=admin/pagos/periodos&view=detalle&id=<?=intval($p['id_periodo'])?>">Ver</a>
                                <a class="btn btn-sm btn-info me-1" href="./index.php?route=admin/pagos/export&id=<?=intval($p['id_periodo'])?>">Exportar</a>
                                <form method="post" action="./index.php?route=admin/pagos/close" style="display:inline">
                                    <input type="hidden" name="id_periodo" value="<?=intval($p['id_periodo'])?>">
                                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Cerrar periodo? Esta acción no se puede deshacer.')">Cerrar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-md-6">
            <h5 class="mb-2">Historial</h5>
            <?php if (empty($historial)): ?>
                <div class="alert alert-info">No hay periodos en historial.</div>
            <?php else: ?>
                <?php foreach ($historial as $p): ?>
                    <div class="card mb-2">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-1"><?=htmlspecialchars($p['nombre_periodo'])?></h6>
                                <p class="mb-0"><small class="text-muted"><?=htmlspecialchars($p['nombre_beneficio'] ?? '—')?> · <?=htmlspecialchars($p['fecha_inicio'])?> → <?=htmlspecialchars($p['fecha_limite'])?></small></p>
                            </div>
                            <div>
                                <a class="btn btn-sm btn-info" href="./index.php?route=admin/pagos/export&id=<?=intval($p['id_periodo'])?>">Exportar</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
