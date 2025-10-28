<?php // Vista de Reporte por Calle ?>
<div class="container mx-auto p-4 md:p-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-extrabold text-gray-900">🛣️ Reporte por Calle</h1>
            <p class="text-gray-600 mt-2">Habitantes de una calle específica</p>
        </div>
        <a href="index.php?route=admin/reports" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold py-2 px-4 rounded-lg transition duration-200">← Volver</a>
    </div>

    <div class="bg-white shadow-lg rounded-xl p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Calle</label>
                <select id="selectCalle" onchange="cargarReportePorCalle()" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white text-gray-900">
                    <option value="">-- Seleccione una calle --</option>
                    <?php foreach($calles as $calle): ?>
                        <option value="<?= $calle['id_calle'] ?>"><?= htmlspecialchars($calle['nombre']) ?><?= $calle['sector'] ? ' - ' . htmlspecialchars($calle['sector']) : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Buscar</label>
                <input type="text" id="filtroCalle" onkeyup="filtrarTabla('filtroCalle', 'tablaReporte')" placeholder="Buscar..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-indigo-500 focus:outline-none bg-white text-gray-900">
            </div>
            <div class="flex items-end gap-2">
                <button onclick="exportarExcel('reporte_por_calle')" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">📊</button>
                <button onclick="imprimirReporte()" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">🖨️</button>
            </div>
        </div>
        <div class="mt-4 text-sm text-gray-600"><span id="contadorRegistros">Seleccione una calle</span></div>
    </div>

    <div class="bg-white shadow-lg rounded-xl overflow-hidden">
        <div id="contenidoReporte" class="overflow-x-auto">
            <div class="text-center py-12 text-gray-500">
                <div class="text-5xl mb-4">🛣️</div>
                <p>Seleccione una calle para ver el reporte</p>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="./js/reportes.js"></script>
<script>
function cargarReportePorCalle() {
    const idCalle = document.getElementById('selectCalle').value;
    if (!idCalle) {
        document.getElementById('contenidoReporte').innerHTML = '<div class="text-center py-12 text-gray-500"><div class="text-5xl mb-4">🛣️</div><p>Seleccione una calle para ver el reporte</p></div>';
        document.getElementById('contadorRegistros').textContent = 'Seleccione una calle';
        return;
    }
    
    mostrarLoading();
    fetch(`index.php?route=admin/reporteHabitantesPorCalle&id_calle=${idCalle}`)
        .then(res => res.json())
        .then(datos => {
            if (!datos || datos.length === 0) { 
                mostrarSinDatos(); 
                document.getElementById('contadorRegistros').textContent = 'No hay habitantes en esta calle';
                return; 
            }
            mostrarHabitantesCalle(datos);
            actualizarContador(datos.length);
        })
        .catch(err => { console.error(err); mostrarError('Error al cargar el reporte'); });
}

function mostrarHabitantesCalle(datos) {
    let html = '<div class="space-y-4">';
    
    datos.forEach((hab, index) => {
        const esJefe = hab.es_jefe_familia == 1;
        
        html += `
            <div class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition duration-200">
                <!-- Header -->
                <div class="flex justify-between items-start mb-4 border-b pb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-900">${hab.nombre_completo}</h3>
                        <p class="text-gray-600 mt-1">${hab.calle_nombre}${hab.calle_sector ? ' - ' + hab.calle_sector : ''} - Casa #${hab.vivienda_numero}</p>
                        <div class="flex gap-2 mt-2">
                            ${esJefe ? crearBadge('Jefe de Familia', 'green') : ''}
                            ${hab.estado_civil ? crearBadge(hab.estado_civil, 'blue') : ''}
                            ${hab.total_familiares > 0 ? crearBadge(hab.total_familiares + ' Familiares', 'yellow') : ''}
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">ID: ${hab.id_habitante}</p>
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
                    </div>
                    
                    <div class="bg-gray-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">💼 Información Laboral</p>
                        <p class="text-sm"><span class="font-medium">Ocupación:</span> ${hab.ocupacion || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Nivel Educativo:</span> ${hab.nivel_educativo || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Condición:</span> ${hab.condicion || 'N/A'}</p>
                    </div>
                </div>

                <!-- Información de Vivienda -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div class="bg-blue-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-blue-700 uppercase mb-1">🏠 Vivienda</p>
                        <p class="text-sm"><span class="font-medium">Número:</span> ${hab.vivienda_numero || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Tipo:</span> ${hab.vivienda_tipo || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Estado:</span> ${hab.vivienda_estado || 'N/A'}</p>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-3">
                        <p class="text-xs font-semibold text-green-700 uppercase mb-1">🛣️ Calle/Sector</p>
                        <p class="text-sm"><span class="font-medium">Calle:</span> ${hab.calle_nombre || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Sector:</span> ${hab.calle_sector || 'N/A'}</p>
                        <p class="text-sm"><span class="font-medium">Líder(es):</span> ${hab.lideres_calle || 'Sin asignar'}</p>
                    </div>
                </div>

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
