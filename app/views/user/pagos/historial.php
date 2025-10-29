<?php
// Vista: user/pagos/historial.php
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Mi Historial de Pagos</h1>

    <?php if (empty($pagos)): ?>
        <div class="mt-4 alert alert-info">No se encontraron pagos registrados.</div>
    <?php else: ?>
        <div class="table-responsive mt-4">
        <table class="table table-striped table-hover align-middle">
            <thead>
                <tr>
                    <th>Periodo</th>
                    <th>Monto</th>
                    <th>Fecha envío</th>
                    <th>Estado</th>
                    <th>Referencia</th>
                    <th>Evidencias</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pagos as $p): ?>
                    <tr>
                        <td><?=htmlspecialchars($p['nombre_periodo'] ?? '')?></td>
                        <td><?=htmlspecialchars($p['monto'] ?? '')?></td>
                        <td><?=htmlspecialchars($p['fecha_envio'] ?? '')?></td>
                        <td><span class="badge bg-secondary"><?=htmlspecialchars($p['estado_actual'] ?? $p['estado'] ?? '')?></span></td>
                        <td><?=htmlspecialchars($p['referencia_pago'] ?? $p['referencia'] ?? '')?></td>
                        <td>
                            <?php if (!empty($p['evidencias'])): ?>
                                <?php foreach ($p['evidencias'] as $e): ?>
                                    <button class="btn btn-sm btn-outline-primary me-1" type="button" onclick="previewEvidence('./<?=htmlspecialchars($e['ruta'])?>','<?=htmlspecialchars(basename($e['ruta']))?>')">Ver</button>
                                <?php endforeach; ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    <?php endif; ?>
</div>
<style>
    .evidence-link { margin-right: .5rem; display:inline-block }
</style>
