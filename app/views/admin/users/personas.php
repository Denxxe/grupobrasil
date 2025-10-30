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

    <?php
    // Preparar separación de admins y el resto (para mostrar admins en un bloque separado)
    $admins = [];
    $others = [];
    if (isset($personas) && is_array($personas)) {
        foreach ($personas as $persona) {
            if (isset($persona['id_rol']) && (int)$persona['id_rol'] === 1) {
                $admins[] = $persona;
            } else {
                $others[] = $persona;
            }
        }
    }
    ?>

    <?php if (!empty($admins)): // Mostrar admins en un bloque separado encima de la tabla ?>
        <div class="mb-3">
            <h5>Administrador(es) Principal(es)</h5>
            <div class="row">
                <?php foreach ($admins as $persona): 
                    $aid = htmlspecialchars($persona['id_persona']);
                    $aced = htmlspecialchars($persona['cedula'] ?? 'N/A');
                    $anombres = htmlspecialchars($persona['nombres'] ?? '');
                    $aapellidos = htmlspecialchars($persona['apellidos'] ?? '');
                    $atelefono = htmlspecialchars($persona['telefono'] ?? 'N/A');
                    $acasa = htmlspecialchars($persona['numero_casa'] ?? 'S/N');
                    $acalle = htmlspecialchars($persona['calle_nombre'] ?? 'Sin Calle');
                    $ahas_user = $persona['tiene_usuario'] ?? false;
                ?>
                <div class="col-md-6">
                    <div class="card border-primary mb-2">
                        <div class="card-body p-2 d-flex justify-content-between align-items-center">
                            <div>
                                <div class="font-weight-bold"><?= "$anombres $aapellidos" ?></div>
                                <div class="small text-muted">Cédula: <?= $aced ?> &middot; <?= "Casa $acasa, $acalle" ?></div>
                                <div class="small">Tel: <?= $atelefono ?></div>
                                <div class="small">Cuenta de Usuario: <?= $ahas_user ? '<span class="text-dark font-weight-bold">Sí (Activo)</span>' : '<span class="text-dark font-weight-bold">No</span>' ?></div>
                            </div>
                            <div class="text-right">
                                <a href="./index.php?route=admin/users/create-user-role&person_id=<?= $aid ?>" class="btn btn-info btn-sm mb-1" title="Gestionar Roles"><i class="fas fa-user-shield"></i></a>
                                <a href="./index.php?route=admin/users/edit-habitante&person_id=<?= $aid ?>" class="btn btn-primary btn-sm mb-1" title="Editar Habitante"><i class="fas fa-edit"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Listado de Personas Registradas</h6>
                <a href="./index.php?route=admin/users/create" class="btn btn-success btn-sm">
                    <i class="fas fa-plus"></i> Añadir Nuevo Habitante
                </a>
            </div>

            <div class="mt-3">
                <form method="GET" action="./index.php" class="row g-2 align-items-end">
                    <input type="hidden" name="route" value="admin/users/personas">
                    <div class="col-md-5">
                        <label class="form-label small">Buscar (Cédula, Nombre)</label>
                        <input type="search" name="search" class="form-control" placeholder="Ej: V-12345678 o Juan" value="<?= htmlspecialchars($current_search ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Estado</label>
                        <select name="activo" class="form-select">
                            <option value="all" <?= (isset($current_activo) && $current_activo === 'all') ? 'selected' : '' ?>>Todos</option>
                            <option value="1" <?= (isset($current_activo) && $current_activo === '1') ? 'selected' : '' ?>>Activos</option>
                            <option value="0" <?= (isset($current_activo) && $current_activo === '0') ? 'selected' : '' ?>>Inactivos</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Cuenta de Usuario</label>
                        <select name="es_usuario" class="form-select">
                            <option value="all" <?= (isset($current_es_usuario) && $current_es_usuario === 'all') ? 'selected' : '' ?>>Todos</option>
                            <option value="1" <?= (isset($current_es_usuario) && $current_es_usuario === '1') ? 'selected' : '' ?>>Con cuenta</option>
                            <option value="0" <?= (isset($current_es_usuario) && $current_es_usuario === '0') ? 'selected' : '' ?>>Sin cuenta</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button class="btn btn-primary w-100" type="submit"><i class="fas fa-search"></i> Filtrar</button>
                    </div>
                </form>
            </div>
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
                        // Mostrar solo el resto de habitantes en la tabla (admins se muestran arriba)
                        if (!empty($others)) {
                            foreach ($others as $persona) {
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
                                    <span class="text-dark font-weight-bold">Sí (Activo)</span>
                                <?php else: ?>
                                    <span class="text-dark font-weight-bold">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="./index.php?route=admin/users/create-user-role&person_id=<?= $id ?>" 
                                       class="btn btn-info btn-sm" title="<?= $has_user ? 'Gestionar Roles' : 'Crear Usuario/Líder' ?>">
                                        <i class="fas fa-user-shield"></i>
                                    </a>
                                    <a href="./index.php?route=admin/users/edit-habitante&person_id=<?= $id ?>" 
                                       class="btn btn-primary btn-sm" title="Editar Habitante">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="confirmarEliminacion(<?= $id ?>, '<?= addslashes($nombres . ' ' . $apellidos) ?>')"
                                            title="Eliminar Habitante">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php
                            }
                        }
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
