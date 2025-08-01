// grupobrasil/public/js/admin.js

document.addEventListener('DOMContentLoaded', function() {
    var successToastEl = document.getElementById('successToast');
    var errorToastEl = document.getElementById('errorToast');

    if (successToastEl) {
        var successToast = new bootstrap.Toast(successToastEl, {
            autohide: true,
            delay: 5000
        });
        successToast.show();
    }

    if (errorToastEl) {
        var errorToast = new bootstrap.Toast(errorToastEl, {
            autohide: true,
            delay: 7000
        });
        errorToast.show();
    }
});