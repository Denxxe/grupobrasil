<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Definición de colores de tu marca para consistencia
$color_vinotinto = '#800000'; // Color primario
$color_acento = '#E0A800';   // Color secundario (Dorado/Crema)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso al Sistema - Consejo Comunal BRASIL</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

     <link rel="stylesheet" href="../public/css/loginstyles.css"> 

    <style>
        /* Estilos en línea para mantener la estética si no usas loginstyles.css */
        :root {
            --primary-color: <?php echo $color_vinotinto; ?>;
            --accent-color: <?php echo $color_acento; ?>;
        }
        .bg-image {
            background-image: url('path/to/your/background/image.jpg'); /* Reemplaza con tu imagen de fondo */
            background-size: cover;
            background-position: center;
            min-height: 100vh;
        }
        .brand-panel {
            background: rgba(0, 0, 0, 0.4); /* Capa oscura semitransparente */
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3rem;
            text-align: center;
        }
        .brand-icon {
            font-size: 5rem;
            color: var(--accent-color);
            margin-bottom: 1rem;
        }
        .login-heading {
            color: var(--primary-color);
        }
        .btn-login {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: background-color 0.3s ease;
        }
        .btn-login:hover {
            background-color: #6a0000; /* Tono más oscuro de Vinotinto */
            border-color: #6a0000;
        }
        /* Estilo para los campos de formulario flotantes */
        .form-floating > .form-control:focus, .form-floating > .form-control:not(:placeholder-shown) {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(128, 0, 0, 0.25);
        }
    </style>

</head>
<body>

    <div class="container-fluid ps-md-0">
        <div class="row g-0">
            <div class="col-md-5 col-lg-6 d-none d-md-flex bg-image">
                <div class="brand-panel">
                    <div class="brand-content">
                        <i class="bi bi-buildings-fill brand-icon"></i>
                        <h1 class="text-white fw-bold">Sistema de Gestión Comunal</h1>
                        <h3 class="text-white-50 mt-3">Consejo Comunal <b>BRASIL</b> sector 3</h3>
                    </div>
                </div>
            </div>

            <div class="col-md-7 col-lg-6">
                <div class="login d-flex align-items-center py-5" style="min-height: 100vh;">
                    <div class="container">
                        <div class="row">
                            <div class="col-md-10 col-lg-8 mx-auto">
                                <div class="text-center text-md-start mb-5">
                                    <h2 class="login-heading fw-bolder display-5">Acceso</h2>
                                    <p class="text-muted">Ingrese su Cédula y Contraseña para continuar.</p>
                                </div>

                                <?php
                                // Bloque PHP de manejo de errores mejorado
                                if (isset($_GET['error'])) {
                                    $error_map = [
                                        'credenciales_invalidas' => 'Cédula o contraseña incorrectos.',
                                        'rol_desconocido' => 'Error: Rol de usuario desconocido.',
                                        'campos_vacios' => 'Por favor, complete todos los campos.',
                                        'formato_invalido' => 'El formato de la cédula no es válido.',
                                        'no_sesion' => 'Debe iniciar sesión para acceder a esta página.',
                                        'rol_desconocido_sesion' => 'Error: Su rol no permite el acceso directo.',
                                        'default' => 'Ha ocurrido un error inesperado.',
                                    ];
                                    $error_code = $_GET['error'];
                                    $error_message = $error_map[$error_code] ?? $error_map['default'];
                                    
                                    echo '<div class="alert alert-danger d-flex align-items-center mb-4" role="alert"><i class="bi bi-exclamation-triangle-fill me-2"></i><div>' . htmlspecialchars($error_message) . '</div></div>';
                                }
                                ?>

                                <form action="/grupobrasil/public/index.php?route=login/authenticate" method="POST" id="loginForm" novalidate>
                                    
                                    <div class="form-floating mb-3">
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="ci_usuario" 
                                            name="ci_usuario" 
                                            placeholder="Cédula" 
                                            pattern="[0-9]{6,9}" 
                                            maxlength="9" 
                                            required 
                                            inputmode="numeric" 
                                            onkeypress="return isNumberKey(event)"
                                        >
                                        <label for="ci_usuario"><i class="bi bi-person-vcard me-2"></i>Cédula de Identidad</label>
                                        <div class="invalid-feedback">La cédula debe contener solo números y un máximo de 9 dígitos.</div>
                                    </div>
                                    
                                    <div class="input-group form-floating mb-3">
                                        <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required>
                                        <label for="password"><i class="bi bi-lock-fill me-2"></i>Contraseña</label>
                                        <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword" style="border-radius: 0 0.25rem 0.25rem 0;">
                                            <i class="bi bi-eye-fill"></i>
                                        </button>
                                        <div class="invalid-feedback">Por favor, ingrese su contraseña.</div>
                                    </div>

                                    <div class="d-grid mt-4">
                                        <button class="btn btn-lg btn-login text-uppercase fw-bold mb-2" type="submit">
                                            <i class="bi bi-box-arrow-in-right me-2"></i> INGRESAR
                                        </button>
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
        // === 1. Validación de Cédula (Solo números, Máx 9 dígitos) ===
        function isNumberKey(evt) {
            const charCode = (evt.which) ? evt.which : event.keyCode;
            // Permitir números (48 a 57)
            if (charCode > 31 && (charCode < 48 || charCode > 57)) {
                return false;
            }
            return true;
        }

        // === 2. Funcionalidad del 'Ojito' para ver/ocultar contraseña ===
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const toggleButton = document.getElementById('togglePassword');
            const toggleIcon = toggleButton.querySelector('i');

            toggleButton.addEventListener('click', function (e) {
                // Alternar el tipo de input entre 'password' y 'text'
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Cambiar el icono del botón
                if (type === 'password') {
                    toggleIcon.classList.remove('bi-eye-slash-fill');
                    toggleIcon.classList.add('bi-eye-fill');
                } else {
                    toggleIcon.classList.remove('bi-eye-fill');
                    toggleIcon.classList.add('bi-eye-slash-fill');
                }
            });

            // Asignar la función de restricción de números al campo de cédula
            const ciInput = document.getElementById('ci_usuario');
            ciInput.addEventListener('keypress', isNumberKey);

            // También forzar la limitación de 9 dígitos para la validación del navegador
            ciInput.setAttribute('maxlength', '9');

            // === 3. Validación de Bootstrap para el formulario ===
            const form = document.getElementById('loginForm');
            form.addEventListener('submit', function(event) {
                // Comprobación específica de la cédula al enviar (9 dígitos max)
                if (ciInput.value.length > 9 || !/^\d+$/.test(ciInput.value)) {
                    ciInput.setCustomValidity("Cédula inválida. Máximo 9 dígitos numéricos.");
                } else {
                    ciInput.setCustomValidity(""); // Restablecer la validación
                }
                
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    </script>
</body>
</html>