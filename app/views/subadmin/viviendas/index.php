<!-- grupobrasil/app/views/subadmin/viviendas/index.php -->

<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><?php echo htmlspecialchars($page_title ?? 'Viviendas de Mis Veredas'); ?></h3>
        <button id="btnNewVivienda" class="btn btn-primary">Registrar Vivienda</button>
    </div>

    <div id="viviendaFormContainer" class="card p-3 mb-4 d-none">
        <form id="viviendaForm">
            <input type="hidden" name="id_vivienda" id="id_vivienda" value="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Vereda</label>
                    <select id="id_calle" name="id_calle" class="form-select">
                        <option value="">-- Seleccionar Vereda --</option>
                        <?php if (!empty($calles_asignadas) && is_array($calles_asignadas)): ?>
                            <?php foreach ($calles_asignadas as $cal): ?>
                                <option value="<?= htmlspecialchars($cal['id_calle']) ?>"><?= htmlspecialchars($cal['nombre'] ?? $cal['nombre_calle'] ?? '') ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número</label>
                    <input type="text" id="numero" name="numero" class="form-control" required maxlength="3" inputmode="numeric" pattern="\d{1,3}" title="Sólo números, máximo 3 dígitos">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="Casa">Casa</option>
                        <option value="Local">Local</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Estado</label>
                    <select id="estado" name="estado" class="form-select">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                        <option value="En Construcción">En Construcción</option>
                    </select>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" id="btnCancelVivienda" class="btn btn-secondary">Cancelar</button>
                </div>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 mb-4">
                <h5>Mis Veredas</h5>
                <ul class="list-group" id="callesList">
                    <?php if (!empty($calles_asignadas) && is_array($calles_asignadas)): ?>
                        <?php foreach ($calles_asignadas as $cal): ?>
                            <li class="list-group-item calle-item" data-id="<?= htmlspecialchars($cal['id_calle']) ?>"><?= htmlspecialchars($cal['nombre'] ?? $cal['nombre_calle'] ?? '') ?></li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No tienes veredas asignadas.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3 mb-4">
                <h5 id="selectedCalleTitle">Selecciona una vereda para ver sus viviendas</h5>
                <div class="table-responsive">
                    <table class="table table-striped" id="viviendasTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Número</th>
                                <th>Tipo</th>
                                <th>Estado</th>
                                <th># Familias</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="viviendasBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formContainer = document.getElementById('viviendaFormContainer');
    const btnNew = document.getElementById('btnNewVivienda');
    const btnCancel = document.getElementById('btnCancelVivienda');
    const form = document.getElementById('viviendaForm');
    const body = document.getElementById('viviendasBody');

    const baseUrl = './index.php?route=subadmin/viviendas';

    function showForm(data = null) {
        form.reset();
        if (data) {
            document.getElementById('id_vivienda').value = data.id_vivienda || '';
            document.getElementById('id_calle').value = data.id_calle || '';
            document.getElementById('numero').value = data.numero || '';
            document.getElementById('tipo').value = data.tipo || '';
            document.getElementById('estado').value = data.estado || 'Activo';
        } else {
            document.getElementById('id_vivienda').value = '';
        }
        formContainer.classList.remove('d-none');
        window.scrollTo({ top: formContainer.offsetTop - 20, behavior: 'smooth' });
    }
    function hideForm() { formContainer.classList.add('d-none'); }

    btnNew.addEventListener('click', function(){ showForm(null); });
    btnCancel.addEventListener('click', function(){ hideForm(); });

    async function loadViviendasByCalle(idCalle, nombreCalle) {
        body.innerHTML = '<tr><td colspan="6">Cargando viviendas...</td></tr>';
        document.getElementById('selectedCalleTitle').textContent = 'Viviendas en: ' + nombreCalle;
        try {
            const res = await fetch(baseUrl + '&action=byCalle&id=' + encodeURIComponent(idCalle), { credentials: 'same-origin' });
            if (!res.ok) { body.innerHTML = '<tr><td colspan="6">Error al cargar viviendas</td></tr>'; return; }
            const data = await res.json();
            if (!Array.isArray(data) || data.length === 0) { body.innerHTML = '<tr><td colspan="6">No hay viviendas registradas en esta vereda.</td></tr>'; return; }
            body.innerHTML = '';
            data.forEach(v => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${v.id_vivienda ?? ''}</td>
                    <td>${escapeHtml(v.numero ?? '')}</td>
                    <td>${escapeHtml(v.tipo ?? '')}</td>
                    <td>${escapeHtml(v.estado ?? '')}</td>
                    <td>${escapeHtml(v.total_familias ?? 0)}</td>
                    <td>
                        <button class="btn btn-sm btn-secondary btn-edit" data-id="${v.id_vivienda}">Editar</button>
                        <button class="btn btn-sm btn-primary btn-details" data-id="${v.id_vivienda}">Ver detalles</button>
                        <button class="btn btn-sm btn-danger btn-delete" data-id="${v.id_vivienda}">Eliminar</button>
                    </td>`;
                body.appendChild(tr);
            });

            document.querySelectorAll('.btn-edit').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                try {
                    const r = await fetch(baseUrl + '&action=show&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
                    if (!r.ok) throw new Error('Error cargando vivienda');
                    const data = await r.json();
                    showForm({ id_vivienda: data.id_vivienda || data.id || id, id_calle: data.id_calle || '', numero: data.numero || '', tipo: data.tipo || '', estado: data.estado || 'Activo' });
                } catch (err) { console.error(err); alert('Error al cargar datos'); }
            }));

            document.querySelectorAll('.btn-details').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                try {
                    const r = await fetch(baseUrl + '&action=familiasPorVivienda&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
                    const payload = await r.json();
                    // Mostrar modal simple con detalle
                    let html = '';
                    if (!Array.isArray(payload) || payload.length === 0) html = '<p>No se encontraron familias en esta vivienda.</p>'; else {
                        payload.forEach(f => {
                            html += `<div class="card mb-2"><div class="card-body"><h6>Jefe ID: ${f.id_jefe}</h6>`;
                            if (Array.isArray(f.miembros) && f.miembros.length>0) {
                                html += '<ul>';
                                f.miembros.forEach(m => { html += `<li>${escapeHtml(m.nombres || '')} ${escapeHtml(m.apellidos || '')} - ${escapeHtml(m.parentesco || '')}</li>`; });
                                html += '</ul>';
                            }
                            html += '</div></div>';
                        });
                    }
                    alert(html.replace(/<[^>]+>/g, '\n'));
                } catch (err) { console.error(err); alert('Error al cargar detalles'); }
            }));

            document.querySelectorAll('.btn-delete').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                if (!confirm('¿Seguro que deseas eliminar esta vivienda? Esto solo eliminará la vivienda; las cargas familiares asociadas permanecerán esperando reasignación.')) return;
                try {
                    const r = await fetch(baseUrl + '&action=destroy&id=' + encodeURIComponent(id), { method: 'POST', credentials: 'same-origin' });
                    const res = await r.json();
                    if (r.ok) { alert(res.message || 'Vivienda eliminada'); loadViviendasByCalle(window.currentCalleId, window.currentCalleName); }
                    else { alert(res.error || 'Error al eliminar'); }
                } catch (err) { console.error(err); alert('Error en la petición'); }
            }));

        } catch (err) { console.error(err); body.innerHTML = '<tr><td colspan="6">Error al cargar viviendas.</td></tr>'; }
    }

    form.addEventListener('submit', async function(e){
        e.preventDefault();
        const id = document.getElementById('id_vivienda').value;
        // Validación cliente: número solo dígitos y máximo 3
        const numeroVal = document.getElementById('numero').value.trim();
        if (!/^\d{1,3}$/.test(numeroVal)) { alert('El número debe ser numérico y tener máximo 3 dígitos'); return; }

        const payload = { numero: numeroVal, tipo: document.getElementById('tipo').value, estado: document.getElementById('estado').value };
        const idCalle = document.getElementById('id_calle').value;
        if (idCalle) payload.id_calle = parseInt(idCalle);
        try {
            const url = id ? (baseUrl + '&action=update&id=' + encodeURIComponent(id)) : (baseUrl + '&action=store');
            const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, credentials: 'same-origin', body: JSON.stringify(payload) });
            const r = await res.json();
            if (res.ok && (r.message || r.id_vivienda)) { alert(r.message || 'Guardado'); hideForm(); loadViviendasByCalle(window.currentCalleId, window.currentCalleName); }
            else { alert(r.error || 'Error al guardar'); }
        } catch (err) { console.error(err); alert('Error en la petición'); }
    });

    function escapeHtml(unsafe) { return String(unsafe).replace(/[&<"'>]/g, function(m){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]; }); }

    document.querySelectorAll('.calle-item').forEach(li => li.addEventListener('click', function(){
        const id = this.dataset.id; const nombre = this.textContent.trim(); window.currentCalleId = id; window.currentCalleName = nombre; loadViviendasByCalle(id, nombre);
    }));

});

// Forzar solo dígitos y máximo 3 en el campo número
const numeroInputSub = document.getElementById('numero');
if (numeroInputSub) {
    numeroInputSub.addEventListener('input', function () { this.value = this.value.replace(/\D/g, '').slice(0,3); });
}
</script>
