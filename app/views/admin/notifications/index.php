<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"></h1>
        <a href="./index.php?route=admin/notifications/mark-all-read" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
            <i class="fas fa-check-double fa-sm text-white-50"></i> Marcar todas como leídas
        </a>
    </div>

    <?php 
    // Muestra los mensajes flash si existen. Estas variables vienen de index.php
    if (isset($success_message) && $success_message): ?>
        <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if (isset($error_message) && $error_message): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Notificaciones del Sistema</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Origen</th>
                            <th>Rol</th>
                            <th>Tipo</th>
                            <th>Mensaje</th>
                            <th>Usuario Destino</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($notificaciones as $notificacion): 
                            $is_leida = (int)($notificacion['leido'] ?? 0) === 1;
                        ?>
                            <tr class="<?= $is_leida ? '' : 'table-warning' ?>">
                                <td><?= htmlspecialchars($notificacion['id_notificacion'] ?? '') ?></td>
                                <td><?= htmlspecialchars(($notificacion['origen_nombres'] ?? '') . ' ' . ($notificacion['origen_apellidos'] ?? '')) ?></td>
                                <td>
                                    <?php
                                        // Mapear nombres de rol a etiquetas legibles solicitadas
                                        $rawRole = $notificacion['origen_rol'] ?? '';
                                        $roleMap = [
                                            'Administrador' => 'Jefe de la Comunidad',
                                            'Sub Administrador' => 'Líder de Vereda',
                                            'Sub-administrador' => 'Líder de Vereda',
                                            'Miembro' => 'Jefe Familiar',
                                            'Miembro Comun' => 'Jefe Familiar'
                                        ];
                                        $displayRole = $roleMap[$rawRole] ?? $rawRole;
                                        echo htmlspecialchars($displayRole ?: '-');
                                    ?>
                                </td>
                                <td><span class="badge bg-primary text-white"><?= htmlspecialchars($notificacion['tipo'] ?? '') ?></span></td>
                                <td><?= htmlspecialchars($notificacion['mensaje'] ?? '') ?></td>
                                <td><?= htmlspecialchars(($notificacion['destino_nombres'] ?? '') . ' ' . ($notificacion['destino_apellidos'] ?? '')) ?></td>
                                <td><?= htmlspecialchars($notificacion['fecha_creacion'] ?? '') ?></td>
                                <td><?= $is_leida ? '<span class="badge bg-success">Leída</span>' : '<span class="badge bg-danger">No Leída</span>' ?></td>
                                <td>
                                    <?php if (!$is_leida): ?>
                                        <a href="./index.php?route=admin/notifications/mark-read&id=<?php echo $notificacion['id_notificacion']; ?>" class="btn btn-sm btn-success me-1" title="Marcar como Leída"><i class="fas fa-check"></i></a>
                                    <?php endif; ?>
                                    <a href="./index.php?route=admin/notifications/delete&id=<?php echo $notificacion['id_notificacion']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar notificación?');"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>