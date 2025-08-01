<?php
// grupobrasil/app/views/admin/news/create.php

// Asegúrate de que $page_title venga del controlador
?>

<div class="container-fluid mt-4">
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-vinotinto-700">Formulario para Crear Noticia</h6>
        </div>
        <div class="card-body">
            <form action="/grupobrasil/public/index.php?route=admin/news/store" method="POST" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label for="titulo" class="form-label">Título de la Noticia</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label for="contenido" class="form-label">Contenido</label>
                    <textarea class="form-control" id="contenido" name="contenido" rows="10" required></textarea>
                </div>

<div class="form-group">
    <label for="id_categoria">Categoría:</label>
    <select class="form-control" id="id_categoria" name="id_categoria" required>
        <option value="">Seleccione una categoría</option>
        <?php foreach ($data['categorias'] ?? [] as $categoria): ?>
            <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>"
                <?= (isset($old_data['id_categoria']) && $old_data['id_categoria'] == $categoria['id_categoria']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($categoria['nombre_categoria']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php if (isset($errors['id_categoria'])): ?>
        <div class="text-danger"><?= $errors['id_categoria'] ?></div>
    <?php endif; ?>
</div>

               <div class="mb-3">
    <label for="imagen_principal" class="form-label">Imagen Principal de la Noticia</label>
    <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" accept="image/*">
    <small class="form-text text-muted">Sube una imagen para la noticia (opcional).</small>
</div>

                <div class="d-flex justify-content-end mt-4">
                    <a href="/grupobrasil/public/index.php?route=admin/news" class="btn btn-secondary me-2">Cancelar</a>
                    <button type="submit" class="btn btn-vinotinto-600 text-white">Guardar Noticia</button>
                </div>
            </form>
        </div>
    </div>
</div>