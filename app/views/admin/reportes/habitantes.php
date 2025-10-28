<?php
// Vista de Reporte de Habitantes - Grupo Brasil
?>

<div class="container mx-auto p-4 md:p-8">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">👥 Reporte de Habitantes</h1>
            <p class="text-gray-600 mt-2">Lista completa de todos los habitantes con información detallada</p>
        </div>
        <a href="index.php?route=admin/reports" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">
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
                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-red-500 focus:outline-none bg-white text-gray-900">
            </div>
            
            <!-- Botones de Acción -->
            <div class="flex items-end gap-2">
                <button onclick="exportarExcel('reporte_habitantes')" 
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
    
    fetchJson('index.php?route=admin/reporteHabitantes')
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
            mostrarError(err.message || 'Error al cargar el reporte');
        });
}

function mostrarTablaHabitantes(datos) {
    let html = '<div class="space-y-4">';
    
    datos.forEach((hab, index) => {
        const esJefe = hab.es_jefe_familia == 1;
        const tieneUsuario = hab.id_usuario ? true : false;
        
        html += `
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4 border-b pb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900">${hab.nombre_completo}</h3>
                        <div class="flex gap-2 mt-2">
                            ${esJefe ? crearBadge('Jefe de Familia', 'green') : ''}
                            ${tieneUsuario ? crearBadge(hab.rol_nombre || 'Usuario', 'purple') : ''}
                            ${hab.estado_civil ? crearBadge(hab.estado_civil, 'blue') : ''}
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">ID: ${hab.id_habitante}</p>
                        ${hab.habitante_fecha_registro ? `<p class="text-xs text-gray-400">Registrado: ${formatearFecha(hab.habitante_fecha_registro)}</p>` : ''}
                    </div>
                </div>

                <!-- Información Personal -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">📋 Datos Personales</p>
                        <p class="text-sm"><span class="font-medium">Cédula:</span> ${hab.cedula || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Edad:</span> ${hab.edad || 'N/A'} años</p>
                        <p class="text-sm"><span class="font-medium">Sexo:</span> ${hab.sexo || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">F. Nacimiento:</span> ${formatearFecha(hab.fecha_nacimiento)}</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">📞 Contacto</p>
                        <p class="text-sm"><span class="font-medium">Teléfono:</span> ${hab.telefono || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Correo:</span> ${hab.correo || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Dirección:</span> ${hab.direccion || 'N/A'}</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">💼 Información Laboral</p>
                        <p class="text-sm"><span class="font-medium">Ocupación:</span> ${hab.ocupacion || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Nivel Educativo:</span> ${hab.nivel_educativo || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Condición:</span> ${hab.condicion || 'N/A'}</p>
                    </div>
                </div>

                <!-- Información de Vivienda y Calle -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-blue-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-blue-700 uppercase mb-1">🏠 Vivienda</p>
                        <p class="text-sm"><span class="font-medium">Número:</span> ${hab.vivienda_numero || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Tipo:</span> ${hab.vivienda_tipo || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Estado:</span> ${hab.vivienda_estado || 'N/A'}</p>
                        ${hab.fecha_asignacion_vivienda ? `<p class="text-xs text-gray-600 mt-1">Asignado: ${formatearFecha(hab.fecha_asignacion_vivienda)}</p>` : ''}
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-green-700 uppercase mb-1">🛣️ Calle/Sector</p>
                        <p class="text-sm"><span class="font-medium">Calle:</span> ${hab.calle_nombre || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Sector:</span> ${hab.calle_sector || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Líder(es):</span> ${hab.lideres_calle || 'Sin asignar'}</p>
                    </div>
                </div>

                <!-- Información de Usuario (si tiene) -->
                ${tieneUsuario ? `
                    <div class="bg-purple-50 rounded-lg p-3 mb-4">
                        <p class="text-xs font-semibold text-purple-700 uppercase mb-1">🔐 Información de Usuario</p>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <p class="text-sm"><span class="font-medium">Email:</span> ${hab.usuario_email || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Rol:</span> ${hab.rol_nombre || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Creado:</span> ${formatearFecha(hab.usuario_fecha_creacion)}</p>
                            <p class="text-sm"><span class="font-medium">Último Acceso:</span> ${formatearFecha(hab.usuario_ultimo_acceso)}</p>
                        </div>
                    </div>
                ` : ''}

                <!-- Información Familiar -->
                ${esJefe && hab.total_familiares > 0 ? `
                    <div class="bg-yellow-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-yellow-700 uppercase mb-1">👨‍👩‍👧‍👦 Familia</p>
                        <p class="text-sm"><span class="font-medium">Total de miembros a cargo:</span> ${hab.total_familiares}</p>
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
        
        table {
            font-size: 10px;
        }
        
        th, td {
            padding: 4px !important;
        }
    }
</style>
