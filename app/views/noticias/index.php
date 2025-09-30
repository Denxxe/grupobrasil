<div class="container my-5">

    <?php if (!empty($noticias)): ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($noticias as $noticia): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm border-0">
                        <?php if (!empty($noticia['imagen_principal'])): ?>
                            <img src="/assets/img/noticias/<?php echo htmlspecialchars($noticia['imagen_principal']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($noticia['titulo']); ?>">
                        <?php else: ?>
                            <img src="/assets/img/noticias/default.jpg" 
                                 class="card-img-top" 
                                 alt="Imagen por defecto">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($noticia['titulo']); ?></h5>
                            <p class="card-text text-muted small mb-2">
                                <?php echo date('d/m/Y', strtotime($noticia['fecha_publicacion'])); ?>
                            </p>
                            <p class="card-text flex-grow-1">
                                <?php echo htmlspecialchars(mb_substr($noticia['contenido'], 0, 120)) . '...'; ?>
                            </p>
                           <a href="./index.php?route=noticias/show/<?php echo urlencode($noticia['id_noticia']); ?>" 
   class="btn btn-outline-primary btn-sm mt-auto">
   <i class="bi bi-eye"></i> Leer más
</a>


                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginación futura -->
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled"><a class="page-link">Anterior</a></li>
                <li class="page-item active"><a class="page-link">1</a></li>
                <li class="page-item"><a class="page-link">2</a></li>
                <li class="page-item"><a class="page-link">Siguiente</a></li>
            </ul>
        </nav>
    <?php else: ?>
        <div class="alert alert-warning text-center">
            No hay noticias disponibles en este momento.
        </div>
    <?php endif; ?>
</div>
