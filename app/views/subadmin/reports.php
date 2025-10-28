<?php
// Vista Principal de Reportes - Subadmin (Líder de Calle)
?>

<div class="container mx-auto p-4 md:p-8">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-8 border-b-4 border-red-700 pb-2">
        📊 Reportes de Mi Calle
    </h1>

    <!-- Estadísticas Generales -->
    <div id="estadisticasGenerales" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-gradient-to-br from-red-50 to-red-100 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-red-700" id="totalHabitantes">-</p>
            <p class="text-sm text-gray-600">Habitantes</p>
        </div>
        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-blue-700" id="totalViviendas">-</p>
            <p class="text-sm text-gray-600">Viviendas</p>
        </div>
        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-green-700" id="totalFamilias">-</p>
            <p class="text-sm text-gray-600">Familias</p>
        </div>
    </div>

    <!-- Opciones de Reportes -->
    <h2 class="text-2xl font-bold text-gray-800 mb-6">📋 Reportes Disponibles</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        
        <!-- Reporte de Habitantes -->
        <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-red-700 hover:shadow-2xl transition duration-300">
            <div class="flex items-center mb-4">
                <span class="text-4xl mr-3">👥</span>
                <h3 class="text-xl font-bold text-gray-800">Habitantes</h3>
            </div>
            <p class="text-gray-600 mb-4 text-sm">Lista de habitantes de las calles asignadas a mí.</p>
            <button onclick="navegarA('habitantes')" class="block w-full bg-red-700 hover:bg-red-800 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-center cursor-pointer">
                Ver Reporte
            </button>
        </div>

        <!-- Reporte de Viviendas -->
        <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-blue-600 hover:shadow-2xl transition duration-300">
            <div class="flex items-center mb-4">
                <span class="text-4xl mr-3">🏠</span>
                <h3 class="text-xl font-bold text-gray-800">Viviendas</h3>
            </div>
            <p class="text-gray-600 mb-4 text-sm">Información de viviendas en mis calles asignadas.</p>
            <button onclick="navegarA('viviendas')" class="block w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-center cursor-pointer">
                Ver Reporte
            </button>
        </div>

        <!-- Reporte de Familias -->
        <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-green-600 hover:shadow-2xl transition duration-300">
            <div class="flex items-center mb-4">
                <span class="text-4xl mr-3">👨‍👩‍👧‍👦</span>
                <h3 class="text-xl font-bold text-gray-800">Familias</h3>
            </div>
            <p class="text-gray-600 mb-4 text-sm">Reporte de familias en mis calles asignadas.</p>
            <button onclick="navegarA('familias')" class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-center cursor-pointer">
                Ver Reporte
            </button>
        </div>

    </div>

</div>

<script>
// Función para navegar a los reportes
function navegarA(reporte) {
    console.log('Navegando a:', reporte);
    const url = 'index.php?route=subadmin/reportes/' + reporte;
    console.log('URL completa:', url);
    window.location.href = url;
}

// Cargar estadísticas generales al inicio
document.addEventListener('DOMContentLoaded', function() {
    cargarEstadisticas();
});

function cargarEstadisticas() {
    console.log('Iniciando carga de estadísticas...');
    fetch('index.php?route=subadmin/reporteEstadisticas')
        .then(res => {
            console.log('Respuesta recibida, status:', res.status);
            if (!res.ok) {
                throw new Error('Error HTTP: ' + res.status);
            }
            return res.json();
        })
        .then(data => {
            console.log('Estadísticas cargadas:', data);
            if (data.error) {
                console.error('Error del servidor:', data.error);
                return;
            }
            document.getElementById('totalHabitantes').textContent = data.total_habitantes || 0;
            document.getElementById('totalViviendas').textContent = data.total_viviendas || 0;
            document.getElementById('totalFamilias').textContent = data.total_familias || 0;
        })
        .catch(err => {
            console.error('Error al cargar estadísticas:', err);
        });
}
</script>
