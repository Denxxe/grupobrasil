<?php 
// app/views/admin/users/personas.php 
// Variables disponibles: $page_title, $personas, $success_message, $error_message
?>

<div class="container-fluid">

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($error_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Personas Registradas</h6>
            <a href="./index.php?route=admin/users/create" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Añadir Nuevo Habitante
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombres y Apellidos</th>
                            <th>Ubicación</th>
                            <th>Teléfono</th>
                            <th>Cuenta de Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (isset($personas) && is_array($personas)):
                            foreach ($personas as $persona): 
                                $id = htmlspecialchars($persona['id_persona']); 
                                $cedula = htmlspecialchars($persona['cedula'] ?? 'N/A'); 
                                $nombres = htmlspecialchars($persona['nombres'] ?? '');
                                $apellidos = htmlspecialchars($persona['apellidos'] ?? '');
                                $casa = htmlspecialchars($persona['numero_casa'] ?? 'S/N');
                                $calle_nombre = htmlspecialchars($persona['calle_nombre'] ?? 'Sin Calle'); 
                                $telefono = htmlspecialchars($persona['telefono'] ?? 'N/A');
                                $has_user = $persona['tiene_usuario'] ?? false; 
                        ?>
                        <tr>
                            <td><?= $cedula ?></td>
                            <td><?= "$nombres $apellidos" ?></td>
                            <td><?= "Casa $casa, $calle_nombre" ?></td>
                            <td><?= $telefono ?></td>
                            <td>
                                <?php if ($has_user): ?>
                                    <span class="badge badge-success">Sí (Activo)</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- Added edit and delete buttons -->
                                <div class="btn-group" role="group">
                                    <!-- Gestionar Roles -->
                                    <a href="./index.php?route=admin/users/create-user-role&person_id=<?= $id ?>" 
                                       class="btn btn-info btn-sm" title="<?= $has_user ? 'Gestionar Roles' : 'Crear Usuario/Líder' ?>">
                                        <i class="fas fa-user-shield"></i>
                                    </a>
                                    
                                    <!-- Editar Habitante -->
                                    <a href="./index.php?route=admin/users/edit-habitante&person_id=<?= $id ?>" 
                                       class="btn btn-primary btn-sm" title="Editar Habitante">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Eliminar Habitante -->
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="confirmarEliminacion(<?= $id ?>, '<?= addslashes($nombres . ' ' . $apellidos) ?>')"
                                            title="Eliminar Habitante">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Added confirmation modal for deletion -->
<script>
function confirmarEliminacion(personId, nombreCompleto) {
    if (confirm('¿Está seguro de que desea eliminar al habitante "' + nombreCompleto + '"?\n\n' +
                'ADVERTENCIA: Esta acción eliminará:\n' +
                '- El registro del habitante\n' +
                '- Su cuenta de usuario (si tiene)\n' +
                '- Sus asignaciones de liderazgo\n' +
                '- Sus registros de familia (la familia se mantendrá)\n\n' +
                'Esta acción NO se puede deshacer.')) {
        window.location.href = './index.php?route=admin/users/delete-habitante&person_id=' + personId;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
        $('#dataTable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
            },
            "order": [[1, "asc"]]
        });
    }
});
</script>
