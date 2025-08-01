<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">Administración de Noticias</h1>
        <a href="/grupobrasil/public/index.php?route=admin/news/create" class="btn btn-vinotinto-600 text-white">
            <i class="fas fa-plus"></i> Crear Nueva Noticia
        </a>
    </div>

    <?php if (empty($noticias)): ?>
        <div class="alert alert-info text-center" role="alert">
            No hay noticias disponibles. ¡Crea la primera!
        </div>
    <?php else: ?>
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-vinotinto-700">Listado de Noticias</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="newsTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Contenido</th>
                                <th>Imagen</th>
                                <th>Fecha Publicación</th>
                                <th>Publicado por</th>
                                <th>Activo</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($noticias as $noticia): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($noticia['id_noticia']); ?></td>
                                    <td><?php echo htmlspecialchars($noticia['titulo']); ?></td>
                                    <td>
                                        <?php
                                        $excerpt = strip_tags($noticia['contenido']);
                                        echo htmlspecialchars(mb_strimwidth($excerpt, 0, 100, '...'));
                                        ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($noticia['imagen_principal'])): ?>
                                            <img src="/grupobrasil/public/<?php echo htmlspecialchars($noticia['imagen_principal']); ?>" alt="Imagen de noticia" class="img-thumbnail" style="width: 80px; height: 80px; object-fit: cover;">
                                        <?php else: ?>
                                            Sin imagen
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($noticia['fecha_publicacion']))); ?></td>
                                    <td>
                                        ID: <?php echo htmlspecialchars($noticia['id_usuario_publicador']); ?>
                                    </td>
                                    <td>
                                        <?php
                                            echo ($noticia['activo'] == 1) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-danger">No</span>';
                                        ?>
                                    </td>
                                    <td>
                                        <a href="/grupobrasil/public/index.php?route=admin/news/edit&id=<?php echo htmlspecialchars($noticia['id_noticia']); ?>" class="btn btn-warning btn-sm m-1">
                                            <i class="fas fa-edit"></i> Editar
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm m-1 delete-news-btn"
                                                data-bs-toggle="modal" data-bs-target="#deleteConfirmationModal"
                                                data-id="<?php echo htmlspecialchars($noticia['id_noticia']); ?>"
                                                data-title="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ¿Está seguro de que desea eliminar la noticia "<strong id="newsTitleToDelete"></strong>"? Esta acción es irreversible.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="confirmDeleteButton" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Eliminar
                </a>
            </div>
        </div>
    </div>
</div>