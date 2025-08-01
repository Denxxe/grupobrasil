// grupobrasil/public/js/edit_user_form.js

document.addEventListener('DOMContentLoaded', function() {
    // Inicializar y mostrar toasts
    var successToastEl = document.getElementById('successToast');
    var errorToastEl = document.getElementById('errorToast');

    if (successToastEl) {
        var successToast = new bootstrap.Toast(successToastEl);
        successToast.show();
    }
    if (errorToastEl) {
        var errorToast = new bootstrap.Toast(errorToastEl);
        errorToast.show();
    }

    // Bootstrap Validation (para el formulario de edición)
    var forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
});