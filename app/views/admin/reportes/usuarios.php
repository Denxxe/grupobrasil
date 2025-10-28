<?php // Vista de Reporte de Usuarios ?>
<div class="container mx-auto p-4 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">🔐 Reporte de Usuarios</h1>
            <p class="text-gray-600 mt-2">Usuarios del sistema con roles y permisos</p>
        </div>
        <a href="index.php?route=admin/reports" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">← Volver</a>
    </div>

    <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <input type="text" id="filtroUsuarios" onkeyup="filtrarTabla('filtroUsuarios', 'tablaReporte')" placeholder="Buscar..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-purple-500 focus:outline-none bg-white text-gray-900">
            </div>
            <div class="flex items-end gap-2">
                <button onclick="exportarExcel('reporte_usuarios')" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">📊 Excel</button>
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
    fetchJson('index.php?route=admin/reporteUsuarios')
        .then(datos => {
            if (!datos || datos.length === 0) { mostrarSinDatos(); return; }
            mostrarUsuarios(datos);
            actualizarContador(datos.length);
        })
        .catch(err => { console.error(err); mostrarError(err.message || 'Error al cargar el reporte'); });
});

function mostrarUsuarios(datos) {
    let html = '<div class="space-y-4">';
    
    datos.forEach((usr, index) => {
        const esLider = usr.id_rol == 2;
        const tieneHabitante = usr.id_habitante ? true : false;
        
        html += `
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4 border-b pb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900">${usr.nombre_completo}</h3>
                        <p class="text-gray-600 mt-1">${usr.email}</p>
                        <div class="flex gap-2 mt-2">
                            ${crearBadge(usr.rol_nombre || 'Usuario', 'purple')}
                            ${esLider && usr.total_calles_asignadas > 0 ? crearBadge(usr.total_calles_asignadas + ' Calles', 'yellow') : ''}
                            ${usr.es_jefe_familia == 1 ? crearBadge('Jefe de Familia', 'green') : ''}
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">ID Usuario: ${usr.id_usuario}</p>
                        <p class="text-xs text-gray-400">Creado: ${formatearFecha(usr.fecha_creacion)}</p>
                    </div>
                </div>

                <!-- Información Personal del Habitante -->
                ${tieneHabitante ? `
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">📋 Datos Personales</p>
                            <p class="text-sm"><span class="font-medium">Cédula:</span> ${usr.cedula || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Edad:</span> ${usr.edad || 'N/A'} años</p>
                            <p class="text-sm"><span class="font-medium">Sexo:</span> ${usr.sexo || 'N/A'}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">📞 Contacto</p>
                            <p class="text-sm"><span class="font-medium">Teléfono:</span> ${usr.telefono || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Correo Personal:</span> ${usr.correo_personal || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Dirección:</span> ${usr.direccion || 'N/A'}</p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-lg p-3">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">💼 Info Laboral</p>
                            <p class="text-sm"><span class="font-medium">Ocupación:</span> ${usr.ocupacion || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Nivel Educativo:</span> ${usr.nivel_educativo || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Estado Civil:</span> ${usr.estado_civil || 'N/A'}</p>
                        </div>
                    </div>
                ` : ''}

                <!-- Información de Usuario -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-purple-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-purple-700 uppercase mb-1">🔐 Información de Cuenta</p>
                        <p class="text-sm"><span class="font-medium">Email:</span> ${usr.email}</p>
                        <p class="text-sm"><span class="font-medium">Rol:</span> ${usr.rol_nombre}</p>
                        ${usr.rol_descripcion ? `<p class="text-sm"><span class="font-medium">Descripción:</span> ${usr.rol_descripcion}</p>` : ''}
                        <p class="text-sm"><span class="font-medium">Fecha Creación:</span> ${formatearFecha(usr.fecha_creacion)}</p>
                        <p class="text-sm"><span class="font-medium">Último Acceso:</span> ${formatearFecha(usr.ultimo_acceso)}</p>
                    </div>
                    
                    ${tieneHabitante && usr.vivienda_numero ? `
                        <div class="bg-blue-50 rounded-lg p-3">
                            <p class="text-xs font-semibold text-blue-700 uppercase mb-1">🏠 Vivienda</p>
                            <p class="text-sm"><span class="font-medium">Calle:</span> ${usr.calle_nombre || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Sector:</span> ${usr.calle_sector || 'N/A'}</p>
                            <p class="text-sm"><span class="font-medium">Casa #:</span> ${usr.vivienda_numero}</p>
                            <p class="text-sm"><span class="font-medium">Tipo:</span> ${usr.vivienda_tipo || 'N/A'}</p>
                        </div>
                    ` : ''}
                </div>

                <!-- Calles Asignadas (para líderes) -->
                ${esLider && usr.calles_asignadas ? `
                    <div class="bg-yellow-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-yellow-700 uppercase mb-1">⭐ Calles Asignadas como Líder</p>
                        <p class="text-sm">${usr.calles_asignadas}</p>
                        <p class="text-xs text-gray-600 mt-1">Total: ${usr.total_calles_asignadas} calle(s)</p>
                    </div>
                ` : ''}
            </div>
        `;
    });
    
    html += '</div>';
    
    document.getElementById('contenidoReporte').innerHTML = html;
}
</script>
