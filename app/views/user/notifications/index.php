<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h4"><i class="fas fa-bell"></i> Mis Notificaciones</h2>
        <?php if (!empty($notificaciones)): ?>
            <a href="./index.php?route=user/notifications/mark-all-read" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-check-double"></i> Marcar todas como leídas
            </a>
        <?php endif; ?>
    </div>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($success_message) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error_message) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($notificaciones)): ?>
        <div class="alert alert-info text-center" role="alert">No tienes notificaciones por el momento.</div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th>Origen</th>
                        <th>Rol</th>
                        <th>Tipo</th>
                        <th>Mensaje</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notificaciones as $notificacion): 
                        $is_leida = (int)$notificacion['leido'] === 1;
                    ?>
                        <tr class="<?= $is_leida ? '' : 'table-warning' ?>">
                            <td><?= $notificacion['id_notificacion'] ?></td>
                            <td><?= htmlspecialchars(($notificacion['origen_nombres'] ?? '') . ' ' . ($notificacion['origen_apellidos'] ?? '')) ?></td>
                            <td>
                                <?php
                                    $rawRole = $notificacion['origen_rol'] ?? '';
                                    $roleMap = [
                                        'Administrador' => 'Jefe de la Comunidad',
                                        'Sub Administrador' => 'Líder de Vereda',
                                        'Sub-administrador' => 'Líder de Vereda',
                                        'Miembro' => 'Jefe Familiar'
                                    ];
                                    $displayRole = $roleMap[$rawRole] ?? $rawRole;
                                    echo htmlspecialchars($displayRole ?: '-');
                                ?>
                            </td>
                            <td><span class="badge bg-primary text-white"><?= htmlspecialchars($notificacion['tipo'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars($notificacion['mensaje']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($notificacion['fecha_creacion'])) ?></td>
                            <td><?= $is_leida ? '<span class="badge bg-success">Leída</span>' : '<span class="badge bg-danger">No Leída</span>' ?></td>
                            <td>
                                <?php if (!$is_leida): ?>
                                    <a href="./index.php?route=user/notifications/mark-read&id=<?php echo $notificacion['id_notificacion']; ?>" class="btn btn-sm btn-success me-1" title="Marcar como Leída"><i class="fas fa-check"></i></a>
                                <?php endif; ?>
                                <a href="./index.php?route=user/notifications/delete&id=<?php echo $notificacion['id_notificacion']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Eliminar notificación?');"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
