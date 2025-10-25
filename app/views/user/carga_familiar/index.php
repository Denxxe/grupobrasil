<?php
// Vista: user/carga_familiar/index.php
?>
<div class="container mx-auto p-6">
    <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($page_title ?? 'Mi Carga Familiar'); ?></h2>

    <div class="mb-6">
        <h3 class="font-semibold">Miembros actuales</h3>
        <?php if (!empty($carga_familiar) && is_array($carga_familiar)): ?>
            <ul class="space-y-2 mt-3">
                <?php foreach ($carga_familiar as $m): ?>
                    <li class="p-3 bg-gray-100 rounded flex justify-between items-center">
                        <div>
                            <div class="font-semibold"><?= htmlspecialchars($m['nombre_completo'] ?? ($m['nombres'] . ' ' . $m['apellidos'] ?? '')) ?></div>
                            <div class="text-sm text-gray-600">Parentesco: <?= htmlspecialchars($m['parentesco'] ?? '') ?></div>
                        </div>
                        <form method="POST" action="./index.php?route=user/deleteMember" onsubmit="return confirm('Eliminar miembro?')">
                            <?= \CsrfHelper::getTokenInput() ?>
                            <input type="hidden" name="id_carga" value="<?= $m['id_carga'] ?>">
                            <button type="submit" class="px-3 py-1 bg-red-500 text-white rounded">Eliminar</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="text-gray-600">No tienes miembros registrados.</p>
        <?php endif; ?>
    </div>

    <div class="mb-6">
        <h3 class="font-semibold">Agregar miembro existente</h3>
        <form method="POST" action="./index.php?route=user/addMember" class="flex gap-2 items-end">
            <?= \CsrfHelper::getTokenInput() ?>
            <div>
                <label class="form-label">ID Habitante existente</label>
                <input type="number" name="existing_habitante_id" class="form-control" placeholder="ID habitante">
            </div>
            <div>
                <label class="form-label">Parentesco</label>
                <input type="text" name="parentesco" class="form-control" placeholder="Ej: Hijo, Cónyuge">
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Agregar</button>
            </div>
        </form>
    </div>

    <div class="mb-6">
        <h3 class="font-semibold">Crear y agregar nuevo miembro</h3>
        <form method="POST" action="./index.php?route=user/addMember" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <?= \CsrfHelper::getTokenInput() ?>
            <div>
                <label class="form-label">Cédula</label>
                <input type="text" name="cedula" class="form-control">
            </div>
            <div>
                <label class="form-label">Nombres</label>
                <input type="text" name="nombres" class="form-control" required>
            </div>
            <div>
                <label class="form-label">Apellidos</label>
                <input type="text" name="apellidos" class="form-control" required>
            </div>
            <div class="md:col-span-2">
                <label class="form-label">Parentesco</label>
                <input type="text" name="parentesco" class="form-control" placeholder="Ej: Hijo, Cónyuge">
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Crear y agregar</button>
            </div>
        </form>
    </div>

    <a href="./index.php?route=user/dashboard" class="btn btn-secondary">Volver al Dashboard</a>
</div>
