
<div class="container my-4">
    <h1 class="mb-4">Últimas Noticias</h1>

    <?php if (!empty($noticias)): ?>
        <div class="row">
            <?php foreach ($noticias as $noticia): ?>
                <div class="col-md-4 mb-4">
                    <div class="card h-100">
                        <?php if (!empty($noticia['imagen_principal'])): ?>
                            <img src="/assets/img/noticias/<?php echo htmlspecialchars($noticia['imagen_principal']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                        <?php else: ?>
                            <img src="/assets/img/noticias/default.jpg" class="card-img-top" alt="Imagen por defecto">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h5>
                            <p class="card-text">
                                <?php 
                                // Mostrar un fragmento del contenido
                                echo htmlspecialchars(mb_substr($noticia['contenido'], 0, 150)) . '...'; 
                                ?>
                            </p>
                            <p class="card-text mt-auto"><small class="text-muted">Publicado el: <?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?></small></p>
                            <a href="/noticias/show/<?php echo htmlspecialchars($noticia['id_noticia']); ?>" class="btn btn-primary mt-2">Leer más</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p>No hay noticias disponibles en este momento.</p>
    <?php endif; ?>
</div>

