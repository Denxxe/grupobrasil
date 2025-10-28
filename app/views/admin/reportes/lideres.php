<?php // Vista de Reporte de Líderes de Calle ?>
<div class="container mx-auto p-4 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">⭐ Reporte de Líderes de Calle</h1>
            <p class="text-gray-600 mt-2">Líderes asignados a cada calle con información de contacto</p>
        </div>
        <a href="index.php?route=admin/reports" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">← Volver</a>
    </div>

    <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <input type="text" id="filtroLideres" onkeyup="filtrarTabla('filtroLideres', 'tablaReporte')" placeholder="Buscar..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-yellow-500 focus:outline-none">
            </div>
            <div class="flex items-end gap-2">
                <button onclick="exportarExcel('reporte_lideres')" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">📊 Excel</button>
                <button onclick="imprimirReporte()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">🖨️ Imprimir</button>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600"><span id="contadorRegistros">Cargando...</span></div>
    </div>

    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <div id="contenidoReporte" class="overflow-x-auto"></div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="./js/reportes.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    mostrarLoading();
    fetch('index.php?route=admin/reporteLideresCalle')
        .then(res => res.json())
        .then(datos => {
            if (!datos || datos.length === 0) { mostrarSinDatos(); return; }
            mostrarLideres(datos);
            actualizarContador(datos.length);
        })
        .catch(err => { console.error(err); mostrarError('Error al cargar el reporte'); });
});

function mostrarLideres(datos) {
    let html = '<div class="space-y-4">';
    
    datos.forEach((lider, index) => {
        html += `
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4 border-b pb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900">⭐ ${lider.nombre_completo}</h3>
                        <p class="text-gray-600 mt-1">${lider.email}</p>
                        <div class="flex gap-2 mt-2">
                            ${crearBadge('Líder de Calle', 'yellow')}
                            ${lider.total_calles ? crearBadge(lider.total_calles + ' Calles', 'blue') : ''}
                            ${lider.total_habitantes_asignados ? crearBadge(lider.total_habitantes_asignados + ' Habitantes', 'green') : ''}
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">ID: ${lider.id_usuario}</p>
                        ${lider.fecha_designacion ? `<p class="text-xs text-gray-400">Designado: ${formatearFecha(lider.fecha_designacion)}</p>` : ''}
                    </div>
                </div>

                <!-- Información Personal -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">📋 Datos Personales</p>
                        <p class="text-sm"><span class="font-medium">Cédula:</span> ${lider.cedula || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Edad:</span> ${lider.edad || 'N/A'} años</p>
                        <p class="text-sm"><span class="font-medium">Sexo:</span> ${lider.sexo || 'N/A'}</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">📞 Contacto</p>
                        <p class="text-sm"><span class="font-medium">Teléfono:</span> ${lider.telefono || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Correo:</span> ${lider.correo || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Email Sistema:</span> ${lider.email || 'N/A'}</p>
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">💼 Información Adicional</p>
                        <p class="text-sm"><span class="font-medium">Ocupación:</span> ${lider.ocupacion || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Nivel Educativo:</span> ${lider.nivel_educativo || 'N/A'}</p>
                    </div>
                </div>

                <!-- Información de Usuario -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-purple-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-purple-700 uppercase mb-1">🔐 Información de Cuenta</p>
                        <p class="text-sm"><span class="font-medium">Email:</span> ${lider.email}</p>
                        <p class="text-sm"><span class="font-medium">Fecha Creación:</span> ${formatearFecha(lider.usuario_fecha_creacion)}</p>
                        <p class="text-sm"><span class="font-medium">Último Acceso:</span> ${formatearFecha(lider.ultimo_acceso)}</p>
                    </div>
                    
                    <div class="bg-blue-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-blue-700 uppercase mb-1">📊 Estadísticas</p>
                        <p class="text-sm"><span class="font-medium">Total Calles:</span> ${lider.total_calles || 0}</p>
                        <p class="text-sm"><span class="font-medium">Total Habitantes:</span> ${lider.total_habitantes_asignados || 0}</p>
                        <p class="text-sm"><span class="font-medium">Fecha Designación:</span> ${formatearFecha(lider.fecha_designacion)}</p>
                    </div>
                </div>

                <!-- Calles Asignadas -->
                ${lider.calles_asignadas ? `
                    <div class="bg-yellow-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-yellow-700 uppercase mb-1">🛣️ Calles Asignadas</p>
                        <p class="text-sm font-medium">${lider.calles_asignadas}</p>
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    
    document.getElementById('contenidoReporte').innerHTML = html;
}
</script>
