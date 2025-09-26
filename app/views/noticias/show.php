<div class="container my-5">
    <?php if ($noticia): ?>
        <div class="card shadow-sm border-0">
            <?php if (!empty($noticia['imagen_principal'])): ?>
                <img src="/assets/img/noticias/<?php echo htmlspecialchars($noticia['imagen_principal']); ?>" 
                     class="card-img-top" 
                     alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
            <?php else: ?>
                <img src="/assets/img/noticias/default.jpg" 
                     class="card-img-top" 
                     alt="Imagen por defecto">
            <?php endif; ?>
            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h1>
                <p class="text-muted">
                    <i class="bi bi-calendar-event"></i> <?php echo date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])); ?>
                </p>
                <hr>
                <p class="lead"><?php echo nl2br(htmlspecialchars($noticia['contenido'])); ?></p>

                <!-- Likes -->
                <div class="d-flex align-items-center mb-3">
                    <form action="/noticias/toggle-like" method="POST" class="me-2">
                        <input type="hidden" name="id_noticia" value="<?php echo htmlspecialchars($noticia['id_noticia']); ?>">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button type="submit" class="btn <?php echo $usuarioDioLike ? 'btn-danger' : 'btn-outline-primary'; ?>">
                                <i class="bi bi-heart<?php echo $usuarioDioLike ? '-fill' : ''; ?>"></i> 
                                <?php echo $usuarioDioLike ? ' Quitar Me Gusta' : ' Me Gusta'; ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" disabled>
                                <i class="bi bi-heart"></i> Inicia sesión para dar Like
                            </button>
                        <?php endif; ?>
                    </form>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($totalLikes); ?> Likes</span>
                </div>

                <!-- Compartir -->
                <h5>Compartir esta noticia:</h5>
                <div class="btn-group mb-4" role="group">
                    <a href="whatsapp://send?text=<?php echo $share_text . $noticia_url; ?>" 
                       class="btn btn-success" data-bs-toggle="tooltip" title="Compartir en WhatsApp">
                        <i class="bi bi-whatsapp"></i>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $noticia_url; ?>&quote=<?php echo $noticia_titulo; ?>" 
                       class="btn btn-primary" data-bs-toggle="tooltip" title="Compartir en Facebook">
                        <i class="bi bi-facebook"></i>
                    </a>
                    <a href="https://t.me/share/url?url=<?php echo $noticia_url; ?>&text=<?php echo $share_text; ?>" 
                       class="btn btn-info" data-bs-toggle="tooltip" title="Compartir en Telegram">
                        <i class="bi bi-telegram"></i>
                    </a>
                    <a href="mailto:?subject=<?php echo $noticia_titulo; ?>&body=<?php echo $share_text . $noticia_url; ?>" 
                       class="btn btn-warning" data-bs-toggle="tooltip" title="Compartir por Email">
                        <i class="bi bi-envelope"></i>
                    </a>
                </div>

                <hr>

                <!-- Comentarios -->
                <h3 class="mt-4">Comentarios (<?php echo count($comentarios); ?>)</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="/noticias/add-comment" method="POST" class="mb-4">
                        <input type="hidden" name="id_noticia" value="<?php echo htmlspecialchars($noticia['id_noticia']); ?>">
                        <textarea class="form-control mb-2" name="contenido" rows="3" placeholder="Escribe un comentario..." required></textarea>
                        <button type="submit" class="btn btn-success">Publicar</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">Inicia sesión para comentar.</div>
                <?php endif; ?>

                <?php if (!empty($comentarios)): ?>
                    <ul class="list-group">
                        <?php foreach ($comentarios as $comentario): ?>
                            <li class="list-group-item">
                                <div class="d-flex align-items-center">
                                    <img src="/assets/img/perfiles/<?php echo $comentario['foto_perfil'] ?? 'default_user.png'; ?>" 
                                         class="rounded-circle me-2" width="40" height="40">
                                    <div>
                                        <strong><?php echo htmlspecialchars($comentario['usuario_nombre']); ?></strong>
                                        <small class="text-muted"> · <?php echo date('d/m/Y H:i', strtotime($comentario['fecha_comentario'])); ?></small>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?></p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted">No hay comentarios aún. Sé el primero en opinar.</p>
                <?php endif; ?>

            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">La noticia que buscas no existe o fue eliminada.</div>
    <?php endif; ?>

    <a href="/noticias" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver</a>
</div>
