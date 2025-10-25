<!-- grupobrasil/app/views/subadmin/familias/index.php -->

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Familias de Mi Vereda</h1>
    </div>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['flash_success']); 
                unset($_SESSION['flash_success']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php 
                echo htmlspecialchars($_SESSION['flash_error']); 
                unset($_SESSION['flash_error']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Familias</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Jefe de Familia</th>
                            <th>Cédula</th>
                            <th>Vereda</th>
                            <th>Casa</th>
                            <th>Teléfono</th>
                            <th>Total Miembros</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($familias)): ?>
                            <tr>
                                <td colspan="7" class="text-center">No hay familias registradas en tus veredas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($familias as $familia): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($familia['nombres'] . ' ' . $familia['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($familia['cedula'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($familia['nombre_vereda'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($familia['numero_casa'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($familia['telefono'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-primary">
                                            <?php echo $familia['total_miembros']; ?> miembro(s)
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-info btn-view-family" data-jefe="<?= htmlspecialchars($familia['id_jefe']) ?>">
                                            <i class="fas fa-eye"></i> Ver Familia
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    document.querySelectorAll('.btn-view-family').forEach(b => b.addEventListener('click', async (e) => {
        const jefeId = e.currentTarget.dataset.jefe;
        try {
            const res = await fetch('./index.php?route=subadmin/familias&action=miembros&jefe=' + encodeURIComponent(jefeId), { credentials: 'same-origin' });
            if (!res.ok) { alert('Error al cargar la familia'); return; }
            const data = await res.json();
            let html = '';
            if (!Array.isArray(data) || data.length === 0) html = '<p>No hay miembros.</p>'; else {
                html = '<h5>Miembros</h5><ul>';
                data.forEach(m => { html += '<li>' + (m.nombres||'') + ' ' + (m.apellidos||'') + ' - ' + (m.parentesco||'') + '</li>'; });
                html += '</ul>';
            }
            // mostrar en modal simple
            let modal = document.getElementById('subadminFamilyModal');
            if (!modal) {
                modal = document.createElement('div'); modal.id = 'subadminFamilyModal'; modal.className = 'modal fade'; modal.tabIndex = -1;
                modal.innerHTML = `<div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Detalles de Familia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body" id="subadminFamilyContent"></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div></div></div>`;
                document.body.appendChild(modal);
            }
            document.getElementById('subadminFamilyContent').innerHTML = html;
            const bsModal = new bootstrap.Modal(document.getElementById('subadminFamilyModal'));
            bsModal.show();
        } catch (err) { console.error(err); alert('Error al cargar familia'); }
    }));
});
</script>
