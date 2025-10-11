<div class="container">
        <div class="card setup-card mx-auto">
            <div class="card-header setup-card-header text-center">
                <i class="fa-solid fa-user-gear fa-2x mb-2"></i>
                <h4 class="mb-0">Configuración Inicial de Perfil Obligatoria</h4>
            </div>
            
            <div class="card-body">

                <?php if (isset($temp_message)): ?>
                    <div class="alert alert-info text-center border-info" role="alert">
                        <i class="fa-solid fa-circle-exclamation me-2"></i>
                        <strong>¡Primer Ingreso!</strong> <?php echo htmlspecialchars($temp_message); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($form_errors)): ?>
                    <div class="alert alert-danger" role="alert">
                        <h5 class="alert-heading"><i class="fa-solid fa-triangle-exclamation me-2"></i> Errores de Formulario:</h5>
                        <ul class="mb-0">
                            <?php foreach ($form_errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form action="./index.php?route=user/updateProfile" method="POST">
                    
                     Sección para mostrar datos ya registrados por el administrador 
                    <h5 class="form-section-title"><i class="fa-solid fa-id-card me-2"></i> Datos Registrados</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label">Cédula:</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['ci_usuario'] ?? ''); ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nombre:</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['nombre'] ?? ''); ?>" disabled>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Apellido:</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user_data['apellido'] ?? ''); ?>" disabled>
                        </div>
                    </div>

                    <h5 class="form-section-title"><i class="fa-solid fa-address-card me-2"></i> Datos Faltantes</h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" 
                                value="<?php echo htmlspecialchars($old_form_data['fecha_nacimiento'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="telefono" class="form-label">Teléfono:</label>
                            <input type="text" class="form-control" id="telefono" name="telefono" 
                                value="<?php echo htmlspecialchars($old_form_data['telefono'] ?? ''); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="direccion" class="form-label">Dirección:</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" 
                                value="<?php echo htmlspecialchars($old_form_data['direccion'] ?? ''); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">Correo Electrónico:</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                value="<?php echo htmlspecialchars($old_form_data['email'] ?? ''); ?>" required>
                        </div>
                        <div class="col-12">
                            <label for="biografia" class="form-label">Biografía (Opcional):</label>
                            <textarea class="form-control" id="biografia" name="biografia" rows="3"><?php echo htmlspecialchars($old_form_data['biografia'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <h5 class="form-section-title mt-4"><i class="fa-solid fa-lock me-2"></i> Cambio de Contraseña</h5>
                    
                    <div class="alert alert-warning" role="alert">
                        Tu contraseña actual es tu <strong>Cédula de Identidad</strong>. Debes cambiarla obligatoriamente por una nueva para asegurar tu cuenta.
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label for="password_actual" class="form-label">Contraseña Actual (Tu Cédula):</label>
                            <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                        </div>
                        <div class="col-md-6">
                            <label for="new_password" class="form-label">Nueva Contraseña:</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <div class="form-text">Mínimo 6 caracteres.</div>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_new_password" class="form-label">Confirmar Nueva Contraseña:</label>
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                        </div>
                    </div>

                    <hr class="my-4">

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fa-solid fa-circle-check me-2"></i> Completar y Acceder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
