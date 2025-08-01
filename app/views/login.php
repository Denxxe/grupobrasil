<?php
// Mostrar mensajes de error si existen
if (isset($_GET['error'])) {
    $error_message = '';
    switch ($_GET['error']) {
        case 'credenciales_invalidas':
            $error_message = 'Cédula o contraseña incorrectos.';
            break;
        case 'rol_desconocido':
            $error_message = 'Error: Rol de usuario desconocido.';
            break;
        case 'campos_vacios':
            $error_message = 'Por favor, complete todos los campos.';
            break;
        case 'formato_invalido':
            $error_message = 'El formato de la cédula no es válido.';
            break;
        case 'no_sesion': // ¡NUEVO CASE!
            $error_message = 'Debe iniciar sesión para acceder a esta página.';
            break;
        case 'rol_desconocido_sesion': // Nuevo caso para rol desconocido al redirigir logueado
            $error_message = 'Error: Su rol de usuario no permite el acceso directo.';
            break;
        default:
            $error_message = 'Ha ocurrido un error inesperado.';
            break;
    }
    echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión - Sistema Consejo Comunal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/grupobrasil/public/css/style.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-header">Iniciar Sesión</h2>

        <?php
        // Mostrar mensajes de error si existen
        if (isset($_GET['error'])) {
            $error_message = '';
            switch ($_GET['error']) {
                case 'credenciales_invalidas':
                    $error_message = 'Cédula o contraseña incorrectos.';
                    break;
                case 'rol_desconocido':
                    $error_message = 'Error: Rol de usuario desconocido.';
                    break;
                case 'campos_vacios': // Nuevo error para validación del lado del servidor
                    $error_message = 'Por favor, complete todos los campos.';
                    break;
                case 'formato_invalido': // Nuevo error para validación del lado del servidor
                    $error_message = 'El formato de la cédula no es válido.';
                    break;
                default:
                    $error_message = 'Ha ocurrido un error inesperado.';
                    break;
            }
            echo '<div class="alert alert-danger" role="alert">' . $error_message . '</div>';
        }
        ?>

        <form action="/grupobrasil/public/index.php?route=login/authenticate" method="POST" novalidate>
            <div class="mb-3">
                <label for="ci_usuario" class="form-label">Cédula de Identidad:</label>
                <input type="text" class="form-control" id="ci_usuario" name="ci_usuario" 
                       pattern="[0-9]+" title="Solo se permiten números" 
                       minlength="6" maxlength="10" required>
                <div class="invalid-feedback">
                    Por favor, ingrese solo números en la cédula (entre 6 y 10 dígitos).
                </div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña:</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="invalid-feedback">
                    Por favor, ingrese su contraseña.
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Script para añadir clases de validación de Bootstrap en el cliente
        (function () {
            'use strict';
            var form = document.querySelector('form');
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();
    </script>
</body>
</html>