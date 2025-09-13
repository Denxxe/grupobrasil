// user_dashboard.js
// Funciones base para peticiones fetch desde el layout de usuario

/**
 * Realiza una petición fetch genérica y maneja errores básicos.
 * @param {string} url - URL a la que se hace la petición
 * @param {object} options - Opciones fetch (method, headers, body, etc)
 * @returns {Promise<object>} - Respuesta parseada o error
 */
async function fetchApi(url, options = {}) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            throw new Error(`Error ${response.status}: ${response.statusText}`);
        }
        // Intenta parsear como JSON, si falla devuelve texto
        try {
            return await response.json();
        } catch {
            return await response.text();
        }
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

// Ejemplo de uso: obtener datos del perfil
async function obtenerPerfilUsuario() {
    try {
        const data = await fetchApi('./index.php?route=user/profile/data');
        // Aquí puedes actualizar el DOM con los datos recibidos
        console.log('Perfil:', data);
    } catch (e) {
        mostrarToastError('No se pudo obtener el perfil');
    }
}

// Función para mostrar toast de error (usa los mismos IDs que el layout)
function mostrarToastError(msg) {
    const toast = document.getElementById('errorToast');
    const body = document.getElementById('errorToastBody');
    if (toast && body) {
        body.textContent = msg;
        toast.classList.remove('d-none');
        toast.classList.add('show');
        setTimeout(() => {
            toast.classList.remove('show');
            toast.classList.add('d-none');
        }, 4000);
    }
}

// Puedes agregar más funciones para otras rutas según necesidades del usuario
