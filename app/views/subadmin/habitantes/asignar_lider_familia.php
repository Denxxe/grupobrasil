<?php
// app/views/subadmin/habitantes/asignar_lider_familia.php
// Variables: $habitante, $persona, $viviendas
?>
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4"><?= htmlspecialchars($page_title ?? 'Asignar Jefe de Familia') ?></h2>

    <div class="bg-white p-4 rounded shadow mb-4">
        <div><strong>Nombre:</strong> <?= htmlspecialchars($persona['nombres'] . ' ' . $persona['apellidos']) ?></div>
        <div><strong>Cédula:</strong> <?= htmlspecialchars($persona['cedula'] ?? '') ?></div>
        <div class="mt-2">
            <form method="POST" action="./index.php?route=subadmin/asignarLiderFamilia">
                <input type="hidden" name="id_habitante" value="<?= (int)$habitante['id_habitante'] ?>">

                <div class="form-group mb-3">
                    <label class="form-label">Seleccionar Vivienda (opcional)</label>
                    <?php if (!empty($viviendas)): ?>
                        <select name="id_vivienda" class="form-control">
                            <option value="">-- Ninguna --</option>
                            <?php foreach ($viviendas as $vv): ?>
                                <?php $label = htmlspecialchars(($vv['nombre_calle'] ?? $vv['nombre'] ?? '')) . ' - Nº ' . htmlspecialchars($vv['numero'] ?? ''); ?>
                                <option value="<?= (int)$vv['id_vivienda'] ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <div class="alert alert-warning">No hay viviendas disponibles en tus veredas asignadas.</div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary" type="submit">Asignar como Jefe de Familia</button>
                <a href="./index.php?route=subadmin/habitantes" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
