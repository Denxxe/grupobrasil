<?php
// Vista de Reporte de Habitantes - Subadmin (Líder de Calle)
?>

<div class="container mx-auto p-4 md:p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">👥 Reporte de Habitantes</h1>
            <p class="text-gray-600 mt-2">Lista de habitantes de mis calles asignadas</p>
        </div>
        <a href="index.php?route=subadmin/reports" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
            ← Volver
        </a>
    </div>

    <!-- Filtros y Acciones -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Búsqueda -->
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" id="filtroHabitantes" onkeyup="filtrarTabla('filtroHabitantes', 'tablaReporte')" 
                    placeholder="Buscar por nombre, cédula, calle, vivienda..." 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:outline-none">
            </div>
            
            <!-- Botones de Acción -->
            <div class="flex items-end gap-2">
                <button onclick="exportarExcel('reporte_habitantes_micalle')" 
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <span>📊</span> Excel
                </button>
                <button onclick="imprimirReporte()" 
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <span>🖨️</span> Imprimir
                </button>
            </div>
        </div>
        
        <!-- Contador de registros -->
        <div class="mt-4 text-sm text-gray-600">
            <span id="contadorRegistros">Cargando...</span>
        </div>
    </div>

    <!-- Contenido del Reporte -->
    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <div id="contenidoReporte" class="overflow-x-auto">
            <!-- Los datos se cargarán aquí -->
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="./js/reportes.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarReporteHabitantes();
});

function cargarReporteHabitantes() {
    mostrarLoading();
    
    fetch('index.php?route=subadmin/reporteHabitantes')
        .then(res => res.json())
        .then(datos => {
            if (!datos || datos.length === 0) {
                mostrarSinDatos();
                return;
            }
            
            mostrarTablaHabitantes(datos);
            actualizarContador(datos.length);
        })
        .catch(err => {
            console.error('Error:', err);
            mostrarError('Error al cargar el reporte');
        });
}

function mostrarTablaHabitantes(datos) {
    let html = `
        <table id="tablaReporte" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cédula</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Edad</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sexo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Teléfono</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Calle</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vivienda</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jefe Familia</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
    `;
    
    datos.forEach(hab => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${hab.nombre_completo}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${hab.cedula || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${hab.edad || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${hab.sexo || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${hab.telefono || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${hab.calle || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${hab.numero_vivienda || 'N/A'}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm">
                    ${hab.es_jefe_familia == 1 ? crearBadge('Sí', 'green') : crearBadge('No', 'gray')}
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    document.getElementById('contenidoReporte').innerHTML = html;
}
</script>

<style media="print">
    @media print {
        .no-print {
            display: none !important;
        }
        body {
            print-color-adjust: exact;
            -webkit-print-color-adjust: exact;
        }
    }
</style>
