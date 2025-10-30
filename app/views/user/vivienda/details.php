<?php
// Vista: user/vivienda/details.php
?>
<div class="container mx-auto p-6">
    <header class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($page_title ?? 'Detalles de mi vivienda'); ?></h1>
        <p class="text-sm text-gray-500 mt-1">Ficha de la vivienda asignada a tu núcleo familiar. Aquí puedes ver y actualizar atributos básicos.</p>
    </header>

    <?php if (empty($vivienda)): ?>
        <p class="text-gray-700">No se encontró vivienda asignada como jefe de familia.</p>
        <a href="./index.php?route=user/dashboard" class="btn btn-secondary">Volver</a>
        <?php return; ?>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <aside class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-medium text-gray-800 mb-3">Información básica</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-gray-700">
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">Número</div>
                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($vivienda['numero'] ?? '') ?></div>
                </div>
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">Tipo</div>
                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($vivienda['tipo'] ?? '') ?></div>
                </div>
                <div class="space-y-1">
                    <div class="text-sm text-gray-500">Estado</div>
                    <div class="font-semibold text-gray-900"><?= htmlspecialchars($vivienda['estado'] ?? '') ?></div>
                </div>
            </div>

            <div class="mt-4">
                <div class="text-sm text-gray-500">Calle / Sector</div>
                <div class="font-medium text-gray-900 mt-1"><?= htmlspecialchars(trim((string)($vivienda['calle_nombre'] ?? '') . ' ' . ($vivienda['sector'] ?? ''))) ?></div>
            </div>

            <?php if (!empty($detalle['servicios'])): ?>
                <?php $svc = array_filter(array_map('trim', explode(',', $detalle['servicios']))); ?>
                <?php if (!empty($svc)): ?>
                    <div class="mt-6">
                        <div class="text-sm text-gray-500">Servicios</div>
                        <div class="flex flex-wrap gap-2 mt-2">
                            <?php foreach ($svc as $s): ?>
                                <span class="px-3 py-1 bg-gray-100 text-sm text-gray-700 rounded-full border"><?= htmlspecialchars($s) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="mt-6">
                <h4 class="text-md font-medium text-gray-800">Miembros asignados</h4>
                <?php if (empty($residents)): ?>
                    <p class="text-gray-600 mt-2">No hay habitantes asignados a esta vivienda.</p>
                <?php else: ?>
                    <ul class="mt-3 space-y-3">
                        <?php foreach ($residents as $r): ?>
                            <li class="flex items-center justify-between p-3 bg-white border rounded-md">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center text-sm font-semibold text-gray-700"><?= htmlspecialchars($r['nombres'][0] ?? '') ?></div>
                                    <div>
                                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($r['nombre_completo'] ?? ($r['nombres'] . ' ' . $r['apellidos'])) ?></div>
                                        <div class="text-sm text-gray-500">Cédula: <?= htmlspecialchars($r['cedula'] ?? '') ?> · Tel: <?= htmlspecialchars($r['telefono'] ?? '') ?></div>
                                    </div>
                                </div>
                                <div>
                                    <?php if (!empty($r['es_jefe_familia'])): ?>
                                        <span class="inline-block px-3 py-1 text-xs bg-blue-50 text-blue-700 rounded-full">Jefe</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </aside>

        <section class="lg:col-span-1 bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Editar detalles</h3>
            <form method="POST" action="./index.php?route=user/updateViviendaDetails" class="space-y-4">
                <?= \CsrfHelper::getTokenInput() ?>
                <input type="hidden" name="id_vivienda" value="<?= htmlspecialchars($vivienda['id_vivienda'] ?? '') ?>">

                <div>
                    <label class="block text-sm text-gray-600">Habitaciones</label>
                    <input type="number" name="habitaciones" id="habitaciones" value="<?= htmlspecialchars($detalle['habitaciones'] ?? '') ?>" class="form-control mt-1" min="0" max="99">
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Baños</label>
                    <input type="number" name="banos" id="banos" value="<?= htmlspecialchars($detalle['banos'] ?? '') ?>" class="form-control mt-1" min="0" max="99">
                </div>

                <div>
                    <label class="block text-sm text-gray-600">Servicios</label>
                    <input type="text" name="servicios" id="servicios_input" maxlength="70" value="<?= htmlspecialchars($detalle['servicios'] ?? '') ?>" class="form-control mt-1" placeholder="Ej: agua, luz, gas">
                    <p class="text-xs text-gray-500 mt-1">Separados por comas. Se guardarán sin duplicados. Máx 70 chars. No se permiten números.</p>
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full px-4 py-2 bg-indigo-600 text-white rounded-md font-medium">Guardar cambios</button>
                </div>
            </form>
        </section>
    </div>

    <div class="mt-6 flex justify-between items-center">
        <a href="./index.php?route=user/dashboard" class="btn btn-secondary inline-block">Volver al Dashboard</a>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const hab = document.getElementById('habitaciones');
    const ban = document.getElementById('banos');
    const svc = document.getElementById('servicios_input');

    function clampNumberInput(el){
        if (!el) return;
        el.addEventListener('input', function(){
            let v = el.value;
            // remove non-digits
            v = v.replace(/[^0-9]/g,'');
            if (v.length > 2) v = v.slice(0,2);
            el.value = v;
        });
    }

    if (hab) clampNumberInput(hab);
    if (ban) clampNumberInput(ban);

    if (svc) {
        svc.addEventListener('input', function(){
            // remove digits
            let v = svc.value;
            v = v.replace(/\d/g,'');
            if (v.length > 70) v = v.slice(0,70);
            svc.value = v;
        });
    }
});
</script>