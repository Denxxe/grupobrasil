<?php 
// app/views/admin/users/lideres.php
// Variables disponibles: $page_title, $usuarios, $current_search, $current_activo
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="./index.php" class="form-row align-items-end">
                <input type="hidden" name="route" value="admin/users/lideres">

                <div class="form-group col-md-6">
                    <label for="search">Buscar (Usuario, Cédula, Nombre)</label>
                    <input type="search" id="search" name="search" 
                           value="<?= htmlspecialchars($current_search) ?>"
                           placeholder="Ej: juanperez, V-12345678"
                           class="form-control">
                </div>

                <div class="form-group col-md-3">
                    <label for="activo">Estado</label>
                    <select id="activo" name="activo" class="form-control">
                        <option value="all" <?= $current_activo === 'all' ? 'selected' : '' ?>>Todos</option>
                        <option value="1" <?= $current_activo === '1' ? 'selected' : '' ?>>Activos</option>
                        <option value="0" <?= $current_activo === '0' ? 'selected' : '' ?>>Inactivos</option>
                    </select>
                </div>

                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Líderes Comunitarios</h6>
            <span class="badge badge-info"><?= count($usuarios) ?> registros</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <?php if (empty($usuarios)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No se encontraron líderes con los filtros aplicados.
                    </div>
                <?php else: ?>
                    <table class="table table-bordered" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Usuario</th>
                                <th>Cédula</th>
                                <th>Nombre Completo</th>
                                <th>Email</th>
                                <th>Calles</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($usuarios as $usuario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($usuario['id_usuario'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($usuario['nombre_usuario'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($usuario['cedula'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($usuario['nombre_completo'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($usuario['email'] ?? 'N/A') ?></td>
                                    <td>
                                        <?php 
                                        if (!empty($usuario['calles_asignadas'])) {
                                            echo '<small>' . htmlspecialchars($usuario['calles_asignadas']) . '</small>';
                                        } else {
                                            echo '<span class="text-muted">Sin calles</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if (($usuario['activo'] ?? 0) == 1): ?>
                                            <span class="badge badge-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge badge-danger">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="./index.php?route=admin/users/edit&id=<?= $usuario['id_usuario'] ?? '' ?>" 
                                           class="btn btn-sm btn-info" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="./index.php?route=admin/users/create-user-role&person_id=<?= $usuario['id_persona'] ?? '' ?>" 
                                           class="btn btn-sm btn-secondary" title="Editar roles/veredas">
                                            <i class="fas fa-user-cog"></i>
                                        </a>
                                        <button onclick="if(confirm('¿Revocar rol de esta persona?')){ window.location='./index.php?route=admin/users/revoke-role&person_id=<?= $usuario['id_persona'] ?? '' ?>'; }"
                                                class="btn btn-sm btn-warning" title="Revocar rol">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
