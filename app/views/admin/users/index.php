<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gestión de Miembros de la Comunidad</h2>
        <div>
            <a href="./index.php?route=admin/dashboard" class="btn btn-secondary me-2">Volver al Dashboard</a>
            <a href="./index.php?route=login/logout" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>

    <a href="./index.php?route=admin/users/create" class="btn btn-primary mb-3">Añadir Nuevo Usuario</a>

    <form action="./index.php" method="GET" class="mb-4">
        <input type="hidden" name="route" value="admin/users">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Buscar:</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="Cédula, nombre, apellido, email" value="<?php echo htmlspecialchars($current_search ?? ''); ?>">
            </div>
            <div class="col-md-3">
                <label for="id_rol" class="form-label">Filtrar por Rol:</label>
                <select class="form-select" id="id_rol" name="id_rol">
                    <option value="all" <?php echo ($current_id_rol == 'all' || $current_id_rol === '') ? 'selected' : ''; ?>>Todos los Roles</option>
                    <option value="1" <?php echo ($current_id_rol == '1') ? 'selected' : ''; ?>>Administrador</option>
                    <option value="2" <?php echo ($current_id_rol == '2') ? 'selected' : ''; ?>>Sub-administrador</option>
                    <option value="3" <?php echo ($current_id_rol == '3') ? 'selected' : ''; ?>>Usuario Común</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="activo" class="form-label">Estado:</label>
                <select class="form-select" id="activo" name="activo">
                    <option value="all" <?php echo ($current_activo == 'all' || $current_activo === '') ? 'selected' : ''; ?>>Todos</option>
                    <option value="1" <?php echo ($current_activo == '1') ? 'selected' : ''; ?>>Activo</option>
                    <option value="0" <?php echo ($current_activo == '0') ? 'selected' : ''; ?>>Inactivo</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="order_by" class="form-label">Ordenar por:</label>
                <div class="input-group">
                    <select class="form-select" id="order_by" name="order_column">
                        <option value="">Seleccionar</option>
                        <option value="nombre" <?php echo ($current_order_column == 'nombre') ? 'selected' : ''; ?>>Nombre</option>
                        <option value="ci_usuario" <?php echo ($current_order_column == 'ci_usuario') ? 'selected' : ''; ?>>Cédula</option>
                        <option value="email" <?php echo ($current_order_column == 'email') ? 'selected' : ''; ?>>Email</option>
                    </select>
                    <select class="form-select" name="order_direction">
                        <option value="ASC" <?php echo ($current_order_direction == 'ASC') ? 'selected' : ''; ?>>Ascendente</option>
                        <option value="DESC" <?php echo ($current_order_direction == 'DESC') ? 'selected' : ''; ?>>Descendente</option>
                    </select>
                </div>
            </div>
            <div class="col-12 d-flex justify-content-end">
                <button type="submit" class="btn btn-info me-2">Aplicar Filtros</button>
                <a href="./index.php?route=admin/users" class="btn btn-warning">Limpiar Filtros</a>
            </div>
        </div>
    </form>

    <?php
    // La lógica de agrupación por roles se mantiene
    $usuariosPorRol = [
        1 => [], // Administradores
        2 => [], // Sub-administradores
        3 => []  // Usuarios Comunes
    ];

    if (!empty($usuarios)) {
        foreach ($usuarios as $usuario) {
            if (isset($usuariosPorRol[$usuario['id_rol']])) {
                $usuariosPorRol[$usuario['id_rol']][] = $usuario;
            }
        }
    }

    /**
     * Función auxiliar para renderizar la tabla de usuarios.
     * @param array $users Array de usuarios a mostrar en la tabla.
     */
    function renderUserTable($users) {
        if (empty($users)) {
            echo '<div class="alert alert-info mt-3" role="alert">No hay usuarios en esta categoría o no coinciden con los filtros aplicados.</div>';
            return;
        }
        ?>
        <div class="table-responsive mt-3">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cédula</th>
                        <th>Nombre Completo</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $usuario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($usuario['id_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['ci_usuario']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                            <td><?php echo htmlspecialchars($usuario['telefono']); ?></td>
                            <td>
                                <?php echo $usuario['activo'] ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>'; ?>
                            </td>
                            <td>
                                <a href="./index.php?route=admin/users/edit&id=<?php echo htmlspecialchars($usuario['id_usuario']); ?>" class="btn btn-warning btn-sm">Editar</a>
                                <a href="./index.php?route=admin/users/delete&id=<?php echo htmlspecialchars($usuario['id_usuario']); ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que quieres eliminar este usuario?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    ?>

    <div class="role-section mb-5">
        <h3 class="mt-4">Administradores</h3>
        <?php renderUserTable($usuariosPorRol[1]); ?>
    </div>

    <div class="role-section mb-5">
        <h3 class="mt-4">Sub-Administradores</h3>
        <?php renderUserTable($usuariosPorRol[2]); ?>
    </div>

    <div class="role-section mb-5">
        <h3 class="mt-4">Usuarios Comunes</h3>
        <?php renderUserTable($usuariosPorRol[3]); ?>
    </div>

</div>