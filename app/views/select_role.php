<?php
// grupobrasil/app/views/select_role.php
$availableRoles = $_SESSION['available_roles'] ?? [];
$nombre_completo = $_SESSION['nombre_completo'] ?? 'Usuario';
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seleccionar Rol - Grupo Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .role-selector-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .role-selector-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .role-selector-header h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .role-selector-header p {
            color: #666;
            font-size: 14px;
        }
        .role-card {
            border: 2px solid #e0e0e0;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .role-card:hover {
            border-color: #667eea;
            background-color: #f8f9ff;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }
        .role-card input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        .role-card.selected {
            border-color: #667eea;
            background-color: #f8f9ff;
        }
        .role-icon {
            font-size: 32px;
            color: #667eea;
            width: 50px;
            text-align: center;
        }
        .role-info {
            flex: 1;
        }
        .role-info h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        .role-info p {
            margin: 5px 0 0 0;
            color: #666;
            font-size: 13px;
        }
        .btn-continue {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }
        .btn-continue:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-continue:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="role-selector-container">
        <div class="role-selector-header">
            <h2>Bienvenido, <?php echo htmlspecialchars($nombre_completo); ?></h2>
            <p>Tienes múltiples roles asignados. Por favor, selecciona con cuál deseas continuar:</p>
        </div>

        <?php if ($error === 'rol_invalido'): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> El rol seleccionado no es válido.
            </div>
        <?php endif; ?>

        <form method="POST" action="./index.php?route=login/setRole" id="roleForm">
            <?php foreach ($availableRoles as $roleId => $roleName): ?>
                <label class="role-card" for="role_<?php echo $roleId; ?>">
                    <input 
                        type="radio" 
                        name="selected_role" 
                        id="role_<?php echo $roleId; ?>" 
                        value="<?php echo $roleId; ?>"
                        required
                    >
                    <div class="role-icon">
                        <?php if ($roleId == 1): ?>
                            <i class="fas fa-user-shield"></i>
                        <?php elseif ($roleId == 2): ?>
                            <i class="fas fa-users-cog"></i>
                        <?php else: ?>
                            <i class="fas fa-home"></i>
                        <?php endif; ?>
                    </div>
                    <div class="role-info">
                        <h5><?php echo htmlspecialchars($roleName); ?></h5>
                        <p>
                            <?php if ($roleId == 1): ?>
                                Acceso completo al sistema
                            <?php elseif ($roleId == 2): ?>
                                Gestión de habitantes y familias de tu vereda
                            <?php else: ?>
                                Gestión de tu familia y hogar
                            <?php endif; ?>
                        </p>
                    </div>
                </label>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-continue">
                <i class="fas fa-arrow-right"></i> Continuar
            </button>
        </form>

        <div class="text-center mt-3">
            <a href="./index.php?route=login/logout" class="text-muted" style="text-decoration: none; font-size: 14px;">
                <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </a>
        </div>
    </div>

    <script>
        // Add visual feedback when selecting a role
        document.querySelectorAll('.role-card').forEach(card => {
            card.addEventListener('click', function() {
                document.querySelectorAll('.role-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
            });
        });
    </script>
</body>
</html>
