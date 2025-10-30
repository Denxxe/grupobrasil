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
            <input type="hidden" name="existing_habitante_id" id="existing_habitante_id">
            <div style="flex:1">
                <label class="form-label">Buscar por cédula o nombre</label>
                <input type="text" id="buscar_habitante" maxlength="20" class="form-control" placeholder="Escribe cédula o nombre" autocomplete="off">
                <div id="suggestions" class="mt-1 bg-white border rounded shadow-sm" style="display:none; max-height:240px; overflow:auto;"></div>
            </div>
            <div>
                <label class="form-label">Parentesco</label>
                <input type="text" name="parentesco" id="parentesco_input" maxlength="20" class="form-control" placeholder="Ej: Hijo, Cónyuge">
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded">Agregar</button>
            </div>
        </form>
    </div>

    <!-- Se removió la opción de crear y agregar nuevos habitantes desde aquí. Solo se permite agregar habitantes existentes -->

    <a href="./index.php?route=user/dashboard" class="btn btn-secondary">Volver al Dashboard</a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('buscar_habitante');
    const suggestions = document.getElementById('suggestions');
    const hiddenId = document.getElementById('existing_habitante_id');
    const parentescoInput = document.getElementById('parentesco_input');
    let timer = null;

    function renderSuggestions(items){
        if (!items || items.length === 0) { suggestions.style.display='none'; suggestions.innerHTML=''; return; }
        suggestions.innerHTML = items.map(it => {
            const name = (it.nombre_completo || (it.nombres + ' ' + it.apellidos));
            const ced = it.cedula ? (' - ' + it.cedula) : '';
            return `<div class="px-3 py-2 hover:bg-gray-100 cursor-pointer" data-id="${it.id_habitante}" data-name="${name}">${name}${ced}</div>`;
        }).join('');
        suggestions.style.display = 'block';
    }

    suggestions.addEventListener('click', function(ev){
        const target = ev.target.closest('[data-id]');
        if (!target) return;
        const id = target.getAttribute('data-id');
        const name = target.getAttribute('data-name');
        hiddenId.value = id;
        input.value = name;
        suggestions.style.display = 'none';
    });

    // Validar parentesco: no números y maxlength enforce via attribute
    if (parentescoInput) {
        parentescoInput.addEventListener('input', function(){
            let v = parentescoInput.value;
            v = v.replace(/\d/g,'');
            if (v.length > 20) v = v.slice(0,20);
            parentescoInput.value = v;
        });
    }

    input.addEventListener('input', function(){
        hiddenId.value = '';
        const q = input.value.trim();
        if (timer) clearTimeout(timer);
        if (q.length < 2) { suggestions.style.display='none'; return; }
        timer = setTimeout(function(){
            fetch(`./index.php?route=user/searchHabitante&q=${encodeURIComponent(q)}`)
                .then(res => res.json())
                .then(json => {
                    if (json && json.success) renderSuggestions(json.data || []);
                    else renderSuggestions([]);
                }).catch(err => { console.error(err); renderSuggestions([]); });
        }, 300);
    });

    // Validar formulario antes de enviar
    const form = document.querySelector('form[action="./index.php?route=user/addMember"]');
    if (form) {
        form.addEventListener('submit', function(e){
            const parentVal = (parentescoInput && parentescoInput.value.trim()) || '';
            if (!hiddenId.value) {
                alert('Selecciona primero un habitante de la lista de sugerencias.');
                e.preventDefault();
                return false;
            }
            if (parentVal.length > 20) {
                alert('El parentesco no puede exceder 20 caracteres.');
                e.preventDefault();
                return false;
            }
            if (/\d/.test(parentVal)) {
                alert('El parentesco no puede contener números.');
                e.preventDefault();
                return false;
            }
            return true;
        });
    }

    document.addEventListener('click', function(e){ if (!suggestions.contains(e.target) && e.target !== input) suggestions.style.display='none'; });
});
</script>
