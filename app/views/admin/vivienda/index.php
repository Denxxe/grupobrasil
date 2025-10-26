<?php

?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><?php echo htmlspecialchars($page_title ?? 'Viviendas'); ?></h3>
        <button id="btnNewVivienda" class="btn btn-primary">Registrar Vivienda</button>
    </div>

    <div id="viviendaFormContainer" class="card p-3 mb-4 d-none">
        <form id="viviendaForm">
            <input type="hidden" name="id_vivienda" id="id_vivienda" value="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Calle</label>
                    <select id="id_calle" name="id_calle" class="form-select">
                        <option value="">-- Seleccionar Calle --</option>
                        <?php if (isset($calles) && is_array($calles)): ?>
                            <?php foreach ($calles as $calle): ?>
                                <option value="<?php echo htmlspecialchars($calle['id_calle']); ?>">
                                    <?php echo htmlspecialchars($calle['nombre'] ?? $calle['nombre_calle'] ?? ''); ?>
                                </option>
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

    <!-- Tabla de viviendas -->
    <div class="row">
        <div class="col-md-4">
            <div class="card p-3 mb-4">
                <h5>Veredas / Calles</h5>
                <ul class="list-group" id="callesList">
                    <?php if (!empty($calles) && is_array($calles)): ?>
                        <?php foreach ($calles as $calle): ?>
                            <li class="list-group-item calle-item" data-id="<?= htmlspecialchars($calle['id_calle']) ?>">
                                <?= htmlspecialchars($calle['nombre'] ?? $calle['nombre_calle'] ?? '') ?>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li class="list-group-item">No hay veredas registradas.</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card p-3 mb-4">
                <h5 id="selectedCalleTitle">Seleccione una vereda para ver sus viviendas</h5>
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
                            <!-- llenado por JS -->
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

    // Base URL usando el mismo router: index.php?route=admin/viviendas
    const baseUrl = './index.php?route=admin/viviendas';

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
    function hideForm() {
        formContainer.classList.add('d-none');
    }

    btnNew.addEventListener('click', function(){ showForm(null); });
    btnCancel.addEventListener('click', function(){ hideForm(); });

    async function loadViviendas() {
        body.innerHTML = '<tr><td colspan="6">Selecciona una vereda...</td></tr>';
    }

    async function loadViviendasByCalle(idCalle, nombreCalle) {
        body.innerHTML = '<tr><td colspan="6">Cargando viviendas...</td></tr>';
        document.getElementById('selectedCalleTitle').textContent = 'Viviendas en: ' + nombreCalle;
        try {
            const res = await fetch(baseUrl + '&action=byCalle&id=' + encodeURIComponent(idCalle), { credentials: 'same-origin' });
            
            // Debug: verificar respuesta
            console.log('Response status:', res.status);
            console.log('Response headers:', res.headers);
            
            if (!res.ok) {
                const errorText = await res.text();
                console.error('Error response:', errorText);
                body.innerHTML = '<tr><td colspan="7">Error del servidor: ' + res.status + '</td></tr>';
                return;
            }
            
            const data = await res.json();
            console.log('Data received:', data);
            
            if (!Array.isArray(data)) {
                body.innerHTML = '<tr><td colspan="7">No se recibieron datos válidos.</td></tr>';
                console.error('Data is not an array:', data);
                return;
            }
            if (!Array.isArray(data) || data.length === 0) {
                body.innerHTML = '<tr><td colspan="6">No hay viviendas registradas en esta vereda.</td></tr>';
                return;
            }
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

            // attach events for details, edit and delete
            document.querySelectorAll('.btn-edit').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                // cargar datos de la vivienda y abrir el formulario en modo edición
                try {
                    const r = await fetch(baseUrl + '&action=show&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
                    if (!r.ok) throw new Error('Error cargando vivienda');
                    const data = await r.json();
                    showForm({
                        id_vivienda: data.id_vivienda || data.id || id,
                        id_calle: data.id_calle || data.idCalle || '',
                        numero: data.numero || '',
                        tipo: data.tipo || '',
                        estado: data.estado || 'Activo'
                    });
                } catch (err) {
                    console.error(err);
                    showToast('Error al cargar datos de la vivienda', 'error');
                }
            }));

            document.querySelectorAll('.btn-details').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                // Mostrar modal con detalles de familias
                try {
                    const r = await fetch(baseUrl + '&action=familiasPorVivienda&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
                    const payload = await r.json();
                    showViviendaFamiliesModal(payload, id);
                } catch (err) {
                    console.error(err);
                    showToast('Error al cargar detalles', 'error');
                }
            }));

            document.querySelectorAll('.btn-delete').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                if (!confirm('¿Seguro que deseas eliminar esta vivienda? Esto solo eliminará la vivienda; las cargas familiares asociadas permanecerán esperando reasignación.')) return;
                try {
                    const r = await fetch(baseUrl + '&action=destroy&id=' + encodeURIComponent(id), { method: 'POST', credentials: 'same-origin' });
                    const res = await r.json();
                    if (r.ok) {
                        showToast(res.message || 'Vivienda eliminada', 'success');
                        // refrescar la lista de la vereda seleccionada
                        if (window.currentCalleId) loadViviendasByCalle(window.currentCalleId, window.currentCalleName);
                        else loadViviendas();
                    } else {
                        showToast(res.error || 'Error al eliminar', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    showToast('Error en la petición', 'error');
                }
            }));

        } catch (err) {
            console.error(err);
            body.innerHTML = '<tr><td colspan="7">Error al cargar viviendas.</td></tr>';
        }
    }

    form.addEventListener('submit', async function(e){
        e.preventDefault();
        const id = document.getElementById('id_vivienda').value;
        const payload = {
            numero: document.getElementById('numero').value.trim(),
            tipo: document.getElementById('tipo').value,
            estado: document.getElementById('estado').value
        };
        
        const idCalle = document.getElementById('id_calle').value;
        if (idCalle) {
            payload.id_calle = parseInt(idCalle);
        }
        
        try {
            // Validación cliente: número solo dígitos y máximo 3
            const numeroVal = document.getElementById('numero').value.trim();
            if (!/^\d{1,3}$/.test(numeroVal)) { showToast('El número debe ser numérico y tener máximo 3 dígitos', 'error'); return; }
            const url = id ? (baseUrl + '&action=update&id=' + encodeURIComponent(id)) : (baseUrl + '&action=store');
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });
            const r = await res.json();
            if (res.ok && (r.message || r.id_vivienda)) {
                showToast(r.message || 'Guardado exitosamente', 'success');
                hideForm();
                if (window.currentCalleId) loadViviendasByCalle(window.currentCalleId, window.currentCalleName);
                else loadViviendas();
            } else {
                showToast(r.error || 'Error al guardar', 'error');
            }
        } catch (err) {
            console.error(err);
            showToast('Error en la petición', 'error');
        }
    });

    function escapeHtml(unsafe) {
        return String(unsafe).replace(/[&<"'>]/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }

    // Función para mostrar toasts
    function showToast(message, type = 'success') {
        const toastId = type === 'success' ? 'successToast' : 'errorToast';
        const toastBodyId = type === 'success' ? 'successToastBody' : 'errorToastBody';
        
        const toastEl = document.getElementById(toastId);
        const toastBody = document.getElementById(toastBodyId);
        
        if (toastEl && toastBody) {
            toastBody.textContent = message;
            toastEl.classList.remove('d-none');
            
            const toast = new bootstrap.Toast(toastEl, {
                autohide: true,
                delay: 3000
            });
            toast.show();
        }
    }

    // Inicializar: attach click en lista de calles
    document.querySelectorAll('.calle-item').forEach(li => li.addEventListener('click', function(){
        const id = this.dataset.id;
        const nombre = this.textContent.trim();
        // Guardar vereda seleccionada para refrescos posteriores
        window.currentCalleId = id;
        window.currentCalleName = nombre;
        loadViviendasByCalle(id, nombre);
    }));

    // modal HTML para detalles
    function showViviendaFamiliesModal(payload, viviendaId) {
        let html = '';
        if (!Array.isArray(payload) || payload.length === 0) {
            html = '<p>No se encontraron familias en esta vivienda.</p>';
        } else {
            payload.forEach(f => {
                html += `<div class="card mb-2"><div class="card-body"><h6>Jefe ID: ${f.id_jefe}</h6>`;
                if (Array.isArray(f.miembros) && f.miembros.length>0) {
                    html += '<ul>';
                    f.miembros.forEach(m => {
                        html += `<li>${escapeHtml(m.nombres || '')} ${escapeHtml(m.apellidos || '')} - ${escapeHtml(m.parentesco || '')}</li>`;
                    });
                    html += '</ul>';
                }
                html += '</div></div>';
            });
        }
        // Reutiliza un modal simple (crear si no existe)
        let modal = document.getElementById('viviendaFamiliesModal');
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'viviendaFamiliesModal';
            modal.className = 'modal fade';
            modal.tabIndex = -1;
            modal.innerHTML = `
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header"><h5 class="modal-title">Detalles de la vivienda ${viviendaId}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                        <div class="modal-body" id="viviendaFamiliesContent"></div>
                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
                    </div>
                </div>`;
            document.body.appendChild(modal);
        }
        document.getElementById('viviendaFamiliesContent').innerHTML = html;
        const bsModal = new bootstrap.Modal(document.getElementById('viviendaFamiliesModal'));
        bsModal.show();
    }

    // No se cargan todas las viviendas por defecto; el admin selecciona una vereda
});

// Forzar solo dígitos en el campo número mientras el usuario escribe
const numeroInput = document.getElementById('numero');
if (numeroInput) {
    numeroInput.addEventListener('input', function (e) {
        // eliminar todo lo que no sea dígito y limitar a 3
        this.value = this.value.replace(/\D/g, '').slice(0,3);
    });
}
</script>