<?php
// Vista: Admin - Listado de todas las cargas familiares
// Variables esperadas: none (se cargan vía controller)
?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">Cargas Familiares - Comunidad</h3>
    </div>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID Carga</th>
                    <th>Jefe (Nombre)</th>
                    <th>Cédula</th>
                    <th>Vereda</th>
                    <th>Número Casa</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($familias) && is_array($familias)): ?>
                    <?php foreach ($familias as $f): ?>
                        <tr>
                            <td><?= htmlspecialchars($f['id_carga'] ?? '') ?></td>
                            <td><?= htmlspecialchars(($f['nombres'] ?? '') . ' ' . ($f['apellidos'] ?? '')) ?></td>
                            <td><?= htmlspecialchars($f['cedula'] ?? '') ?></td>
                            <td><?= htmlspecialchars($f['nombre_vereda'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($f['numero_casa'] ?? 'N/A') ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary btn-view-family" data-jefe="<?= htmlspecialchars($f['id_jefe'] ?? '') ?>">Ver Familia</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">No se encontraron familias registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.btn-view-family').forEach(b => b.addEventListener('click', async (e) => {
        const jefeId = e.currentTarget.dataset.jefe;
        try {
            const res = await fetch('./index.php?route=admin/viviendas&action=familiasPorViviendaByJefe&jefe=' + encodeURIComponent(jefeId), { credentials: 'same-origin' });
            const data = await res.json();
            // Mostrar en modal simple
            let html = '';
            if (!Array.isArray(data) || data.length === 0) html = '<p>No hay miembros.</p>'; else {
                html = '<ul>';
                data.forEach(m => { html += '<li>' + (m.nombres||'') + ' ' + (m.apellidos||'') + ' - ' + (m.parentesco||'') + '</li>'; });
                html += '</ul>';
            }
            alert('Miembros: \n' + (html.replace(/<[^>]+>/g, '\n')));
        } catch (err) { console.error(err); alert('Error al cargar familia'); }
    }));
});
</script>
