<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completar Perfil y Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/grupobrasil/public/css/style.css">
    <style>
        .container {
            max-width: 800px;
        }
        .form-section {
            background-color: #f8f9fa;
            padding: 2rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .welcome-message {
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.1rem;
            color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h1 class="text-center mb-4">Configuración Inicial de Perfil</h1>

        <?php if (isset($temp_message)): ?>
            <div class="alert alert-info welcome-message" role="alert">
                <?php echo htmlspecialchars($temp_message); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($form_errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul>
                    <?php foreach ($form_errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="/grupobrasil/public/index.php?route=user/update_profile" method="POST">
            <div class="form-section">
                <h3>Datos Personales Faltantes</h3>
                <div class="mb-3">
                    <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento:</label>
                    <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="<?php echo htmlspecialchars($old_form_data['fecha_nacimiento'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="direccion" class="form-label">Dirección:</label>
                    <input type="text" class="form-control" id="direccion" name="direccion" value="<?php echo htmlspecialchars($old_form_data['direccion'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="telefono" class="form-label">Teléfono:</label>
                    <input type="text" class="form-control" id="telefono" name="telefono" value="<?php echo htmlspecialchars($old_form_data['telefono'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Correo Electrónico:</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($old_form_data['email'] ?? ''); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="biografia" class="form-label">Biografía (Opcional):</label>
                    <textarea class="form-control" id="biografia" name="biografia" rows="3"><?php echo htmlspecialchars($old_form_data['biografia'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="form-section">
                <h3>Cambiar Contraseña</h3>
                <div class="alert alert-warning" role="alert">
                    Tu contraseña actual es tu Cédula de Identidad. Debes cambiarla por una nueva.
                </div>
                <div class="mb-3">
                    <label for="password_actual" class="form-label">Contraseña Actual (Tu Cédula):</label>
                    <input type="password" class="form-control" id="password_actual" name="password_actual" required>
                </div>
                <div class="mb-3">
                    <label for="new_password" class="form-label">Nueva Contraseña:</label>
                    <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                    <div class="form-text">Mínimo 6 caracteres.</div>
                </div>
                <div class="mb-3">
                    <label for="confirm_new_password" class="form-label">Confirmar Nueva Contraseña:</label>
                    <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-success btn-lg">Completar Configuración</button>
            </div>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>