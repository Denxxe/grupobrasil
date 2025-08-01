// grupobrasil/public/js/admin_dashboard.js

document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle'); // Botón de hamburguesa para móviles
    const sidebarCollapseToggle = document.getElementById('sidebarCollapseToggle'); // Botón de colapso para escritorio
    const body = document.body; // Se usa para añadir/eliminar la clase global 'sidebar-collapsed'

    // --- Funcionalidad para Sidebar en Móviles ---
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('show'); // Alterna la clase 'show' para mostrar/ocultar en móviles
        });
    }

    // Cierra la barra lateral móvil si se hace clic fuera de ella (solo en móviles)
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 768) { // Asegura que esta lógica solo aplica a móviles
            const isClickInsideSidebar = sidebar.contains(event.target);
            const isClickOnSidebarToggle = sidebarToggle && sidebarToggle.contains(event.target);

            if (sidebar.classList.contains('show') && !isClickInsideSidebar && !isClickOnSidebarToggle) {
                sidebar.classList.remove('show');
            }
        }
    });

    // --- Funcionalidad para Sidebar en Escritorio (Colapsar/Expandir) ---
    if (sidebarCollapseToggle && sidebar) {
        sidebarCollapseToggle.addEventListener('click', function() {
            body.classList.toggle('sidebar-collapsed'); // Añade/elimina la clase en el body
            const icon = sidebarCollapseToggle.querySelector('i'); // Obtiene el ícono del botón

            // Cambia el ícono del botón para indicar el estado
            if (body.classList.contains('sidebar-collapsed')) {
                icon.classList.remove('fa-chevron-left');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-chevron-left');
            }
        });
    }

    // --- Manejo del Redimensionamiento de Ventana ---
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            // En escritorio, asegura que la barra lateral móvil no esté "mostrada"
            sidebar.classList.remove('show');
        } else {
            // En móvil, asegura que el estado colapsado de escritorio no esté aplicado
            body.classList.remove('sidebar-collapsed');
        }
    });
});