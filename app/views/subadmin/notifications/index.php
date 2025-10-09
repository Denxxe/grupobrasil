<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"></h1>
        <a href="./index.php?route=subadmin/notifications/mark-all-read" class="d-none d-sm-inline-block btn btn-sm btn-success shadow-sm">
            <i class="fas fa-check-double fa-sm text-white-50"></i> Marcar todas como leídas
        </a>
    </div>

    <?php 
    // Muestra los mensajes flash si existen
    if (isset($success_message) && $success_message): ?>
        <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
    <?php if (isset($error_message) && $error_message): ?>
        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Notificaciones Dirigidas a Ti</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Mensaje</th>
                            <th>Usuario Origen</th>
                            <th>Referencia ID</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($notificaciones)): ?>
                            <?php foreach ($notificaciones as $notificacion): ?>
                                <!-- Usamos table-warning para resaltar las no leídas -->
                                <tr class="<?php echo $notificacion['leido'] == 0 ? 'table-warning' : ''; ?>">
                                    <td><?php echo htmlspecialchars($notificacion['id_notificacion']); ?></td>
                                    <td><?php echo htmlspecialchars($notificacion['tipo']); ?></td>
                                    <td><?php echo htmlspecialchars($notificacion['mensaje']); ?></td>
                                    
                                    <!-- El usuario destino es implícito (el Subadmin actual) -->
                                    <td><?php echo htmlspecialchars($notificacion['origen_nombre'] ?? 'N/A') . ' ' . htmlspecialchars($notificacion['origen_apellido'] ?? ''); ?></td>
                                    
                                    <td><?php echo htmlspecialchars($notificacion['id_referencia'] ?? 'N/A'); ?></td>
                                    <td>
                                        <?php echo $notificacion['leido'] == 1 ? '<span class="badge badge-success">Leída</span>' : '<span class="badge badge-danger">No Leída</span>'; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($notificacion['fecha_creacion']); ?></td>
                                    <td>
                                        <?php if ($notificacion['leido'] == 0): ?>
                                            <!-- Enlace para marcar como leída, apunta a la ruta del subadmin -->
                                            <a href="./index.php?route=subadmin/notifications/mark-read&id=<?php echo $notificacion['id_notificacion']; ?>" class="btn btn-sm btn-success" title="Marcar como Leída">
                                                <i class="fas fa-check"></i>
                                            </a>
                                        <?php endif; ?>
                                        <!-- Enlace para eliminar, apunta a la ruta del subadmin -->
                                        <a href="./index.php?route=subadmin/notifications/delete&id=<?php echo $notificacion['id_notificacion']; ?>" class="btn btn-sm btn-danger" title="Eliminar" onclick="return confirm('¿Estás seguro de que quieres eliminar esta notificación?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">No tienes notificaciones para mostrar.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
