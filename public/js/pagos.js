// public/js/pagos.js
async function submitPagoForm(form) {
    const url = form.getAttribute('action');
    const fd = new FormData(form);
    try {
        const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message || 'Enviado correctamente', 'success');
            // Deshabilitar form
            form.querySelectorAll('input,button,select,textarea').forEach(el => el.disabled = true);
        } else {
            showToast(json.message || 'Error al enviar', 'error');
        }
    } catch (e) {
        showToast('Error de red: ' + e.message, 'error');
    }
}

function showToast(message, type) {
    let toastElement;
    let toastBodyElement;
    if (type === 'success') {
        toastElement = document.getElementById('successToast');
        toastBodyElement = document.getElementById('successToastBody');
    } else {
        toastElement = document.getElementById('errorToast');
        toastBodyElement = document.getElementById('errorToastBody');
    }
    if (toastElement && toastBodyElement) {
        toastBodyElement.innerHTML = message;
        toastElement.classList.remove('d-none');
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    } else {
        // Fallback
        alert(message);
    }
}

async function fetchJsonWithTimeout(url, options = {}, timeout = 15000) {
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);
    try {
        const res = await fetch(url, Object.assign({}, options, { signal: controller.signal, credentials: 'same-origin' }));
        clearTimeout(id);
        return res.json();
    } catch (e) {
        clearTimeout(id);
        throw e;
    }
}

// Hacer funciones globales para uso desde vistas sin módulo
window.submitPagoForm = submitPagoForm;
window.fetchJsonWithTimeout = fetchJsonWithTimeout;

// Previsualizar evidencias (imagen o PDF) en modal dinámico
window.previewEvidence = function(url, filename) {
        // Crear modal si no existe
        let modal = document.getElementById('evidencePreviewModal');
        if (!modal) {
                const div = document.createElement('div');
                div.innerHTML = `
                        <div class="modal fade" id="evidencePreviewModal" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="evidencePreviewTitle"></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="evidencePreviewBody" style="min-height:300px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                `;
                document.body.appendChild(div);
                modal = document.getElementById('evidencePreviewModal');
        }

        const titleEl = document.getElementById('evidencePreviewTitle');
        const bodyEl = document.getElementById('evidencePreviewBody');
        titleEl.textContent = filename || url;
        // Detectar extensión
        const lower = url.split('?')[0].toLowerCase();
        if (lower.endsWith('.pdf')) {
                bodyEl.innerHTML = `<iframe src="${url}" style="width:100%;height:600px;border:0"></iframe>`;
        } else {
                bodyEl.innerHTML = `<img src="${url}" style="max-width:100%;height:auto;display:block;margin:0 auto">`;
        }

        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
}
