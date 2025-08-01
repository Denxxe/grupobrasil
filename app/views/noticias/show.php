<?php 
include __DIR__ . '/../partials/header.php'; 
?>

<div class="container my-4">
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <?php if ($noticia): ?>
        <div class="card mb-4">
            <?php if (!empty($noticia['imagen_principal'])): ?>
                <img src="/assets/img/noticias/<?php echo htmlspecialchars($noticia['imagen_principal']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
            <?php else: ?>
                <img src="/assets/img/noticias/default.jpg" class="card-img-top" alt="Imagen por defecto">
            <?php endif; ?>
            <div class="card-body">
                <h1 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h1>
                <p class="card-subtitle mb-2 text-muted">Publicado el: <?php echo date('d/m/Y H:i', strtotime($noticia['fecha_publicacion'])); ?> por <?php 
                    // Aquí podrías mostrar el nombre del publicador si lo recuperas
                    // Por ahora, solo el ID
                    echo htmlspecialchars($noticia['id_usuario_publicador']); 
                ?></p>
                <hr>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($noticia['contenido'])); ?></p>

                <div class="d-flex align-items-center mb-3">
                    <form action="/noticias/toggle-like" method="POST" class="me-2">
                        <input type="hidden" name="id_noticia" value="<?php echo htmlspecialchars($noticia['id_noticia']); ?>">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button type="submit" class="btn <?php echo $usuarioDioLike ? 'btn-danger' : 'btn-primary'; ?>">
                                <i class="bi bi-heart<?php echo $usuarioDioLike ? '-fill' : ''; ?>"></i> 
                                <?php echo $usuarioDioLike ? 'Ya no me gusta' : 'Me Gusta'; ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary" disabled>
                                <i class="bi bi-heart"></i> Me Gusta (Inicia sesión)
                            </button>
                        <?php endif; ?>
                    </form>
                    <span class="badge bg-secondary"><?php echo htmlspecialchars($totalLikes); ?> Likes</span>
                </div>

                <div class="mb-3">
                    <h5>Compartir:</h5>
                    <?php 
                        $noticia_url = urlencode("http://" . $_SERVER['HTTP_HOST'] . "/noticias/show/" . $noticia['id_noticia']);
                        $noticia_titulo = urlencode($noticia['titulo']);
                        $share_text = urlencode("Mira esta noticia: " . $noticia['titulo'] . " - ");
                    ?>
                    <a href="whatsapp://send?text=<?php echo $share_text . $noticia_url; ?>" class="btn btn-success me-2" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $noticia_url; ?>&quote=<?php echo $noticia_titulo; ?>" class="btn btn-primary me-2" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                    <a href="https://t.me/share/url?url=<?php echo $noticia_url; ?>&text=<?php echo $share_text; ?>" class="btn btn-info me-2" target="_blank" rel="noopener noreferrer">
                        <i class="bi bi-telegram"></i> Telegram
                    </a>
                    <a href="mailto:?subject=<?php echo $noticia_titulo; ?>&body=<?php echo $share_text . $noticia_url; ?>" class="btn btn-warning">
                        <i class="bi bi-envelope"></i> Email
                    </a>
                </div>

                <hr>

                <h3 class="mt-4">Comentarios (<?php echo count($comentarios); ?>)</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="/noticias/add-comment" method="POST" class="mb-4">
                        <input type="hidden" name="id_noticia" value="<?php echo htmlspecialchars($noticia['id_noticia']); ?>">
                        <div class="mb-3">
                            <label for="contenido" class="form-label">Tu comentario:</label>
                            <textarea class="form-control" id="contenido" name="contenido" rows="3" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-success">Publicar Comentario</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info" role="alert">
                        Inicia sesión para dejar un comentario. <a href="/login" class="alert-link">Ir a Iniciar Sesión</a>
                    </div>
                <?php endif; ?>

                <?php if (!empty($comentarios)): ?>
                    <div class="list-group">
                        <?php foreach ($comentarios as $comentario): ?>
                            <div class="list-group-item list-group-item-action flex-column align-items-start mb-2">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1">
                                        <?php if (!empty($comentario['foto_perfil'])): ?>
                                            <img src="/assets/img/perfiles/<?php echo htmlspecialchars($comentario['foto_perfil']); ?>" class="rounded-circle me-2" alt="Foto de perfil" width="30" height="30">
                                        <?php else: ?>
                                            <img src="/assets/img/perfiles/default_user.png" class="rounded-circle me-2" alt="Foto de perfil por defecto" width="30" height="30">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($comentario['usuario_nombre'] . ' ' . $comentario['usuario_apellido']); ?>
                                    </h5>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($comentario['fecha_comentario'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($comentario['contenido'])); ?></p>
                                </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p>Sé el primero en comentar esta noticia.</p>
                <?php endif; ?>

            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-warning" role="alert">
            La noticia que buscas no existe o ha sido eliminada.
        </div>
    <?php endif; ?>

    <a href="/noticias" class="btn btn-secondary mt-3">Volver al listado de noticias</a>
</div>

<?php 
include __DIR__ . '/../partials/footer.php'; 
?>