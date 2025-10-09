<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-bell"></i></h2>
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
        <div class="alert alert-info text-center" role="alert">
            No tienes notificaciones por el momento.
        </div>
    <?php else: ?>
        <div class="list-group">
            <?php foreach ($notificaciones as $notificacion): 
                // Clase CSS basada en el estado de lectura
                $is_leida = $notificacion['leido'];
                $clase_leido = $is_leida ? 'list-group-item-light' : 'list-group-item-warning'; 
            ?>
                <div class="list-group-item <?= $clase_leido ?> mb-2 shadow-sm border rounded">
                    <div class="d-flex w-100 justify-content-between">
                        <div class="flex-grow-1">
                            <h5 class="mb-1">
                                <?= $is_leida ? '<i class="far fa-envelope-open me-2"></i>' : '<i class="fas fa-envelope me-2"></i>' ?>
                                <?= htmlspecialchars($notificacion['mensaje']) ?>
                            </h5>
                            <small class="text-muted">
                                <?php if (!empty($notificacion['origen_nombre'])): ?>
                                    De: <?= htmlspecialchars($notificacion['origen_nombre'] . ' ' . $notificacion['origen_apellido']) ?>
                                <?php endif; ?>
                                <span class="ms-3">Fecha: <?= date('d/m/Y H:i', strtotime($notificacion['fecha_creacion'])) ?></span>
                            </small>
                        </div>

                        <div class="ms-3 d-flex align-items-center">
                            <?php if (!$is_leida): ?>
                                <a href="./index.php?route=user/notifications/mark-read/<?= $notificacion['id_notificacion'] ?>" class="btn btn-sm btn-success me-2" title="Marcar como Leída">
                                    <i class="fas fa-check"></i>
                                </a>
                            <?php endif; ?>
                            
                            <a href="./index.php?route=user/notifications/delete/<?= $notificacion['id_notificacion'] ?>" 
                               class="btn btn-sm btn-danger" 
                               title="Eliminar Notificación"
                               onclick="return confirm('¿Estás seguro de que quieres eliminar esta notificación? Esta acción es irreversible.');">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
