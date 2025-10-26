<?php
// Vista Principal de Reportes - Grupo Brasil
?>

<div class="container mx-auto p-4 md:p-8">
    <h1 class="text-4xl font-extrabold text-gray-900 mb-8 border-b-4 border-red-700 pb-2">
        📊 Reportes del Sistema
    </h1>

    <!-- Estadísticas Generales -->
    <div id="estadisticasGenerales" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
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
        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-purple-700" id="totalCalles">-</p>
            <p class="text-sm text-gray-600">Calles</p>
        </div>
        <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-yellow-700" id="totalUsuarios">-</p>
            <p class="text-sm text-gray-600">Usuarios</p>
        </div>
        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-indigo-700" id="totalLideres">-</p>
            <p class="text-sm text-gray-600">Líderes</p>
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
            <p class="text-gray-600 mb-4 text-sm">Lista completa de todos los habitantes con información personal, vivienda, calle y edad.</p>
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
            <p class="text-gray-600 mb-4 text-sm">Información de todas las viviendas, ubicación, tipo y cantidad de habitantes.</p>
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
            <p class="text-gray-600 mb-4 text-sm">Reporte de familias con jefes de hogar y todos sus miembros.</p>
            <button onclick="navegarA('familias')" class="block w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-center cursor-pointer">
                Ver Reporte
            </button>
        </div>

        <!-- Reporte de Usuarios -->
        <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-purple-600 hover:shadow-2xl transition duration-300">
            <div class="flex items-center mb-4">
                <span class="text-4xl mr-3">🔐</span>
                <h3 class="text-xl font-bold text-gray-800">Usuarios</h3>
            </div>
            <p class="text-gray-600 mb-4 text-sm">Usuarios del sistema con roles, permisos y último acceso.</p>
            <button onclick="navegarA('usuarios')" class="block w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-center cursor-pointer">
                Ver Reporte
            </button>
        </div>

        <!-- Reporte de Líderes de Calle -->
        <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-yellow-600 hover:shadow-2xl transition duration-300">
            <div class="flex items-center mb-4">
                <span class="text-4xl mr-3">⭐</span>
                <h3 class="text-xl font-bold text-gray-800">Líderes de Calle</h3>
            </div>
            <p class="text-gray-600 mb-4 text-sm">Líderes asignados a cada calle con sus datos de contacto.</p>
            <button onclick="navegarA('lideres')" class="block w-full bg-yellow-600 hover:bg-yellow-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-center cursor-pointer">
                Ver Reporte
            </button>
        </div>

        <!-- Reporte por Calle -->
        <div class="bg-white shadow-lg rounded-xl p-6 border-l-4 border-indigo-600 hover:shadow-2xl transition duration-300">
            <div class="flex items-center mb-4">
                <span class="text-4xl mr-3">🛣️</span>
                <h3 class="text-xl font-bold text-gray-800">Por Calle</h3>
            </div>
            <p class="text-gray-600 mb-4 text-sm">Habitantes de una calle específica.</p>
            <button onclick="navegarA('por-calle')" class="block w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 text-center cursor-pointer">
                Ver Reporte
            </button>
        </div>

    </div>

</div>

<script>
// Función para navegar a los reportes
function navegarA(reporte) {
    console.log('Navegando a:', reporte);
    const url = 'index.php?route=admin/reportes/' + reporte;
    console.log('URL completa:', url);
    window.location.href = url;
}

// Cargar estadísticas generales al inicio
document.addEventListener('DOMContentLoaded', function() {
    cargarEstadisticas();
});

function cargarEstadisticas() {
    console.log('Iniciando carga de estadísticas...');
    fetch('index.php?route=admin/reporteEstadisticas')
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
            document.getElementById('totalCalles').textContent = data.total_calles || 0;
            document.getElementById('totalUsuarios').textContent = data.total_usuarios || 0;
            document.getElementById('totalLideres').textContent = data.total_lideres || 0;
        })
        .catch(err => {
            console.error('Error al cargar estadísticas:', err);
        });
}
</script>