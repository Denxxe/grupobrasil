<?php
// grupobrasil/app/views/user/dashboard.php

// Verificar si el usuario está autenticado y es un usuario común
if (!isset($_SESSION['id_usuario']) || $_SESSION['id_rol'] != 3) {
    header('Location: /grupobrasil/public/index.php?route=login&error=acceso_denegado');
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Usuario</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/grupobrasil/public/css/style.css">
    </head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="text-center">Bienvenido al Dashboard de Usuario, <?php echo htmlspecialchars($_SESSION['nombre_completo'] ?? 'Usuario'); ?>!</h1>
            <div>
                <a href="/grupobrasil/public/index.php?route=login/logout" class="btn btn-danger">Cerrar Sesión</a>
            </div>
        </div>

        <div class="alert alert-success" role="alert">
            ¡Has iniciado sesión exitosamente como usuario común!
        </div>

        <p>Aquí podrás ver y gestionar la información relevante para tu perfil y las actividades de la comunidad.</p>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Mi Perfil</h5>
                        <p class="card-text">Consulta y actualiza tus datos personales.</p>
                        <a href="/grupobrasil/public/index.php?route=user/view_profile" class="btn btn-primary">Ver Perfil</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Noticias de la Comunidad</h5>
                        <p class="card-text">Mantente al tanto de los avisos y eventos importantes.</p>
                        <a href="/grupobrasil/public/index.php?route=noticias" class="btn btn-info">Ver Noticias</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>