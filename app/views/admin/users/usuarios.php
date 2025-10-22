<?php 
// Ruta: grupobrasil/app/views/admin/users/usuarios.php
// Esta vista muestra el listado de usuarios (líderes) que tienen acceso al sistema.

// Extracción de datos pasados desde AdminController::usuarios()
$usuarios = $data['usuarios'] ?? [];
$current_search = $data['current_search'] ?? '';
$current_rol = $data['current_rol'] ?? 'all';
$current_activo = $data['current_activo'] ?? 'all';

?>

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
        
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="./index.php?route=admin/dashboard">Inicio</a></li>
                    <li class="breadcrumb-item active">Usuarios</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Filtros de Búsqueda</h3>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="./index.php" class="form-row align-items-end">
                            <input type="hidden" name="route" value="admin/users/usuarios">

                            <div class="form-group col-md-4">
                                <label for="search">Buscar (Usuario, Cédula, Nombre)</label>
                                <input type="search" id="search" name="search" 
                                       value="<?= htmlspecialchars($current_search) ?>"
                                       placeholder="Ej: juanperez, V-12345678"
                                       class="form-control">
                            </div>

                            <div class="form-group col-md-2">
                                <label for="rol">Filtrar por Rol</label>
                                <select id="rol" name="rol" class="form-control">
                                    <option value="all" <?= $current_rol === 'all' ? 'selected' : '' ?>>Todos los Roles</option>
                                    <option value="1" <?= $current_rol == 1 ? 'selected' : '' ?>>1 - Administrador</option>
                                    <option value="2" <?= $current_rol == 2 ? 'selected' : '' ?>>2 - Sub-Admin</option>
                                    <option value="3" <?= $current_rol == 3 ? 'selected' : '' ?>>3 - Usuario Común</option>
                                    </select>
                            </div>

                            <div class="form-group col-md-2">
                                <label for="activo">Estado Usuario</label>
                                <select name="activo" id="activo" class="form-control">
                                    <option value="all" <?= ($current_activo ?? '') == 'all' ? 'selected' : '' ?>>Todos</option>
                                    <option value="1" <?= ($current_activo ?? '') == '1' ? 'selected' : '' ?>>Activos</option>
                                    <option value="0" <?= ($current_activo ?? '') == '0' ? 'selected' : '' ?>>Inactivos</option>
                                </select>
                            </div>

                            <div class="form-group col-md-4 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary mr-2"><i class="fas fa-search"></i> Buscar / Filtrar</button>
                                <?php if (!empty($current_search) || $current_rol !== 'all' || $current_activo !== 'all'): ?>
                                    <a href="./index.php?route=admin/users/usuarios" class="btn btn-secondary"><i class="fas fa-undo"></i> Limpiar</a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Resultados</h3>
                    </div>
                    
                    <div class="card-body">
                        <table id="usuariosTable" class="table table-bordered table-striped responsive">
                            <thead>
                                <tr>
                                    <th>Usuario / Cédula</th>
                                    <th>Líder Asociado</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($usuarios)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">
                                            No se encontraron usuarios con los criterios de búsqueda o filtro.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td>
                                                <div class="font-weight-bold">
                                                    <?= htmlspecialchars($usuario['nombre_usuario'] ?? 'N/A') ?>
                                                </div>
                                                <small class="text-muted">
                                                    C.I.: <?= htmlspecialchars($usuario['cedula'] ?? 'N/A') ?>
                                                </small>
                                            </td>

                                            <td>
                                                <div class="font-weight-bold">
                                                    <?= htmlspecialchars(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? '')) ?>
                                                </div>
                                                <small class="text-info">
                                                    <?= htmlspecialchars($usuario['correo'] ?? 'Sin correo') ?>
                                                </small>
                                            </td>

                                            <td>
                                                <?php 
                                                    $rol = $usuario['id_rol'] ?? 0;
                                                    $badge_class = 'badge-secondary';
                                                    if ($rol == 1) $badge_class = 'badge-danger';
                                                    else if ($rol == 2) $badge_class = 'badge-primary';
                                                    else if ($rol == 3) $badge_class = 'badge-success';
                                                ?>
                                                <span class="badge <?= $badge_class ?>">
                                                    <?= $rol == 1 ? 'ADMIN' : ($rol == 2 ? 'SUB-ADMIN' : ($rol == 3 ? 'LÍDER' : 'DESCONOCIDO')) ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php if ($usuario['activo'] ?? 0): ?>
                                                    <span class="badge badge-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">Inactivo</span>
                                                <?php endif; ?>
                                            </td>

                                            <td>
                                                <a href="./index.php?route=admin/users/edit&id=<?= htmlspecialchars($usuario['id_usuario'] ?? '') ?>" 
                                                   title="Editar usuario"
                                                   class="btn btn-info btn-sm">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <?php $action_status = ($usuario['activo'] ?? 0) ? 0 : 1; ?>
                                                <button onclick="confirmToggleStatus('<?= htmlspecialchars($usuario['id_usuario'] ?? '') ?>', '<?= htmlspecialchars($usuario['nombre_usuario'] ?? '') ?>', <?= $action_status ?>)"
                                                        title="<?= $action_status == 1 ? 'Activar Usuario' : 'Inactivar Usuario' ?>"
                                                        class="btn btn-<?= $action_status == 1 ? 'success' : 'danger' ?> btn-sm">
                                                    <i class="fas fa-power-off"></i>
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
    </div>
</section>

<script>
    function confirmToggleStatus(id, username, status) {
        const actionText = status === 1 ? 'Activar' : 'Inactivar';
        const confirmMsg = `¿Está seguro que desea ${actionText.toLowerCase()} al usuario ${username}?`;
        
        if (confirm(confirmMsg)) {
            // NOTA: Debes implementar esta ruta en AdminController.php
            window.location.href = `./index.php?route=admin/users/toggle_status&id=${id}&status=${status}`;
        }
    }

    // Inicializar DataTables
    $(function () {
        $('#usuariosTable').DataTable({
            "responsive": true,
            "autoWidth": false,
            "paging": true,
            "lengthChange": true,
            "searching": false, 
            "ordering": true,
            "info": true,
        });
    });
</script>