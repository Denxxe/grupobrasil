// public/js/toast_initializer.js

document.addEventListener('DOMContentLoaded', function() {
    const body = document.body;
    const successMessage = body.getAttribute('data-success-message');
    const errorMessage = body.getAttribute('data-error-message');

    // Opcional: Para depuración, puedes descomentar estas líneas
    // console.log('toast_initializer.js se está ejecutando.');
    // console.log('DOMContentLoaded disparado en toast_initializer.js.');
    // console.log('Success Message (leído del body):', successMessage);
    // console.log('Error Message (leído del body):', errorMessage);

    // Función para mostrar un toast específico
    function showToast(message, type) {
        let toastElement;
        let toastBodyElement;

        if (type === 'success') {
            toastElement = document.getElementById('successToast');
            toastBodyElement = document.getElementById('successToastBody');
        } else if (type === 'error') {
            toastElement = document.getElementById('errorToast');
            toastBodyElement = document.getElementById('errorToastBody');
        }

        // Solo procede si el elemento del toast y su cuerpo existen, Y si hay un mensaje
        if (toastElement && toastBodyElement && message) {
            toastBodyElement.innerHTML = message; // Usar innerHTML para permitir <br>

            // Eliminar la clase 'd-none' para que el toast sea visible antes de mostrarlo
            // Esto es crucial si lo tienes en tu HTML con d-none por defecto
            toastElement.classList.remove('d-none'); 
            
            // Inicializa y muestra el toast de Bootstrap
            const toast = new bootstrap.Toast(toastElement);
            toast.show();

            // Opcional: Para depuración, puedes descomentar
            // console.log(`Toast de ${type} mostrado con mensaje: ${message}`);
        } else {
            // Opcional: Para depuración, puedes descomentar
            // console.log(`No se mostró toast de ${type}. Elemento o mensaje faltante.`);
        }
    }

    // Muestra el toast de éxito SOLO SI successMessage NO está vacío
    if (successMessage && successMessage.trim() !== '') {
        showToast(successMessage, 'success');
    }

    // Muestra el toast de error SOLO SI errorMessage NO está vacío
    if (errorMessage && errorMessage.trim() !== '') {
        showToast(errorMessage, 'error');
    }
});