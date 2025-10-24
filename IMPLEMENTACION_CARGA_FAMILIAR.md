# Implementación de Carga Familiar

## 📋 Resumen
Se ha implementado la funcionalidad para que los usuarios puedan ver su **carga familiar** en el sistema. Esta funcionalidad está disponible para **todos los roles** (Admin, Sub-Admin, Usuario Común), pero **solo se muestra si el usuario es jefe de familia**.

---

## 🎯 Funcionalidad

### **Características:**
- ✅ Disponible para todos los roles (Admin, Líder, Jefe de Familia)
- ✅ Solo se muestra si el usuario es jefe de familia (`es_jefe_familia = 1`)
- ✅ Muestra información completa de cada miembro de la familia
- ✅ Incluye estadísticas (total miembros, hombres, mujeres)
- ✅ Diseño responsive y moderno

### **Datos Mostrados:**
- Cédula
- Nombre completo
- Parentesco
- Edad (calculada automáticamente)
- Sexo
- Teléfono
- Fecha de registro

---

## 📁 Archivos Modificados/Creados

### **1. Modelo: `app/models/CargaFamiliar.php`**

#### Nuevos métodos agregados:

**`getCargaFamiliarConDatos(int $jefeId): array`**
- Obtiene la carga familiar con información completa de las personas
- Hace JOIN con las tablas `habitante` y `persona`
- Calcula la edad automáticamente
- Ordena por fecha de nacimiento

**`getCargaFamiliarPorUsuario(int $idUsuario)`**
- Obtiene la carga familiar a través del ID de usuario
- Verifica si el usuario es jefe de familia
- Retorna `false` si no es jefe de familia
- Retorna array con miembros si es jefe de familia

---

### **2. Controlador: `app/controllers/AdminController.php`**

#### Cambios:
- ✅ Agregado `require_once` para `CargaFamiliar.php`
- ✅ Agregada propiedad `$cargaFamiliarModel`
- ✅ Actualizado constructor para recibir `CargaFamiliar`

#### Nuevo método:

**`cargaFamiliar()`**
```php
public function cargaFamiliar() {
    $idUsuario = $_SESSION['id_usuario'] ?? null;
    
    // Obtener carga familiar del usuario
    $cargaFamiliar = $this->cargaFamiliarModel->getCargaFamiliarPorUsuario($idUsuario);
    
    // Verificar si es jefe de familia
    $esJefeFamilia = $cargaFamiliar !== false;
    
    $data = [
        'page_title' => 'Mi Carga Familiar',
        'carga_familiar' => $cargaFamiliar ?: [],
        'es_jefe_familia' => $esJefeFamilia,
        'total_miembros' => $esJefeFamilia ? count($cargaFamiliar) : 0
    ];
    
    $this->renderAdminView('carga_familiar/index', $data);
}
```

---

### **3. Routing: `public/index.php`**

#### Cambios:
- ✅ Instanciado `CargaFamiliar` model
- ✅ Pasado al constructor de `AdminController`
- ✅ Agregada ruta `admin/carga-familiar`

```php
elseif ($actionSegment === 'carga-familiar') {
    $actionName = 'cargaFamiliar';
}
```

---

### **4. Vista: `app/views/admin/carga_familiar/index.php`** - NUEVO

Vista completa que muestra:
- Mensaje informativo si no es jefe de familia
- Estadísticas en cards (total miembros, hombres, mujeres)
- Tabla con listado de miembros
- Iconos y badges para mejor visualización
- Diseño responsive

---

### **5. Layout: `app/views/layouts/admin_layout.php`**

#### Cambios:
- ✅ Agregado enlace "Mi Carga Familiar" en el sidebar
- ✅ Icono: `fa-user-friends`
- ✅ Ruta: `./index.php?route=admin/carga-familiar`

---

## 🔍 Lógica de Verificación

### **¿Cómo se determina si un usuario es jefe de familia?**

1. Se obtiene el `id_habitante` del usuario a través de la tabla `usuario` → `habitante`
2. Se verifica en la tabla `habitante_vivienda` si `es_jefe_familia = 1`
3. Si es jefe de familia, se obtienen los miembros de la tabla `carga_familiar` donde `id_jefe = id_habitante`

### **Consulta SQL principal:**

```sql
SELECT 
    cf.id_carga,
    cf.id_habitante,
    cf.id_jefe,
    cf.parentesco,
    cf.fecha_registro,
    h.id_persona,
    p.cedula,
    p.nombres,
    p.apellidos,
    CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
    p.fecha_nacimiento,
    p.sexo,
    p.telefono,
    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
FROM carga_familiar cf
INNER JOIN habitante h ON cf.id_habitante = h.id_habitante
INNER JOIN persona p ON h.id_persona = p.id_persona
WHERE cf.id_jefe = ? AND cf.activo = 1
ORDER BY p.fecha_nacimiento ASC
```

---

## 🎨 Interfaz de Usuario

### **Caso 1: Usuario NO es jefe de familia**
```
┌─────────────────────────────────────────┐
│ ℹ️ No eres jefe de familia              │
│                                         │
│ Esta sección solo está disponible      │
│ para usuarios que son jefes de familia.│
└─────────────────────────────────────────┘
```

### **Caso 2: Usuario ES jefe de familia SIN miembros**
```
┌─────────────────────────────────────────┐
│ 👥 Mi Carga Familiar          0         │
│                            Miembros     │
├─────────────────────────────────────────┤
│                                         │
│        👥                               │
│   No tienes miembros registrados       │
│                                         │
└─────────────────────────────────────────┘
```

### **Caso 3: Usuario ES jefe de familia CON miembros**
```
┌─────────────────────────────────────────┐
│ 👥 Mi Carga Familiar          3         │
│                            Miembros     │
├─────────────────────────────────────────┤
│ Listado de Miembros                     │
├───┬─────────┬──────────┬──────────┬────┤
│ # │ Cédula  │ Nombre   │Parentesco│Edad│
├───┼─────────┼──────────┼──────────┼────┤
│ 1 │V-1234567│Juan Pérez│   Hijo   │ 15 │
│ 2 │V-7654321│Ana Pérez │   Hija   │ 12 │
│ 3 │V-9876543│Luis Pérez│   Hijo   │  8 │
└───┴─────────┴──────────┴──────────┴────┘

┌─────────┬─────────┬─────────┐
│    3    │    2    │    1    │
│ Total   │ Hombres │ Mujeres │
└─────────┴─────────┴─────────┘
```

---

## 🧪 Pruebas

### **1. Verificar si un usuario es jefe de familia**

```sql
SELECT 
    u.id_usuario,
    u.username,
    h.id_habitante,
    hv.es_jefe_familia
FROM usuario u
INNER JOIN habitante h ON u.id_persona = h.id_persona
LEFT JOIN habitante_vivienda hv ON h.id_habitante = hv.id_habitante
WHERE u.id_usuario = 2;
```

### **2. Ver carga familiar de un jefe**

```sql
SELECT 
    cf.*,
    CONCAT(p.nombres, ' ', p.apellidos) AS nombre_completo,
    p.cedula,
    TIMESTAMPDIFF(YEAR, p.fecha_nacimiento, CURDATE()) AS edad
FROM carga_familiar cf
INNER JOIN habitante h ON cf.id_habitante = h.id_habitante
INNER JOIN persona p ON h.id_persona = p.id_persona
WHERE cf.id_jefe = 1 AND cf.activo = 1;
```

### **3. Asignar un usuario como jefe de familia**

```sql
-- Primero obtener el id_habitante del usuario
SELECT h.id_habitante 
FROM usuario u
INNER JOIN habitante h ON u.id_persona = h.id_persona
WHERE u.id_usuario = 3;

-- Luego marcar como jefe de familia
UPDATE habitante_vivienda 
SET es_jefe_familia = 1 
WHERE id_habitante = 2;
```

---

## 🔐 Permisos y Acceso

### **¿Quién puede ver la carga familiar?**

| Rol | Puede Acceder | Condición |
|-----|---------------|-----------|
| Administrador (1) | ✅ Sí | Solo si es jefe de familia |
| Líder (2) | ✅ Sí | Solo si es jefe de familia |
| Jefe de Familia (3) | ✅ Sí | Solo si es jefe de familia |

**Nota:** Aunque un usuario tenga rol de Admin o Líder, solo verá su carga familiar si además está marcado como jefe de familia en `habitante_vivienda.es_jefe_familia = 1`.

---

## 📊 Estructura de Datos

### **Tablas Involucradas:**

```
usuario
  └─ id_persona ──┐
                  │
habitante         │
  ├─ id_persona ──┘
  └─ id_habitante ──┐
                    │
habitante_vivienda  │
  ├─ id_habitante ──┤
  └─ es_jefe_familia│
                    │
carga_familiar      │
  ├─ id_jefe ───────┘
  └─ id_habitante ──┐
                    │
habitante           │
  └─ id_persona ────┤
                    │
persona             │
  └─ (datos) ───────┘
```

---

## 🚀 Acceso a la Funcionalidad

### **URL:**
```
http://localhost/grupobrasil/public/index.php?route=admin/carga-familiar
```

### **Enlace en el Sidebar:**
- Ubicación: Entre "Viviendas" y "Noticias"
- Icono: 👥 (fa-user-friends)
- Texto: "Mi Carga Familiar"

---

## ✅ Checklist de Implementación

- [x] Modelo CargaFamiliar actualizado
- [x] Métodos `getCargaFamiliarConDatos()` y `getCargaFamiliarPorUsuario()` creados
- [x] AdminController actualizado con método `cargaFamiliar()`
- [x] Ruta agregada en `public/index.php`
- [x] Vista `carga_familiar/index.php` creada
- [x] Enlace agregado al sidebar
- [x] Diseño responsive implementado
- [x] Estadísticas de miembros incluidas
- [x] Validación de jefe de familia implementada

---

## 📝 Notas Importantes

1. **La funcionalidad está disponible para TODOS los roles**, no solo para jefes de familia
2. **Se muestra un mensaje informativo** si el usuario no es jefe de familia
3. **No requiere permisos especiales** - cualquier usuario autenticado puede acceder
4. **La edad se calcula automáticamente** usando `TIMESTAMPDIFF` en SQL
5. **Los datos se obtienen en tiempo real** de la base de datos

---

## 🔄 Próximas Mejoras Sugeridas

- [ ] Agregar filtros por parentesco
- [ ] Exportar listado a PDF
- [ ] Agregar gráficos estadísticos
- [ ] Permitir editar información de miembros (solo admin)
- [ ] Agregar fotos de perfil de los miembros
- [ ] Historial de cambios en la carga familiar

---

¡Implementación completada! 🎉
