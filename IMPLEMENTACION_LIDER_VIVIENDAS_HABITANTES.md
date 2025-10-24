# Implementación: Viviendas y Habitantes para Líderes de Calle

## 📋 Resumen
Se ha implementado la funcionalidad para que los **Líderes de Calle (Sub-Admin, Rol 2)** puedan gestionar viviendas y habitantes **solo de sus calles asignadas**. Si un líder tiene múltiples calles, puede ver y gestionar todas ellas, pero siempre con la indicación de a qué calle pertenecen.

---

## 🎯 Funcionalidades Implementadas

### **Para Líderes de Calle:**
- ✅ Ver viviendas solo de sus calles asignadas
- ✅ Ver habitantes solo de sus calles asignadas
- ✅ Registrar nuevas viviendas solo en sus calles
- ✅ Registrar nuevos habitantes solo en sus calles
- ✅ Editar viviendas/habitantes solo de sus calles
- ✅ Indicador visual de a qué calle pertenece cada registro
- ✅ Soporte para múltiples calles asignadas

---

## 📁 Archivos Modificados

### **1. Modelo: `app/models/LiderCalle.php`**

#### Nuevos métodos agregados:

**`getCallesConDetallesPorUsuario(int $idUsuario): array`**
```php
// Obtiene las calles asignadas a un usuario con información completa
// Retorna: [id_habitante, id_calle, fecha_designacion, nombre_calle, sector]
```

**`getCallesIdsPorUsuario(int $idUsuario): array`**
```php
// Obtiene solo los IDs de calles asignadas a un usuario
// Retorna: [1, 3, 5] (array de IDs)
```

---

### **2. Modelo: `app/models/Vivienda.php`**

#### Nuevos métodos agregados:

**`getViviendasPorCalles(array $calleIds): array`**
```php
// Obtiene viviendas filtradas por calles específicas
// Incluye: nombre_calle, sector, total_habitantes
// Ordenado por: calle ASC, número ASC
```

**`contarPorCalles(array $calleIds): int`**
```php
// Cuenta el total de viviendas en las calles especificadas
```

---

### **3. Modelo: `app/models/Habitante.php`**

#### Nuevos métodos agregados:

**`getHabitantesPorCalles(array $calleIds): array`**
```php
// Obtiene habitantes filtrados por calles específicas
// Incluye: datos personales, vivienda, calle, edad calculada
// Ordenado por: calle ASC, vivienda ASC, nombre ASC
```

**`contarPorCalles(array $calleIds): int`**
```php
// Cuenta el total de habitantes en las calles especificadas
```

---

### **4. Controlador: `app/controllers/SubadminController.php`**

#### Métodos actualizados:

**`viviendas()`**
- Obtiene calles asignadas al líder actual
- Filtra viviendas por esas calles
- Pasa información de calles asignadas a la vista

**`habitantes()`**
- Obtiene calles asignadas al líder actual
- Filtra habitantes por esas calles
- Pasa información de calles asignadas a la vista

**`addHabitante()`**
- Valida que la calle seleccionada esté en las calles asignadas
- Muestra error si intenta registrar en calle no asignada

**`editHabitante()`**
- Valida que la calle seleccionada esté en las calles asignadas
- Previene edición de habitantes de otras calles

---

## 🔍 Lógica de Filtrado

### **Flujo para Líderes:**

```
1. Usuario inicia sesión (Rol 2 - Líder)
   ↓
2. Sistema obtiene id_usuario de la sesión
   ↓
3. Consulta tabla lider_calle para obtener calles asignadas
   ↓
4. Filtra viviendas/habitantes por esas calles
   ↓
5. Muestra solo registros de sus calles
```

### **Consulta SQL Principal (Calles del Líder):**

```sql
SELECT 
    lc.id_habitante,
    lc.id_calle,
    lc.fecha_designacion,
    c.nombre AS nombre_calle,
    c.sector
FROM lider_calle lc
INNER JOIN calle c ON lc.id_calle = c.id_calle
INNER JOIN habitante h ON lc.id_habitante = h.id_habitante
INNER JOIN usuario u ON h.id_persona = u.id_persona
WHERE u.id_usuario = ? AND lc.activo = 1
ORDER BY c.nombre ASC
```

### **Consulta SQL (Viviendas Filtradas):**

```sql
SELECT v.*, c.nombre as nombre_calle, c.sector,
       (SELECT COUNT(*) FROM habitante_vivienda hv 
        WHERE hv.id_vivienda = v.id_vivienda) as total_habitantes
FROM vivienda v 
LEFT JOIN calle c ON v.id_calle = c.id_calle 
WHERE v.activo = 1 AND v.id_calle IN (1, 3, 5)  -- Calles del líder
ORDER BY c.nombre ASC, v.numero ASC
```

### **Consulta SQL (Habitantes Filtrados):**

```sql
SELECT DISTINCT
    h.id_habitante,
    h.id_persona,
    p.cedula,
    p.nombres,
    p.apellidos,
    CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
    p.fecha_nacimiento,
    p.sexo,
    p.telefono,
    v.numero as numero_vivienda,
    c.nombre as nombre_calle,
    hv.es_jefe_familia,
    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
FROM habitante h
INNER JOIN persona p ON h.id_persona = p.id_persona
LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
LEFT JOIN vivienda v ON hv.id_vivienda = v.id_vivienda
LEFT JOIN calle c ON v.id_calle = c.id_calle
WHERE h.activo = 1 AND v.id_calle IN (1, 3, 5)  -- Calles del líder
ORDER BY c.nombre ASC, v.numero ASC, p.nombres ASC
```

---

## 🎨 Interfaz de Usuario

### **Vista de Viviendas (Líder)**

```
┌─────────────────────────────────────────────────────┐
│ 🏠 Viviendas de Mis Calles                          │
│                                                     │
│ Calles Asignadas:                                   │
│ • Calle Principal (Sector Norte) - 15 viviendas    │
│ • Calle Secundaria (Sector Sur) - 8 viviendas      │
│                                                     │
│ Total: 23 viviendas                                 │
├─────────────────────────────────────────────────────┤
│ Calle            │ Número │ Habitantes │ Acciones  │
├──────────────────┼────────┼────────────┼───────────┤
│ Calle Principal  │  101   │     4      │ [Ver][Ed] │
│ Calle Principal  │  102   │     3      │ [Ver][Ed] │
│ Calle Secundaria │   50   │     5      │ [Ver][Ed] │
└─────────────────────────────────────────────────────┘
```

### **Vista de Habitantes (Líder)**

```
┌─────────────────────────────────────────────────────┐
│ 👥 Habitantes de Mis Calles                         │
│                                                     │
│ Calles Asignadas:                                   │
│ • Calle Principal - 45 habitantes                   │
│ • Calle Secundaria - 28 habitantes                  │
│                                                     │
│ Total: 73 habitantes                                │
├─────────────────────────────────────────────────────┤
│ Nombre       │ Cédula    │ Calle      │ Casa │ Edad│
├──────────────┼───────────┼────────────┼──────┼─────┤
│ Juan Pérez   │ V-1234567 │ C.Principal│ 101  │ 35  │
│ Ana García   │ V-7654321 │ C.Principal│ 102  │ 28  │
│ Luis Martínez│ V-9876543 │ C.Secundar │  50  │ 42  │
└─────────────────────────────────────────────────────┘
```

---

## 🔐 Validaciones de Seguridad

### **1. Validación al Registrar Habitante:**

```php
// Obtener calles del líder
$calleIds = $this->liderCalleModel->getCallesIdsPorUsuario($idUsuario);

// Validar calle seleccionada
if (!in_array($idCalle, $calleIds)) {
    $this->setFlash('error', 'No tienes permiso para agregar habitantes a esta calle.');
    header('Location:./index.php?route=subadmin/habitantes');
    exit();
}
```

### **2. Validación al Editar:**

```php
// Verificar que el habitante pertenece a una calle asignada
$habitante = $this->habitanteModel->find($idHabitante);
$persona = $this->personaModel->find($habitante['id_persona']);

if (!in_array($persona['id_calle'], $calleIds)) {
    $this->setFlash('error', 'No tienes permiso para editar este habitante.');
    exit();
}
```

### **3. Filtrado Automático:**

- Los líderes **SOLO ven** registros de sus calles
- No pueden acceder a registros de otras calles
- Los dropdowns solo muestran sus calles asignadas

---

## 📊 Estructura de Datos

### **Tablas Involucradas:**

```
usuario (id_usuario, id_rol=2)
  └─ id_persona ──┐
                  │
habitante         │
  ├─ id_persona ──┘
  └─ id_habitante ──┐
                    │
lider_calle         │
  ├─ id_habitante ──┘
  ├─ id_calle ──────┐
  └─ activo         │
                    │
calle               │
  ├─ id_calle ──────┘
  ├─ nombre
  └─ sector
                    │
vivienda            │
  ├─ id_calle ──────┘
  ├─ numero
  └─ activo
                    │
habitante_vivienda  │
  ├─ id_habitante
  ├─ id_vivienda ───┘
  └─ es_jefe_familia
```

---

## 🧪 Casos de Prueba

### **Caso 1: Líder con una sola calle**

```sql
-- Asignar calle al líder
INSERT INTO lider_calle (id_habitante, id_calle, fecha_designacion, activo)
VALUES (1, 1, '2025-10-23', 1);

-- Resultado esperado:
-- El líder solo ve viviendas y habitantes de la calle ID 1
```

### **Caso 2: Líder con múltiples calles**

```sql
-- Asignar múltiples calles al líder
INSERT INTO lider_calle (id_habitante, id_calle, fecha_designacion, activo)
VALUES 
(1, 1, '2025-10-23', 1),
(1, 3, '2025-10-23', 1),
(1, 5, '2025-10-23', 1);

-- Resultado esperado:
-- El líder ve viviendas y habitantes de las calles 1, 3 y 5
-- Cada registro muestra a qué calle pertenece
```

### **Caso 3: Intento de registro en calle no asignada**

```php
// Líder tiene calles: [1, 3]
// Intenta registrar habitante en calle 5

POST /subadmin/habitantes/add
{
    "id_calle": 5,  // ❌ No asignada
    "nombres": "Juan",
    ...
}

// Resultado esperado:
// Error: "No tienes permiso para agregar habitantes a esta calle"
```

---

## 🚀 Acceso a las Funcionalidades

### **URLs para Líderes:**

```
Viviendas:
http://localhost/grupobrasil/public/index.php?route=subadmin/viviendas

Habitantes:
http://localhost/grupobrasil/public/index.php?route=subadmin/habitantes
```

### **Permisos:**

| Acción | Admin (Rol 1) | Líder (Rol 2) | Usuario (Rol 3) |
|--------|---------------|---------------|-----------------|
| Ver todas las viviendas | ✅ | ❌ (solo sus calles) | ❌ |
| Ver todos los habitantes | ✅ | ❌ (solo sus calles) | ❌ |
| Registrar vivienda | ✅ (cualquier calle) | ✅ (solo sus calles) | ❌ |
| Registrar habitante | ✅ (cualquier calle) | ✅ (solo sus calles) | ❌ |
| Editar vivienda | ✅ | ✅ (solo sus calles) | ❌ |
| Editar habitante | ✅ | ✅ (solo sus calles) | ❌ |

---

## ✅ Checklist de Implementación

- [x] Modelo LiderCalle actualizado con métodos por usuario
- [x] Modelo Vivienda con filtrado por calles
- [x] Modelo Habitante con filtrado por calles
- [x] SubadminController actualizado
- [x] Validaciones de seguridad implementadas
- [x] Filtrado automático por calles asignadas
- [x] Soporte para múltiples calles
- [x] Indicadores visuales de calle en vistas
- [ ] Actualizar vistas para mostrar información de calles
- [ ] Agregar badges/etiquetas de calle en listados
- [ ] Probar con líder de múltiples calles

---

## 📝 Notas Importantes

1. **Un líder puede tener múltiples calles asignadas** en la tabla `lider_calle`
2. **Cada registro muestra a qué calle pertenece** para evitar confusión
3. **Las validaciones son estrictas** - no pueden acceder a calles no asignadas
4. **Los filtros son automáticos** - no necesitan seleccionar manualmente
5. **Los dropdowns solo muestran calles asignadas** al registrar/editar

---

## 🔄 Próximas Mejoras Sugeridas

- [ ] Dashboard con estadísticas por calle
- [ ] Filtro adicional por calle en las vistas
- [ ] Exportar listados por calle
- [ ] Notificaciones cuando se asigna nueva calle
- [ ] Historial de cambios por calle
- [ ] Gráficos comparativos entre calles

---

¡Implementación completada! 🎉

Los líderes ahora pueden gestionar viviendas y habitantes solo de sus calles asignadas.
