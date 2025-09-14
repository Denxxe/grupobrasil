<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Consejo Comunal</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

    <!-- Hoja de estilos personalizada -->
    <link rel="stylesheet" href="../public/css/loginstyles.css"> 

</head>
<body>

    <div class="container-fluid ps-md-0">
        <div class="row g-0">
            <!-- Columna de Marca (Izquierda) -->
            <div class="col-md-5 col-lg-6 d-none d-md-flex bg-image">
                <div class="brand-panel">
                    <div class="brand-content">
                        <i class="bi bi-buildings-fill brand-icon"></i>
                        <h1 class="text-white">Bienvenido al Sistema de Gestión</h1>
                        <p class="text-white-50">Consejo Comunal</p>
                    </div>
                </div>
            </div>

            <!-- Columna del Formulario (Derecha) -->
            <div class="col-md-7 col-lg-6">
                <div class="login d-flex align-items-center py-5">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-10 col-lg-8 mx-auto">
                                <div class="text-center text-md-start mb-4">
                                     <h2 class="login-heading mb-0 fw-bold">Iniciar Sesión</h2>
                                </div>

                                <?php
                                if (isset($_GET['error'])) {
                                    $error_message = '';
                                    switch ($_GET['error']) {
                                        case 'credenciales_invalidas': $error_message = 'Cédula o contraseña incorrectos.'; break;
                                        case 'rol_desconocido': $error_message = 'Error: Rol de usuario desconocido.'; break;
                                        case 'campos_vacios': $error_message = 'Por favor, complete todos los campos.'; break;
                                        case 'formato_invalido': $error_message = 'El formato de la cédula no es válido.'; break;
                                        case 'no_sesion': $error_message = 'Debe iniciar sesión para acceder a esta página.'; break;
                                        case 'rol_desconocido_sesion': $error_message = 'Error: Su rol no permite el acceso directo.'; break;
                                        default: $error_message = 'Ha ocurrido un error inesperado.'; break;
                                    }
                                    echo '<div class="alert alert-danger d-flex align-items-center" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><div>' . htmlspecialchars($error_message) . '</div></div>';
                                }
                                ?>

                                <form action="/grupobrasil/public/index.php?route=login/authenticate" method="POST" novalidate>
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control" id="ci_usuario" name="ci_usuario" placeholder="Cédula" pattern="[0-9]+" minlength="6" maxlength="10" required>
                                        <label for="ci_usuario"><i class="bi bi-person-vcard me-2"></i>Cédula de Identidad</label>
                                        <div class="invalid-feedback">Cédula inválida (solo números, 6-10 dígitos).</div>
                                    </div>
                                    
                                    <div class="form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                                        <label for="password"><i class="bi bi-lock-fill me-2"></i>Contraseña</label>
                                        <div class="invalid-feedback">Por favor, ingrese su contraseña.</div>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button class="btn btn-lg btn-primary btn-login text-uppercase fw-bold mb-2" type="submit">Ingresar</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (() => {
            'use strict';
            const forms = document.querySelectorAll('form');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>
</html>
