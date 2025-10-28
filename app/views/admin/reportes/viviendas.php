<?php
// Vista de Reporte de Viviendas - Grupo Brasil
?>

<div class="container mx-auto p-4 md:p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">🏠 Reporte de Viviendas</h1>
            <p class="text-gray-600 mt-2">Información completa de todas las viviendas registradas</p>
        </div>
        <a href="index.php?route=admin/reports" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
            ← Volver
        </a>
    </div>

    <!-- Filtros y Acciones -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" id="filtroViviendas" onkeyup="filtrarTabla('filtroViviendas', 'tablaReporte')" 
                    placeholder="Buscar por calle, número, tipo..." 
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white text-gray-900">
            </div>
            
            <div class="flex items-end gap-2">
                <button onclick="exportarExcel('reporte_viviendas')" 
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <span>📊</span> Excel
                </button>
                <button onclick="imprimirReporte()" 
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2">
                    <span>🖨️</span> Imprimir
                </button>
            </div>
        </div>
        
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

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="./js/reportes.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarReporteViviendas();
});

function cargarReporteViviendas() {
    mostrarLoading();
    
    fetchJson('index.php?route=admin/reporteViviendas')
        .then(datos => {
            if (!datos || datos.length === 0) {
                mostrarSinDatos();
                return;
            }

            mostrarTablaViviendas(datos);
            actualizarContador(datos.length);
        })
        .catch(err => {
            console.error('Error:', err);
            mostrarError(err.message || 'Error al cargar el reporte');
        });
}

function mostrarTablaViviendas(datos) {
    let html = '<div class="space-y-4">';
    
    datos.forEach((viv, index) => {
        let estadoBadge = '';
        switch(viv.estado?.toLowerCase()) {
            case 'bueno':
                estadoBadge = crearBadge(viv.estado, 'green');
                break;
            case 'regular':
                estadoBadge = crearBadge(viv.estado, 'yellow');
                break;
            case 'malo':
                estadoBadge = crearBadge(viv.estado, 'red');
                break;
            default:
                estadoBadge = crearBadge(viv.estado || 'N/A', 'gray');
        }
        
        html += `
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4 border-b pb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900">🏠 Casa #${viv.numero}</h3>
                        <p class="text-gray-600 mt-1">${viv.calle_nombre}${viv.calle_sector ? ' - ' + viv.calle_sector : ''}</p>
                        <div class="flex gap-2 mt-2">
                            ${estadoBadge}
                            ${crearBadge(viv.tipo || 'N/A', 'blue')}
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">ID: ${viv.id_vivienda}</p>
                        ${viv.vivienda_fecha_registro ? `<p class="text-xs text-gray-400">Registrada: ${formatearFecha(viv.vivienda_fecha_registro)}</p>` : ''}
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-700">${viv.total_habitantes || 0}</p>
                        <p class="text-xs text-gray-600">Habitantes</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-700">${viv.total_familias || 0}</p>
                        <p class="text-xs text-gray-600">Familias</p>
                    </div>
                    <div class="bg-purple-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-purple-700">${viv.habitaciones || 'N/A'}</p>
                        <p class="text-xs text-gray-600">Habitaciones</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-yellow-700">${viv.banos || 'N/A'}</p>
                        <p class="text-xs text-gray-600">Baños</p>
                    </div>
                </div>

                <!-- Detalles de la Vivienda -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">📍 Ubicación</p>
                        <p class="text-sm"><span class="font-medium">Calle:</span> ${viv.calle_nombre || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Sector:</span> ${viv.calle_sector || 'N/A'}</p>
                        ${viv.calle_descripcion ? `<p class="text-sm"><span class="font-medium">Descripción:</span> ${viv.calle_descripcion}</p>` : ''}
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-2">🔧 Servicios</p>
                        <p class="text-sm">${viv.servicios || 'No especificado'}</p>
                    </div>
                </div>

                <!-- Jefes de Familia -->
                ${viv.jefes_familia ? `
                    <div class="bg-green-50 rounded-lg p-3 mb-4">
                        <p class="text-xs font-semibold text-green-700 uppercase mb-1">👨‍👩‍👧‍👦 Jefes de Familia</p>
                        <p class="text-sm">${viv.jefes_familia}</p>
                    </div>
                ` : ''}

                <!-- Líderes de Calle -->
                ${viv.lideres_calle ? `
                    <div class="bg-yellow-50 rounded-lg p-3 mb-4">
                        <p class="text-xs font-semibold text-yellow-700 uppercase mb-1">⭐ Líderes de Calle</p>
                        <p class="text-sm">${viv.lideres_calle}</p>
                    </div>
                ` : ''}

                <!-- Observaciones -->
                ${viv.vivienda_observaciones ? `
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">📝 Observaciones</p>
                        <p class="text-sm text-gray-700">${viv.vivienda_observaciones}</p>
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    
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
