<?php
// grupobrasil/app/views/admin/news/edit.php

// Asegúrate de que $news, $title, $page_title, $errors, $old_data, $categorias vengan del controlador (renderAdminView)
// $news: Contiene los datos actuales de la noticia que se está editando.
// $errors: Un array con mensajes de error de validación, si los hay.
// $old_data: Datos del formulario enviados previamente en caso de error, para repoblar los campos.
// $categorias: Un array de todas las categorías disponibles para el selector.

// Si hay errores, la variable $errors debería ser un array o una cadena.
// Para consistencia con 'create', aquí asumimos que $errors podría ser un array de mensajes.
$display_errors = [];
if (isset($errors) && is_array($errors)) {
    $display_errors = $errors;
} elseif (isset($errors) && is_string($errors)) {
    $display_errors[] = $errors; // Si es una cadena, conviértela en un array para el foreach
}

// Para repoblar el formulario, priorizamos old_data sobre $news en caso de errores de validación.
$news_data = !empty($old_data) ? $old_data : $news;

?>

<div class="container-fluid mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="./index.php?route=admin/news" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Volver a Noticias
        </a>
    </div>

    <?php if (!empty($display_errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error al guardar la noticia!</h4>
            <ul class="mb-0">
                <?php foreach ($display_errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-vinotinto-700">Formulario de Edición de Noticia</h6>
        </div>
        <div class="card-body">
            <form action="./index.php?route=admin/news/update" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id_noticia" value="<?php echo htmlspecialchars($news['id_noticia']); ?>">

                <div class="mb-3">
                    <label for="titulo" class="form-label">Título de la Noticia:</label>
                    <input type="text" class="form-control" id="titulo" name="titulo"
                           value="<?php echo htmlspecialchars($news_data['titulo'] ?? ''); ?>" required>
                </div>

                <div class="mb-3">
                    <label for="contenido" class="form-label">Contenido:</label>
                    <textarea class="form-control" id="contenido" name="contenido" rows="10"
                              required><?php echo htmlspecialchars($news_data['contenido'] ?? ''); ?></textarea>
                </div>

                <div class="mb-3">
                    <label for="id_categoria" class="form-label">Categoría:</label>
                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                        <option value="">Seleccione una categoría</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo htmlspecialchars($categoria['id_categoria']); ?>"
                                <?php echo (isset($news_data['id_categoria']) && $news_data['id_categoria'] == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="imagen_principal" class="form-label">Imagen Principal:</label>
                    <?php if (!empty($news_data['imagen_principal'])): ?>
                        <div class="mb-2">
                            <p>Imagen actual:</p>
                            <img src="./<?php echo htmlspecialchars($news_data['imagen_principal']); ?>"
                                 alt="Imagen actual" class="img-thumbnail" style="max-width: 200px; height: auto;">
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" id="remove_imagen_principal" name="remove_imagen_principal" value="1">
                                <label class="form-check-label" for="remove_imagen_principal">
                                    Eliminar imagen actual
                                </label>
                            </div>
                        </div>
                    <?php endif; ?>
                    <input type="file" class="form-control" id="imagen_principal" name="imagen_principal" accept="image/*">
                    <div class="form-text">Sube una nueva imagen para reemplazar la actual (opcional).</div>
                </div>

                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1"
                           <?php echo (isset($news_data['activo']) && $news_data['activo'] == 1) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="activo">Noticia Activa (Visible al público)</label>
                </div>

                <button type="submit" class="btn btn-vinotinto-600 text-white"><i class="fas fa-save"></i> Guardar Cambios</button>
            </form>
        </div>
    </div>
</div>