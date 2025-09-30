<!-- grupobrasil/app/views/noticias/show.php -->

<div class="container mx-auto p-6">
    <!-- Título -->
    <h1 class="text-3xl font-bold text-gray-800 mb-4">
        <?= htmlspecialchars($noticia['titulo']) ?>
    </h1>

    <!-- Imagen -->
    <?php if (!empty($noticia['imagen'])): ?>
        <img src="<?= htmlspecialchars($noticia['imagen']) ?>" 
             alt="Imagen de la noticia"
             class="w-full h-64 object-cover rounded-lg shadow mb-6">
    <?php endif; ?>

    <!-- Contenido -->
    <div class="text-gray-700 text-lg mb-6">
        <?= nl2br(htmlspecialchars($noticia['contenido'])) ?>
    </div>

    <!-- Botones de interacción -->
    <div class="flex items-center gap-4 mb-8">
        <!-- Likes -->
        <form method="POST" action="./index.php?route=noticias/toggle-like">
            <input type="hidden" name="id_noticia" value="<?= $noticia['id_noticia'] ?>">
            <button type="submit" 
                    class="px-4 py-2 rounded-lg shadow <?= $usuarioDioLike ? 'bg-red-500 text-white' : 'bg-gray-200 text-gray-800' ?>">
                ❤️ <?= $totalLikes ?> Me gusta
            </button>
        </form>

        <!-- Compartir -->
        <form method="POST" action="./index.php?route=noticias/share/<?= $noticia['id_noticia'] ?>">
            <button type="submit" 
                    class="px-4 py-2 rounded-lg shadow bg-blue-500 text-white">
                🔗 Compartir
            </button>
        </form>
    </div>

    <!-- Comentarios -->
    <div class="mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-4">Comentarios</h2>

        <?php if (!empty($comentarios)): ?>
            <ul class="space-y-4">
                <?php foreach ($comentarios as $comentario): ?>
                    <li class="bg-gray-100 p-4 rounded-lg shadow">
                        <p class="text-gray-800"><?= htmlspecialchars($comentario['contenido']) ?></p>
                        <small class="text-gray-500">
                            Por <?= htmlspecialchars($comentario['nombre_usuario'] ?? 'Anónimo') ?>
                            el <?= htmlspecialchars($comentario['fecha_creacion']) ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-gray-500">Aún no hay comentarios.</p>
        <?php endif; ?>
    </div>

    <!-- Agregar comentario -->
    <?php if (isset($_SESSION['id_usuario'])): ?>
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-2">Agregar comentario</h3>
            <form method="POST" action="./index.php?route=noticias/add-comment" class="space-y-3">
                <input type="hidden" name="id_noticia" value="<?= $noticia['id_noticia'] ?>">
                <textarea name="contenido" rows="3"
                          class="w-full border border-gray-300 rounded-lg p-2"
                          placeholder="Escribe tu comentario..." required></textarea>
                <button type="submit" 
                        class="px-4 py-2 bg-green-500 text-white rounded-lg shadow">
                    💬 Comentar
                </button>
            </form>
        </div>

<a href="./index.php?route=noticias" class="btn btn-secondary mt-3"><i class="bi bi-arrow-left"></i> Volver</a>

    <?php else: ?>
        <p class="text-gray-500">Debes <a href="./index.php?route=login" class="text-blue-600">iniciar sesión</a> para comentar.</p>
    <?php endif; ?>
</div>
