<?php
// Vista: admin/pagos/editar.php
$periodo = $periodo ?? null;
$tipos = $tipos_beneficio ?? [];
?>
<div class="p-4">
    <h1 class="text-2xl font-bold">Editar Periodo de Pago</h1>

    <?php if (!$periodo): ?>
        <div class="alert alert-danger mt-4">Periodo no encontrado.</div>
    <?php else: ?>
    <div class="mt-4 card shadow-sm">
        <div class="card-body">
            <form method="post" action="./index.php?route=admin/pagos/update" class="row g-3">
                <input type="hidden" name="id_periodo" value="<?=intval($periodo['id_periodo'])?>">

                <div class="col-12">
                    <label class="form-label">Nombre del periodo</label>
                    <input name="nombre_periodo" class="form-control" value="<?=htmlspecialchars($periodo['nombre_periodo'])?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Monto</label>
                    <input name="monto" class="form-control" type="number" step="0.01" value="<?=htmlspecialchars($periodo['monto'])?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha inicio</label>
                    <input name="fecha_inicio" class="form-control" type="date" value="<?=htmlspecialchars($periodo['fecha_inicio'])?>" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fecha límite</label>
                    <input name="fecha_limite" class="form-control" type="date" value="<?=htmlspecialchars($periodo['fecha_limite'])?>" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label">Tipo de beneficio</label>
                    <select name="id_tipo_beneficio" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($tipos as $t): ?>
                            <option value="<?=intval($t['id_tipo_beneficio'])?>" <?= (intval($t['id_tipo_beneficio']) === intval($periodo['id_tipo_beneficio'] ?? 0)) ? 'selected' : '' ?>><?=htmlspecialchars($t['nombre'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">Instrucciones / datos de pago</label>
                    <textarea name="instrucciones_pago" class="form-control" rows="4"><?=htmlspecialchars($periodo['instrucciones_pago'] ?? '')?></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <a href="./index.php?route=admin/pagos/periodos" class="btn btn-secondary">&larr; Volver</a>
                    <button class="btn btn-primary" type="submit">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>
