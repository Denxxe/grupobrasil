<!-- grupobrasil/app/views/subadmin/familias/ver.php -->

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detalles de Familia</h1>
        <a href="./index.php?route=subadmin/familias" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Información del Jefe de Familia -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 font-weight-bold">Jefe de Familia</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Nombre Completo:</strong> <?php echo htmlspecialchars($personaJefe['nombres'] . ' ' . $personaJefe['apellidos']); ?></p>
                    <p><strong>Cédula:</strong> <?php echo htmlspecialchars($personaJefe['cedula'] ?? 'N/A'); ?></p>
                    <p><strong>Fecha de Nacimiento:</strong> <?php echo htmlspecialchars($personaJefe['fecha_nacimiento'] ?? 'N/A'); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($personaJefe['telefono'] ?? 'N/A'); ?></p>
                    <p><strong>Correo:</strong> <?php echo htmlspecialchars($personaJefe['correo'] ?? 'N/A'); ?></p>
                    <p><strong>Dirección:</strong> <?php echo htmlspecialchars($personaJefe['direccion'] ?? 'N/A'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Miembros de la Familia -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Miembros de la Familia (<?php echo count($miembros); ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (empty($miembros)): ?>
                <p class="text-center text-muted">Esta familia no tiene miembros registrados aún.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre Completo</th>
                                <th>Cédula</th>
                                <th>Parentesco</th>
                                <th>Fecha de Nacimiento</th>
                                <th>Sexo</th>
                                <th>Teléfono</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($miembros as $miembro): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($miembro['nombres'] . ' ' . $miembro['apellidos']); ?></td>
                                    <td><?php echo htmlspecialchars($miembro['cedula'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($miembro['parentesco'] ?? 'N/A'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($miembro['fecha_nacimiento'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($miembro['sexo'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($miembro['telefono'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
