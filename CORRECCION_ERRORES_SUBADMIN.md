
- Cambio de esto

# Corrección de Errores - Módulo Subadmin (Líder de Calle)

## 📋 Problemas Resueltos

### ✅ **1. Error "Acción No Encontrada"**
**Problema:** Las rutas de subadmin no estaban definidas en `index.php`

**Solución:**
- Agregadas rutas para `habitantes`, `viviendas`, `familias`
- Agregadas rutas para acciones: `addHabitante`, `editHabitante`, `deleteHabitante`

---

### ✅ **2. Error al Guardar Habitante**
**Problema:** La ruta de guardado no estaba correctamente mapeada

**Solución:**
- Corregida ruta en `index.php` para `subadmin/addHabitante`
- Actualizado método `addHabitante()` en `SubadminController`

---

### ✅ **3. Falta de Mensajes Toast de Éxito**
**Problema:** No había feedback visual al guardar

**Solución:**
- Agregados mensajes flash de éxito/error usando `$_SESSION['flash_success']` y `$_SESSION['flash_error']`
- Los mensajes se muestran automáticamente en la vista

---

### ✅ **4. Permitir Registrar Jefes de Familia**
**Problema:** No había opción para marcar un habitante como jefe de familia

**Solución:**
- Agregado checkbox "Es Jefe de Familia" en el formulario
- Agregado campo `id_vivienda` para asignar vivienda
- Actualizado método para crear registro en `habitante_vivienda` con `es_jefe_familia`

---

### ✅ **5. Vista de Viviendas con Error**
**Problema:** Variables incorrectas pasadas a la vista

**Solución:**
- Corregidas variables en `SubadminController::viviendas()`
- Agregadas variables de compatibilidad: `veredasAsignadas`, `todasVeredas`

---

## 📁 Archivos Modificados

### **1. `public/index.php`**

#### Rutas agregadas:
```php
case 'subadmin':
    // ... código existente ...
    
    elseif ($actionSegment === 'habitantes') {
        $actionName = 'habitantes';
    } 
    elseif ($actionSegment === 'addHabitante') {
        $actionName = 'addHabitante';
    } 
    elseif ($actionSegment === 'editHabitante') {
        $actionName = 'editHabitante';
    } 
    elseif ($actionSegment === 'deleteHabitante') {
        $actionName = 'deleteHabitante';
    }
    elseif ($actionSegment === 'viviendas') {
        $actionName = 'viviendas';
    }
    elseif ($actionSegment === 'familias') {
        $actionName = 'familias';
    }
```

#### API endpoint agregado:
```php
case 'api':
    if ($actionSegment === 'viviendas-por-calle') {
        header('Content-Type: application/json');
        require_once __DIR__ . '/../app/models/Vivienda.php';
        $viviendaModel = new Vivienda();
        $idCalle = filter_input(INPUT_GET, 'id_calle', FILTER_SANITIZE_NUMBER_INT);
        
        if ($idCalle) {
            $viviendas = $viviendaModel->getViviendasPorCalle($idCalle);
            echo json_encode(['success' => true, 'viviendas' => $viviendas]);
        } else {
            echo json_encode(['success' => false, 'message' => 'ID de calle no válido']);
        }
        exit();
    }
    break;
```

---

### **2. `app/controllers/SubadminController.php`**

#### Método `addHabitante()` actualizado:
```php
public function addHabitante() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Location:./index.php?route=subadmin/habitantes');
        exit();
    }
    
    $idUsuario = $_SESSION['id_usuario'] ?? 0;
    $calleIds = $this->liderCalleModel->getCallesIdsPorUsuario($idUsuario);
    $idCalle = (int)($_POST['id_calle'] ?? 0);
    $idVivienda = (int)($_POST['id_vivienda'] ?? 0);
    
    // Verificar que la calle esté asignada al líder
    if (!in_array($idCalle, $calleIds)) {
        $_SESSION['flash_error'] = 'No tienes permiso para agregar habitantes a esta calle.';
        header('Location:./index.php?route=subadmin/habitantes');
        exit();
    }
    
    // Crear persona
    $personaData = [
        'cedula' => $_POST['cedula'] ?? null,
        'nombres' => $_POST['nombres'] ?? '',
        'apellidos' => $_POST['apellidos'] ?? '',
        'fecha_nacimiento' => $_POST['fecha_nacimiento'] ?? null,
        'sexo' => $_POST['sexo'] ?? '',
        'telefono' => $_POST['telefono'] ?? '',
        'direccion' => $_POST['direccion'] ?? '',
        'correo' => $_POST['correo'] ?? null,
        'activo' => 1
    ];
    
    $idPersona = $this->personaModel->create($personaData);
    
    if ($idPersona) {
        // Crear habitante
        $habitanteData = [
            'id_persona' => $idPersona,
            'fecha_ingreso' => date('Y-m-d'),
            'condicion' => $_POST['condicion'] ?? 'Residente',
            'activo' => 1
        ];
        
        $idHabitante = $this->habitanteModel->create($habitanteData);
        
        if ($idHabitante) {
            // Asignar a vivienda si se seleccionó
            if ($idVivienda > 0) {
                $esJefeFamilia = isset($_POST['es_jefe_familia']) ? 1 : 0;
                $habitanteViviendaData = [
                    'id_habitante' => $idHabitante,
                    'id_vivienda' => $idVivienda,
                    'es_jefe_familia' => $esJefeFamilia,
                    'fecha_ingreso' => date('Y-m-d'),
                    'activo' => 1
                ];
                $this->habitanteViviendaModel->create($habitanteViviendaData);
            }
            
            $_SESSION['flash_success'] = 'Habitante agregado exitosamente.';
        } else {
            $_SESSION['flash_error'] = 'Error al crear el habitante.';
        }
    } else {
        $_SESSION['flash_error'] = 'Error al crear la persona.';
    }
    
    header('Location:./index.php?route=subadmin/habitantes');
    exit();
}
```

---

### **3. `app/views/subadmin/habitantes/index.php`**

#### Campos agregados al formulario:

**Campo Vivienda:**
```html
<div class="col-md-6 mb-3">
    <label for="id_vivienda" class="form-label">Vivienda</label>
    <select class="form-select" id="id_vivienda" name="id_vivienda">
        <option value="">Seleccionar calle primero...</option>
    </select>
</div>
```

**Checkbox Jefe de Familia:**
```html
<div class="col-md-6 mb-3">
    <div class="form-check mt-4">
        <input class="form-check-input" type="checkbox" 
               id="es_jefe_familia" name="es_jefe_familia" value="1">
        <label class="form-check-label" for="es_jefe_familia">
            <strong>Es Jefe de Familia</strong>
        </label>
    </div>
</div>
```

**Script JavaScript para cargar viviendas:**
```javascript
<script>
function cargarViviendas(idCalle) {
    const selectVivienda = document.getElementById('id_vivienda');
    selectVivienda.innerHTML = '<option value="">Cargando...</option>';
    
    if (!idCalle) {
        selectVivienda.innerHTML = '<option value="">Seleccionar calle primero...</option>';
        return;
    }
    
    fetch(`./index.php?route=api/viviendas-por-calle&id_calle=${idCalle}`)
        .then(response => response.json())
        .then(data => {
            selectVivienda.innerHTML = '<option value="">Sin vivienda</option>';
            if (data.success && data.viviendas.length > 0) {
                data.viviendas.forEach(vivienda => {
                    const option = document.createElement('option');
                    option.value = vivienda.id_vivienda;
                    option.textContent = `Casa #${vivienda.numero}`;
                    selectVivienda.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            selectVivienda.innerHTML = '<option value="">Error al cargar viviendas</option>';
        });
}
</script>
```

---

### **4. `app/models/Vivienda.php`**

#### Método agregado:
```php
/**
 * Obtiene viviendas de una calle específica
 * @param int $idCalle ID de la calle
 * @return array Array de viviendas
 */
public function getViviendasPorCalle(int $idCalle): array {
    $sql = "SELECT id_vivienda, numero, tipo, estado
            FROM " . $this->table . " 
            WHERE activo = 1 AND id_calle = ?
            ORDER BY numero ASC";
    
    $stmt = $this->conn->prepare($sql);
    if ($stmt === false) {
        error_log("Error al preparar getViviendasPorCalle: " . $this->conn->error);
        return [];
    }
    
    $stmt->bind_param("i", $idCalle);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    $stmt->close();
    return $data;
}
```

---

### **5. `app/views/layouts/subadmin_layout.php`**

#### Corrección de error en línea 160:
```php
// ANTES:
error_log("Error: La vista de contenido '$content_view_path' no existe o no está definida.");

// DESPUÉS:
$ruta = $content_view_path ?? 'N/A';
error_log("Error: La vista de contenido no existe o no está definida. Ruta: " . $ruta);
```

---

## 🎯 Funcionalidades Implementadas

### **1. Registro de Habitantes con Vivienda**
- ✅ Seleccionar calle (solo calles asignadas al líder)
- ✅ Cargar viviendas dinámicamente según la calle
- ✅ Asignar habitante a vivienda
- ✅ Marcar como jefe de familia

### **2. Mensajes Flash**
- ✅ Mensaje de éxito al guardar
- ✅ Mensaje de error si falla
- ✅ Toast automático en la vista

### **3. Validaciones**
- ✅ Solo puede registrar en sus calles asignadas
- ✅ Validación de permisos en backend
- ✅ Mensajes claros de error

---

## 🧪 Flujo de Registro

```
1. Líder abre modal "Agregar Habitante"
   ↓
2. Llena datos personales
   ↓
3. Selecciona calle (solo sus calles)
   ↓
4. Sistema carga viviendas de esa calle (AJAX)
   ↓
5. Selecciona vivienda (opcional)
   ↓
6. Marca checkbox "Es Jefe de Familia" (opcional)
   ↓
7. Envía formulario
   ↓
8. Backend valida permisos
   ↓
9. Crea persona → habitante → habitante_vivienda
   ↓
10. Muestra mensaje de éxito con toast
   ↓
11. Recarga página con habitante agregado
```

---

## 📊 Estructura de Datos

### **Tablas Involucradas:**

```
persona
  └─ id_persona ──┐
                  │
habitante         │
  ├─ id_persona ──┘
  └─ id_habitante ──┐
                    │
habitante_vivienda  │
  ├─ id_habitante ──┘
  ├─ id_vivienda
  └─ es_jefe_familia (0 o 1)
```

---

## ✅ Checklist de Correcciones

- [x] Rutas de subadmin agregadas en `index.php`
- [x] Método `addHabitante()` corregido
- [x] Mensajes flash implementados
- [x] Checkbox jefe de familia agregado
- [x] Campo vivienda agregado
- [x] API endpoint para cargar viviendas
- [x] JavaScript para carga dinámica
- [x] Método `getViviendasPorCalle()` en modelo
- [x] Validaciones de permisos
- [x] Error en layout corregido

---

## 🚀 Cómo Probar

### **1. Registrar Habitante Normal:**
1. Ir a "Habitantes"
2. Click en "Agregar Habitante"
3. Llenar datos
4. Seleccionar calle
5. Seleccionar vivienda (opcional)
6. Guardar
7. Ver mensaje de éxito

### **2. Registrar Jefe de Familia:**
1. Seguir pasos anteriores
2. Marcar checkbox "Es Jefe de Familia"
3. Guardar
4. Verificar en base de datos: `habitante_vivienda.es_jefe_familia = 1`

### **3. Validar Permisos:**
1. Intentar seleccionar calle no asignada (no debe aparecer en dropdown)
2. Intentar enviar con calle no asignada (debe mostrar error)

---

¡Todas las correcciones implementadas! 🎉
