

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Crear Nuevo Miembro de la Comunidad</h2>
            <div>
                <a href="/grupobrasil/public/index.php?route=admin/users" class="btn btn-secondary me-2">Volver a la Lista de Usuarios</a>
                <a href="/grupobrasil/public/index.php?route=login/logout" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" role="alert">
                <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <form action="/grupobrasil/public/index.php?route=admin/users/store" method="POST">
            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="ci_usuario" class="form-label">Cédula de Identidad:</label>
                    <input type="text" class="form-control" id="ci_usuario" name="ci_usuario" value="<?php echo htmlspecialchars($old_data['ci_usuario'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="id_rol" class="form-label">Rol:</label>
                    <select class="form-select" id="id_rol" name="id_rol" required>
                        <option value="">Seleccionar Rol</option>
                        <?php foreach ($roles as $id => $nombre): ?>
                            <option value="<?php echo htmlspecialchars($id); ?>" <?php echo (isset($old_data['id_rol']) && $old_data['id_rol'] == $id) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($old_data['nombre'] ?? ''); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="apellido" class="form-label">Apellido:</label>
                    <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($old_data['apellido'] ?? ''); ?>" required>
                </div>
            </div>

            <button type="submit" class="btn btn-primary">Crear Usuario</button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>