<?php
// Vista: user/vivienda/details.php
?>
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($page_title ?? 'Detalles de mi vivienda'); ?></h2>

    <?php if (empty($vivienda)): ?>
        <p class="text-gray-700">No se encontró vivienda asignada como jefe de familia.</p>
        <a href="./index.php?route=user/dashboard" class="btn btn-secondary">Volver</a>
        <?php return; ?>
    <?php endif; ?>

    <div class="mb-4 bg-white p-4 rounded shadow">
        <h3 class="font-semibold">Información básica</h3>
        <div>Número: <?= htmlspecialchars($vivienda['numero'] ?? '') ?></div>
        <div>Calle: <?= htmlspecialchars($vivienda['calle_nombre'] ?? '') ?></div>
    </div>

    <form method="POST" action="./index.php?route=user/updateViviendaDetails" class="bg-white p-4 rounded shadow grid gap-3 max-w-md">
        <?= \CsrfHelper::getTokenInput() ?>
        <input type="hidden" name="id_vivienda" value="<?= $vivienda['id_vivienda'] ?>">

        <div>
            <label class="form-label">Habitaciones</label>
            <input type="number" name="habitaciones" value="<?= htmlspecialchars($detalle['habitaciones'] ?? '') ?>" class="form-control" min="0">
        </div>

        <div>
            <label class="form-label">Baños</label>
            <input type="number" name="banos" value="<?= htmlspecialchars($detalle['banos'] ?? '') ?>" class="form-control" min="0">
        </div>

        <div class="md:col-span-2">
            <label class="form-label">Servicios (coma-separated)</label>
            <input type="text" name="servicios" value="<?= htmlspecialchars($detalle['servicios'] ?? '') ?>" class="form-control" placeholder="Ej: agua, luz, gas">
        </div>

        <div>
            <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Guardar</button>
        </div>
    </form>

    <a href="./index.php?route=user/dashboard" class="btn btn-secondary mt-4 inline-block">Volver al Dashboard</a>
</div>