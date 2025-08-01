// public/js/admin_news_scripts.js

document.addEventListener('DOMContentLoaded', function() {
    // ELIMINAR ESTAS LÍNEAS YA QUE toast_initializer.js se encarga de esto
    // var toastElList = [].slice.call(document.querySelectorAll('.toast'))
    // var toastList = toastElList.map(function (toastEl) {
    //     return new bootstrap.Toast(toastEl)
    // })
    // toastList.forEach(toast => toast.show());

    // Configuración del Modal de Confirmación de Eliminación
    var deleteModal = document.getElementById('deleteConfirmationModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            // Botón que disparó el modal
            var button = event.relatedTarget;

            // Extraer info de los atributos data-*
            var newsId = button.getAttribute('data-id');
            var newsTitle = button.getAttribute('data-title');

            // Actualizar el título de la noticia en el cuerpo del modal
            var modalTitleElement = deleteModal.querySelector('#newsTitleToDelete');
            if (modalTitleElement) {
                modalTitleElement.textContent = newsTitle;
            }

            // Actualizar el href del botón de confirmación del modal
            var confirmButton = deleteModal.querySelector('#confirmDeleteButton');
            if (confirmButton) {
                confirmButton.href = '/grupobrasil/public/index.php?route=admin/news/delete&id=' + newsId;
            }
        });
    }
});