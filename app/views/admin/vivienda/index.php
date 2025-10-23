<?php

// Vista: Gestión de Viviendas (lista y formulario)
// Variables esperadas: $page_title (definido por index.php)
?>
<div class="card p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0"><?php echo htmlspecialchars($page_title ?? 'Viviendas'); ?></h3>
        <button id="btnNewVivienda" class="btn btn-primary">Registrar Vivienda</button>
    </div>

    <!-- Formulario (oculto por defecto) -->
    <div id="viviendaFormContainer" class="card p-3 mb-4 d-none">
        <form id="viviendaForm">
            <input type="hidden" name="id_vivienda" id="id_vivienda" value="">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Dirección</label>
                    <input type="text" id="direccion" name="direccion" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Número</label>
                    <input type="text" id="numero" name="numero" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tipo</label>
                    <select id="tipo" name="tipo" class="form-select" required>
                        <option value="">-- Seleccionar --</option>
                        <option value="Casa">Casa</option>
                        <option value="Apartamento">Apartamento</option>
                        <option value="Local">Local</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Sector</label>
                    <input type="text" id="sector" name="sector" class="form-control" required>
                </div>

                <div class="col-12">
                    <label class="form-label">Observaciones (opcional)</label>
                    <textarea id="observaciones" name="observaciones" class="form-control" rows="2"></textarea>
                </div>

                <div class="col-12 d-flex gap-2">
                    <button type="submit" class="btn btn-success">Guardar</button>
                    <button type="button" id="btnCancelVivienda" class="btn btn-secondary">Cancelar</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabla de viviendas -->
    <div class="table-responsive">
        <table class="table table-striped" id="viviendasTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Dirección</th>
                    <th>Número</th>
                    <th>Tipo</th>
                    <th>Sector</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="viviendasBody">
                <!-- llenado por JS -->
            </tbody>
        </table>
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
            document.getElementById('direccion').value = data.direccion || '';
            document.getElementById('numero').value = data.numero || '';
            document.getElementById('tipo').value = data.tipo || '';
            document.getElementById('sector').value = data.sector || '';
            document.getElementById('observaciones').value = data.observaciones || '';
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
        body.innerHTML = '<tr><td colspan="7">Cargando...</td></tr>';
        try {
            const res = await fetch(baseUrl + '&action=index', { credentials: 'same-origin' });
            const data = await res.json();
            if (!Array.isArray(data)) {
                body.innerHTML = '<tr><td colspan="7">No se recibieron datos válidos.</td></tr>';
                return;
            }
            if (data.length === 0) {
                body.innerHTML = '<tr><td colspan="7">No hay viviendas registradas.</td></tr>';
                return;
            }
            body.innerHTML = '';
            data.forEach(v => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${v.id_vivienda ?? ''}</td>
                    <td>${escapeHtml(v.direccion ?? '')}</td>
                    <td>${escapeHtml(v.numero ?? '')}</td>
                    <td>${escapeHtml(v.tipo ?? '')}</td>
                    <td>${escapeHtml(v.sector ?? '')}</td>
                    <td>${(v.activo == 1 || v.activo === '1' || v.activo === true) ? 'Sí' : 'No'}</td>
                    <td>
                        <button class="btn btn-sm btn-info btn-edit" data-id="${v.id_vivienda}">Editar</button>
                        <button class="btn btn-sm btn-danger btn-delete" data-id="${v.id_vivienda}">Eliminar</button>
                    </td>`;
                body.appendChild(tr);
            });

            // attach events
            document.querySelectorAll('.btn-edit').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                const res = await fetch(baseUrl + '&action=show&id=' + encodeURIComponent(id), { credentials: 'same-origin' });
                const data = await res.json();
                showForm(data);
            }));
            document.querySelectorAll('.btn-delete').forEach(b => b.addEventListener('click', async (e) => {
                const id = e.currentTarget.dataset.id;
                if (!confirm('¿Eliminar vivienda #' + id + '?')) return;
                const res = await fetch(baseUrl + '&action=destroy&id=' + encodeURIComponent(id), {
                    method: 'GET',
                    credentials: 'same-origin'
                });
                const r = await res.json();
                if (r.message) {
                    alert('Eliminado exitosamente');
                    loadViviendas();
                } else {
                    alert(r.error || 'Error al eliminar');
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
            direccion: document.getElementById('direccion').value.trim(),
            numero: document.getElementById('numero').value.trim(),
            tipo: document.getElementById('tipo').value,
            sector: document.getElementById('sector').value.trim(),
            observaciones: document.getElementById('observaciones').value.trim()
        };
        try {
            const url = id ? (baseUrl + '&action=update&id=' + encodeURIComponent(id)) : (baseUrl + '&action=store');
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify(payload)
            });
            const r = await res.json();
            if (res.ok && (r.message || r.id_vivienda)) {
                hideForm();
                loadViviendas();
            } else {
                alert(r.error || 'Error al guardar');
            }
        } catch (err) {
            console.error(err);
            alert('Error en la petición');
        }
    });

    function escapeHtml(unsafe) {
        return String(unsafe).replace(/[&<"'>]/g, function(m) {
            return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m];
        });
    }

    // inicializar
    loadViviendas();
});
</script>