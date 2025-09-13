<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Sub-Administrador - Sistema Consejo Comunal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Dashboard de Sub-Administrador</h2>
            <a href="./index.php?route=login/logout" class="btn btn-danger">Cerrar Sesión</a>
        </div>

        <?php if (isset($_SESSION['nombre_usuario'])): ?>
            <p>Bienvenido, **<?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>** (Sub-Administrador).</p>
        <?php endif; ?>

        <div class="alert alert-info" role="alert">
            Aquí podrás gestionar los usuarios que te han sido asignados y acceder a tus herramientas específicas.
        </div>

        <div class="mt-4">
            <h3>Opciones Disponibles:</h3>
            <ul class="list-group">
                <li class="list-group-item"><a href="#">Ver y Editar Usuarios Asignados</a></li>
                <li class="list-group-item"><a href="#">Consultar Pagos de mi lista</a></li>
                </ul>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>