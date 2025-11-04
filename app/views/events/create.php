<?php
// Vista: app/views/events/create.php
require_once __DIR__ . '/../../helpers/CsrfHelper.php';
$editing = isset($event) && !empty($event);
?>
<div class="container mx-auto p-4">
  <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars($page_title) ?></h1>

  <form method="post" action="<?= $editing ? './index.php?route=eventos/edit/' . (int)$event['id_evento'] : './index.php?route=eventos/create' ?>">
    <?= \CsrfHelper::getTokenInput() ?>
    <div class="form-group mb-2">
      <label>Título</label>
      <input type="text" name="titulo" class="form-control" required value="<?= $editing ? htmlspecialchars($event['titulo']) : '' ?>" />
    </div>

    <div class="form-group mb-2">
      <label>Fecha</label>
      <input type="date" name="fecha" class="form-control" required value="<?= $editing ? htmlspecialchars($event['fecha']) : '' ?>" />
    </div>

    <div class="form-group mb-2">
      <label>Hora inicio</label>
      <input type="time" name="hora_inicio" class="form-control" value="<?= $editing ? htmlspecialchars($event['hora_inicio']) : '' ?>" />
    </div>

    <div class="form-group mb-2">
      <label>Hora fin</label>
      <input type="time" name="hora_fin" class="form-control" value="<?= $editing ? htmlspecialchars($event['hora_fin']) : '' ?>" />
    </div>

    <div class="form-group mb-2">
      <label>Ubicación</label>
      <input type="text" name="ubicacion" class="form-control" value="<?= $editing ? htmlspecialchars($event['ubicacion']) : '' ?>" />
    </div>

    <div class="form-group mb-2">
      <label>Categoría de edad</label>
      <select name="categoria_edad" class="form-control">
        <?php $opts = ['ninos'=>'Niños','jovenes'=>'Jóvenes','adultos'=>'Adultos','adultos_mayores'=>'Adultos mayores','todos'=>'Todos'];
        $sel = $editing ? $event['categoria_edad'] : 'todos';
        foreach ($opts as $val=>$label): ?>
          <option value="<?= $val ?>" <?= $sel === $val ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group mb-2">
      <label>Alcance</label>
      <select name="alcance" class="form-control">
        <option value="comunidad" <?= ($editing && $event['alcance']==='comunidad') ? 'selected' : '' ?>>Comunidad</option>
        <option value="vereda" <?= ($editing && $event['alcance']==='vereda') ? 'selected' : '' ?>>Vereda</option>
      </select>
    </div>

    <div class="form-group mb-2">
      <label>Id Calle (opcional)</label>
      <input type="number" name="id_calle" class="form-control" value="<?= $editing ? htmlspecialchars($event['id_calle']) : '' ?>" />
    </div>

    <div class="form-group mb-2">
      <label>Descripción</label>
      <input type="text" name="descripcion" class="form-control" value="<?= $editing ? htmlspecialchars($event['descripcion']) : '' ?>" />
    </div>

    <div class="mt-4">
      <button class="btn btn-primary" type="submit"><?= $editing ? 'Actualizar' : 'Crear' ?></button>
      <a href="./index.php?route=eventos" class="btn btn-secondary">Cancelar</a>
      <?php if ($editing): ?>
        <button formmethod="post" formaction="./index.php?route=eventos/delete" name="id_evento" value="<?= (int)$event['id_evento'] ?>" class="btn btn-danger float-right">Eliminar</button>
      <?php endif; ?>
    </div>
  </form>
</div>
