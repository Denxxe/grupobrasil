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

    <style>
        :root {
            --primary-color: <?php echo $color_vinotinto; ?>;
            --accent-color: <?php echo $color_acento; ?>;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa; /* Fondo claro */
        }

        /* Icono de la marca (ahora usa el color primario) */
        .brand-icon {
            font-size: 4rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        .login-heading {
            color: var(--primary-color);
        }

        .btn-login {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            transition: all 0.3s ease;
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            /* Texto e ícono blanco */
            color: #fff !important; 
        }

        /* Hover simplificado, sin sombras ni transformaciones */
        .btn-login:hover {
            background-color: #6a0000; 
            border-color: #6a0000;
            color: #fff !important; /* Asegurar que el texto se mantenga blanco en hover */
        }

        /* Estilo para los campos de formulario flotantes */
        .form-floating > .form-control:focus, 
        .form-floating > .form-control:not(:placeholder-shown) {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(128, 0, 0, 0.25);
        }
        
        .form-control:focus {
             border-color: var(--primary-color);
             box-shadow: 0 0 0 0.25rem rgba(128, 0, 0, 0.25) !important;
        }

        /* Estilo para el botón de ver/ocultar contraseña */
        #togglePassword {
            border-left: 0;
            border-color: #ced4da;
        }
        #togglePassword:hover {
            color: var(--primary-color);
        }
        
        /* Ajuste para el 'Ojito' en el input-group */
        .input-group .form-floating {
            flex: 1;
        }
        .input-group .form-floating .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group #togglePassword {
            border-top-right-radius: 0.375rem;
            border-bottom-right-radius: 0.375rem;
        }

    </style>

</head>
<body class="bg-light">

    <!-- Contenedor principal centrado vertical y horizontalmente --><div class="d-flex justify-content-center align-items-center min-vh-100 p-3 p-md-0">

        <!-- Columna responsiva para la tarjeta de login --><div class="col-11 col-sm-10 col-md-8 col-lg-6 col-xl-4">
            
            <!-- Tarjeta de Login Minimalista con sombra sutil --><div class="card shadow-sm border-0" style="border-radius: 1.5rem;">
                <div class="card-body p-4 p-md-5">

                    <!-- Encabezado de la marca (Movido aquí) --><div class="text-center mb-4">
                        <i class="bi bi-buildings-fill brand-icon"></i>
                        <h1 class="h4 fw-bold text-dark">Sistema de Gestión Comunal</h1>
                        <p class="text-muted mb-0">Consejo Comunal <b>BRASIL</b> sector 3</p>
                    </div>

                    <!-- Encabezado del formulario --><div class="text-center text-md-start mb-4">
                        <h2 class="login-heading fw-bolder display-5">Acceso</h2>
                        <p class="text-muted">Ingrese su Cédula y Contraseña.</p>
                    </div>

                    <?php
                    // Bloque PHP de manejo de errores (Sin cambios)
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

                    <!-- Formulario de Login (Sin cambios en 'action' o 'name') --><form action="/grupobrasil/public/index.php?route=login/authenticate" method="POST" id="loginForm" novalidate>
                        
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
                            <div class="invalid-feedback">La cédula debe contener solo números (máx. 9 dígitos).</div>
                        </div>
                        
                        <!-- Grupo de Contraseña con 'Ojito' --><div class="input-group mb-3">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña" required maxlength="16">
                                <label for="password"><i class="bi bi-lock-fill me-2"></i>Contraseña</label>
                                <div class="invalid-feedback">Por favor, ingrese su contraseña.</div>
                            </div>
                            <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                <i class="bi bi-eye-fill"></i>
                            </button>
                        </div>

                        <div class="d-grid mt-4">
                            <!-- Botón minimalista (sin sombra) --><button class="btn btn-lg btn-login text-uppercase fw-bold mb-2 rounded-pill" type="submit">
                                <i class="bi bi-box-arrow-in-right me-2"></i> INGRESAR
                            </button>
                        </div>
                
                    </form>
                </div>
            </div>

            <!-- Footer sutil opcional --><footer class="text-center text-muted mt-4">
                <small>&copy; <?php echo date('Y'); ?> Consejo Comunal BRASIL. Todos los derechos reservados.</small>
            </footer>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script de funcionalides (Sin cambios) --><script>
        // === 1. Validación de Cédula (Solo números) ===
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

            // Asegurarse de que los elementos existen antes de añadir listeners
            if(toggleButton && passwordInput) {
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
            }

            // Asignar la función de restricción de números al campo de cédula
            const ciInput = document.getElementById('ci_usuario');
            if(ciInput) {
                ciInput.addEventListener('keypress', isNumberKey);
            }

            // === 3. Validación de Bootstrap para el formulario ===
            const form = document.getElementById('loginForm');
            if(form && ciInput) {
                form.addEventListener('submit', function(event) {
                    // Comprobación específica de la cédula al enviar
                    if (ciInput.value.length > 9 || !/^\d{6,9}$/.test(ciInput.value)) {
                        ciInput.setCustomValidity("Cédula inválida. Debe tener entre 6 y 9 dígitos numéricos.");
                    } else {
                        ciInput.setCustomValidity(""); // Restablecer la validación
                    }
                    
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            }
        });
    </script>
</body>
</html>


