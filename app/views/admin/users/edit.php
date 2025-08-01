<?php

// Aseguramos que las variables esenciales existan para evitar errores de referencia.
// Si no están definidas, redirigimos o manejamos el error de forma adecuada.
if (!isset($user_data_to_display) || !isset($roles)) {
    // Es buena práctica registrar este tipo de error en un log para depuración.
    error_log("Error: Datos de usuario o roles faltantes en edit_user.php");
    // Redirigimos de vuelta a la lista de usuarios con un mensaje de error.
    $_SESSION['error_message'] = 'Error al cargar los datos del usuario. Intente de nuevo.';
    header('Location: /grupobrasil/public/index.php?route=admin/users');
    exit();
}

// Capturamos los mensajes de la sesión para mostrarlos y luego los eliminamos.
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// Nota: $user_data_to_display ya debería contener los datos del usuario
// o los datos del formulario si hubo un error de validación previa (en el controlador).

// Si hay un mensaje de error, asumimos que el formulario debe mostrar los errores de validación.
// Esto activará las clases de Bootstrap 'is-invalid' en los campos.
$form_validation_class = $error_message ? 'was-validated' : '';

?>


    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100;">
        <?php if ($success_message): ?>
            <div class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true" id="successToast">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="toast align-items-center text-bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true" id="errorToast">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo $error_message; // El mensaje de error ya está sanitizado desde el controlador ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php include __DIR__ . '/../../../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Editar Usuario: <?php echo htmlspecialchars($user_data_to_display['nombre'] . ' ' . $user_data_to_display['apellido']); ?></h2>
            <div>
                <a href="/grupobrasil/public/index.php?route=admin/users" class="btn btn-secondary me-2">Volver a Gestión de Usuarios</a>
                <a href="/grupobrasil/public/index.php?route=login/logout" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </div>

        <form action="/grupobrasil/public/index.php?route=admin/updateUser" method="POST" class="needs-validation <?php echo $form_validation_class; ?>" novalidate>
            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($user_data_to_display['id_usuario']); ?>">

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="ci_usuario" class="form-label">Cédula de Identidad:</label>
                    <input type="text" class="form-control" id="ci_usuario" name="ci_usuario" value="<?php echo htmlspecialchars($user_data_to_display['ci_usuario'] ?? ''); ?>" required>
                    <div class="invalid-feedback">La cédula es obligatoria y debe ser válida.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user_data_to_display['email'] ?? ''); ?>" required>
                    <div class="invalid-feedback">El email es obligatorio y debe tener un formato válido.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="nombre" class="form-label">Nombre:</label>
                    <input type="text" class="form-control" id="nombre" name="nombre" value="<?php echo htmlspecialchars($user_data_to_display['nombre'] ?? ''); ?>" required>
                    <div class="invalid-feedback">El nombre es obligatorio.</div>
                </div>

                <div class="col-md-6 mb-3">
                    <label for="apellido" class="form-label">Apellido:</label>
                    <input type="text" class="form-control" id="apellido" name="apellido" value="<?php echo htmlspecialchars($user_data_to_display['apellido'] ?? ''); ?>" required>
                    <div class="invalid-feedback">El apellido es obligatorio.</div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="telefono" class="form-label">Teléfono:</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($user_data_to_display['telefono'] ?? ''); ?>">
                </div>

                <div class="col-md-6 mb-3">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($user_data_to_display['fecha_nacimiento'] ?? ''); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label for="direccion" class="form-label">Dirección:</label>
                <textarea class="form-control" id="direccion" name="direccion" rows="3"><?php echo htmlspecialchars($user_data_to_display['direccion'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="biografia" class="form-label">Biografía:</label>
                <textarea class="form-control" id="biografia" name="biografia" rows="3"><?php echo htmlspecialchars($user_data_to_display['biografia'] ?? ''); ?></textarea>
            </div>

            <div class="mb-3">
                <label for="foto_perfil" class="form-label">URL Foto de Perfil:</label>
                <input type="text" class="form-control" id="foto_perfil" name="foto_perfil" value="<?php echo htmlspecialchars($user_data_to_display['foto_perfil'] ?? ''); ?>">
            </div>

            <div class="mb-3">
                <label for="id_rol" class="form-label">Rol:</label>
                <select class="form-select" id="id_rol" name="id_rol" required>
                    <option value="">Seleccione un rol</option>
                    <?php foreach ($roles as $rol): ?>
                        <option value="<?php echo htmlspecialchars($rol['id_rol']); ?>"
                            <?php echo ((isset($user_data_to_display['id_rol']) && $user_data_to_display['id_rol'] == $rol['id_rol'])) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($rol['nombre_rol']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback">Debe seleccionar un rol.</div>
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Nueva Contraseña (dejar en blanco para no cambiar):</label>
                <input type="password" class="form-control" id="password" name="password">
                <div class="form-text text-muted">Mínimo 8 caracteres, al menos una mayúscula, una minúscula, un número y un símbolo.</div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" <?php echo ((isset($user_data_to_display['activo']) && $user_data_to_display['activo'] == 1)) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="activo">Usuario Activo</label>
                </div>

                <div class="col-md-6 mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="requires_setup" name="requires_setup" value="1" <?php echo ((isset($user_data_to_display['requires_setup']) && $user_data_to_display['requires_setup'] == 1)) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="requires_setup">Requiere Configuración Inicial (redirigido a configurar perfil al iniciar sesión)</label>
                </div>
            </div>

            <div class="d-flex justify-content-start gap-2 mt-3">
                <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                <a href="/grupobrasil/public/index.php?route=admin/users" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/grupobrasil/public/js/edit_user_form.js"></script>
    <script>
        // Script para inicializar los toasts de Bootstrap y la validación
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar Toasts
            var successToastEl = document.getElementById('successToast');
            if (successToastEl) {
                var successToast = new bootstrap.Toast(successToastEl);
                successToast.show();
            }

            var errorToastEl = document.getElementById('errorToast');
            if (errorToastEl) {
                var errorToast = new bootstrap.Toast(errorToastEl);
                errorToast.show();
            }

            // Activar validación de Bootstrap en el formulario
            var forms = document.querySelectorAll('.needs-validation');
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        });
    </script>
</body>
</html>