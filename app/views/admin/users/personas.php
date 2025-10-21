<?php 
// app/views/admin/users/personas.php 
// Variables disponibles: $page_title, $personas, $success_message, $error_message
?>

<div class="container-fluid">

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
        <?php unset($_SESSION['error_message']); // Limpiar mensaje ?>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
        <?php unset($_SESSION['success_message']); // Limpiar mensaje ?>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Personas Registradas</h6>
            <a href="./index.php?route=admin/users/create" class="btn btn-success btn-sm">
                <i class="fas fa-plus"></i> Añadir Nuevo Habitante
            </a>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Cédula</th>
                            <th>Nombres y Apellidos</th>
                            <th>Ubicación</th>
                            <th>Teléfono</th>
                            <th>Cuenta de Usuario</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (isset($personas) && is_array($personas)):
                            foreach ($personas as $persona): 
                                // ¡CORRECCIÓN AQUÍ! Usar notación de array asociativo []
                                // La línea 42 es donde estaba fallando (id_persona)
                                $id = htmlspecialchars($persona['id_persona']); 
                                $cedula = htmlspecialchars($persona['cedula'] ?? 'N/A'); 
                                $nombres = htmlspecialchars($persona['nombres'] ?? '');
                                $apellidos = htmlspecialchars($persona['apellidos'] ?? '');
                                $casa = htmlspecialchars($persona['numero_casa'] ?? 'S/N');
                                // La columna se llama calle_nombre en el SQL
                                $calle_nombre = htmlspecialchars($persona['calle_nombre'] ?? 'Sin Calle'); 
                                $telefono = htmlspecialchars($persona['telefono'] ?? 'N/A');
                                // La columna se llama tiene_usuario en el SQL
                                $has_user = $persona['tiene_usuario'] ?? false; 
                        ?>
                        <tr>
                            <td><?= $cedula ?></td>
                            <td><?= "$nombres $apellidos" ?></td>
                            <td><?= "Casa $casa, $calle_nombre" ?></td>
                            <td><?= $telefono ?></td>
                            <td>
                                <?php if ($has_user): ?>
                                    <span class="badge badge-success">Sí (Activo)</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <!-- ENLACE CLAVE: Para gestionar roles de LIDERAZGO -->
                                <a href="./index.php?route=admin/users/create-user-role&person_id=<?= $id ?>" class="btn btn-info btn-sm">
                                    <i class="fas fa-user-shield"></i> 
                                    <?= $has_user ? 'Gestionar Roles' : 'Crear Usuario/Líder' ?>
                                </a>
                                
                                <!-- Botón de Edición Estándar -->
                                <a href="./index.php?route=admin/users/edit&person_id=<?= $id ?>" class="btn btn-primary btn-sm ml-2">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        endif; 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Scripts necesarios para DataTables (asumiendo que ya están en el layout) -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof $ !== 'undefined' && typeof $.fn.DataTable !== 'undefined') {
            $('#dataTable').DataTable({
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
        } else {
            // DataTables no está cargado, simplemente mostrar la tabla
            console.log('DataTables library not loaded.');
        }
    });
</script>
