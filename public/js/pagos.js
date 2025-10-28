// public/js/pagos.js
async function submitPagoForm(form) {
    const url = form.getAttribute('action');
    const fd = new FormData(form);
    try {
        const res = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin' });
        const json = await res.json();
        if (json.ok) {
            alert(json.message || 'Enviado correctamente');
            // Deshabilitar form
            form.querySelectorAll('input,button,select,textarea').forEach(el => el.disabled = true);
        } else {
            alert(json.message || 'Error al enviar');
        }
    } catch (e) {
        alert('Error de red: ' + e.message);
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
