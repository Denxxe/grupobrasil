# Implementación de Rol Secundario para Usuarios

## 📋 Resumen
Se ha implementado un sistema de **rol secundario** que permite que un usuario tenga dos roles simultáneamente. Esto es útil para casos donde un **Líder de Calle** también necesita permisos de **Jefe de Familia**.

---

## 🗄️ Cambios en la Base de Datos

### 1. Ejecutar la migración SQL

Ejecuta este SQL en phpMyAdmin o tu cliente MySQL:

```sql
-- Agregar columna id_rol_secundario
ALTER TABLE `usuario` 
ADD COLUMN `id_rol_secundario` int(10) UNSIGNED DEFAULT NULL COMMENT 'Rol secundario opcional (ej: Líder que también es Jefe de Familia)' 
AFTER `id_rol`;

-- Agregar foreign key constraint
ALTER TABLE `usuario`
ADD CONSTRAINT `fk_usuario_rol_secundario` 
FOREIGN KEY (`id_rol_secundario`) REFERENCES `rol`(`id_rol`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- Actualizar comentario del rol primario
ALTER TABLE `usuario` 
MODIFY COLUMN `id_rol` int(10) UNSIGNED DEFAULT NULL COMMENT 'Rol principal del usuario';
```

---

## 📁 Archivos Modificados

### 1. **Modelo Usuario** (`app/models/Usuario.php`)

#### Cambios en `getBaseQuery()`:
- Ahora incluye JOINs con la tabla `rol` para obtener nombres de roles
- Agrega campos: `nombre_completo`, `nombre_rol`, `nombre_rol_secundario`

#### Nuevos métodos:

**`tieneRol(int $id_usuario, int $id_rol): bool`**
- Verifica si un usuario tiene un rol específico (primario o secundario)

**`getRolesUsuario(int $id_usuario): array`**
- Obtiene todos los roles de un usuario

**`asignarRolSecundario(int $id_usuario, ?int $id_rol_secundario): bool`**
- Asigna o remueve un rol secundario a un usuario

---

### 2. **LoginController** (`app/controllers/LoginController.php`)

#### Cambios en el login:
Ahora guarda en sesión:
```php
$_SESSION['id_rol_secundario'] = $user['id_rol_secundario'] ?? null;
$_SESSION['nombre_rol'] = $user['nombre_rol'] ?? 'Usuario';
$_SESSION['nombre_rol_secundario'] = $user['nombre_rol_secundario'] ?? null;
```

---

### 3. **AuthHelper** (`app/helpers/AuthHelper.php`) - NUEVO

Helper para verificar permisos de forma sencilla:

```php
// Verificar si tiene un rol específico
AuthHelper::tieneRol(2); // ¿Es líder?

// Verificar roles específicos
AuthHelper::esAdmin();
AuthHelper::esLider();
AuthHelper::esJefeFamilia();

// Verificar si tiene al menos uno de varios roles
AuthHelper::tieneAlgunRol([1, 2]); // ¿Es Admin O Líder?

// Obtener todos los roles del usuario
$roles = AuthHelper::getRoles(); // [2, 3] si es Líder y Jefe de Familia

// Requerir un rol (redirige si no lo tiene)
AuthHelper::requiereRol(1); // Solo admins
AuthHelper::requiereAlgunRol([1, 2]); // Admins o Líderes
```

---

## 💻 Uso en el Código

### Ejemplo 1: Verificar permisos en un controlador

```php
<?php
require_once __DIR__ . '/../helpers/AuthHelper.php';

class MiControlador {
    public function accionSoloLideres() {
        // Redirige si no es líder
        AuthHelper::requiereRol(2);
        
        // Código para líderes...
    }
    
    public function accionLideresOJefes() {
        // Redirige si no es líder NI jefe de familia
        AuthHelper::requiereAlgunRol([2, 3]);
        
        // Código para líderes o jefes...
    }
}
```

### Ejemplo 2: Mostrar contenido según roles en vistas

```php
<?php require_once __DIR__ . '/../../helpers/AuthHelper.php'; ?>

<!-- Solo para administradores -->
<?php if (AuthHelper::esAdmin()): ?>
    <button class="btn btn-danger">Eliminar Usuario</button>
<?php endif; ?>

<!-- Para líderes o jefes de familia -->
<?php if (AuthHelper::tieneAlgunRol([2, 3])): ?>
    <div class="panel-gestion">
        <!-- Contenido -->
    </div>
<?php endif; ?>

<!-- Mostrar roles del usuario -->
<p>Rol principal: <?= AuthHelper::getNombreRolPrimario() ?></p>
<?php if (AuthHelper::getNombreRolSecundario()): ?>
    <p>Rol secundario: <?= AuthHelper::getNombreRolSecundario() ?></p>
<?php endif; ?>
```

### Ejemplo 3: Asignar rol secundario

```php
<?php
require_once __DIR__ . '/../models/Usuario.php';

$usuarioModel = new Usuario();

// Asignar rol secundario (Jefe de Familia) a un Líder
$id_usuario = 4; // Usuario que es Líder (rol 2)
$usuarioModel->asignarRolSecundario($id_usuario, 3); // Agregar rol de Jefe de Familia

// Remover rol secundario
$usuarioModel->asignarRolSecundario($id_usuario, null);

// Verificar si tiene un rol
if ($usuarioModel->tieneRol($id_usuario, 3)) {
    echo "Este usuario es Jefe de Familia";
}

// Obtener todos los roles
$roles = $usuarioModel->getRolesUsuario($id_usuario);
// Resultado: [2, 3] si tiene ambos roles
```

---

## 🎯 Casos de Uso

### Caso 1: Líder que también es Jefe de Familia

```sql
-- Usuario con id_rol = 2 (Líder) que también necesita permisos de Jefe de Familia
UPDATE `usuario` 
SET `id_rol_secundario` = 3 
WHERE `id_usuario` = 4;
```

**Resultado:**
- Puede acceder a funciones de Líder (gestionar calles, ver reportes)
- Puede acceder a funciones de Jefe de Familia (gestionar su vivienda, familia)

### Caso 2: Jefe de Familia que se convierte en Líder

**Opción A:** Cambiar rol principal
```sql
UPDATE `usuario` 
SET `id_rol` = 2, `id_rol_secundario` = 3 
WHERE `id_usuario` = 5;
```

**Opción B:** Mantener como Jefe y agregar permisos de Líder
```sql
UPDATE `usuario` 
SET `id_rol_secundario` = 2 
WHERE `id_usuario` = 5 AND `id_rol` = 3;
```

---

## 🔐 Sistema de Permisos

### Roles Definidos:
- **Rol 1**: Administrador Principal (acceso total)
- **Rol 2**: Sub-Administrador / Líder (gestión limitada)
- **Rol 3**: Miembro de Comunidad / Jefe de Familia (acceso básico)

### Matriz de Permisos:

| Sección | Admin (1) | Líder (2) | Jefe Familia (3) |
|---------|-----------|-----------|------------------|
| Dashboard | ✅ | ✅ | ✅ |
| Gestión Usuarios | ✅ | ✅ | ❌ |
| Viviendas | ✅ | ✅ | ✅ (solo su vivienda) |
| Noticias (crear/editar) | ✅ | ✅ | ❌ |
| Reportes | ✅ | ✅ | ❌ |
| Configuración | ✅ | ❌ | ❌ |

---

## ✅ Checklist de Implementación

- [x] Crear migración SQL
- [x] Actualizar modelo Usuario
- [x] Crear AuthHelper
- [x] Actualizar LoginController
- [ ] **Ejecutar migración SQL en la base de datos** ⚠️
- [ ] Actualizar vistas para mostrar rol secundario
- [ ] Actualizar formularios de edición de usuario
- [ ] Probar asignación de roles secundarios

---

## 🧪 Pruebas

### 1. Asignar rol secundario a un usuario existente

```sql
-- Ver usuarios actuales
SELECT id_usuario, id_persona, id_rol, id_rol_secundario, username 
FROM usuario;

-- Asignar rol secundario al usuario ID 4 (si es líder)
UPDATE usuario 
SET id_rol_secundario = 3 
WHERE id_usuario = 4;

-- Verificar
SELECT u.id_usuario, u.username, 
       r1.nombre as rol_principal, 
       r2.nombre as rol_secundario
FROM usuario u
LEFT JOIN rol r1 ON u.id_rol = r1.id_rol
LEFT JOIN rol r2 ON u.id_rol_secundario = r2.id_rol
WHERE u.id_usuario = 4;
```

### 2. Probar en código PHP

```php
<?php
session_start();
require_once 'app/helpers/AuthHelper.php';

// Simular sesión de usuario con rol secundario
$_SESSION['id_usuario'] = 4;
$_SESSION['id_rol'] = 2; // Líder
$_SESSION['id_rol_secundario'] = 3; // Jefe de Familia

// Probar verificaciones
var_dump(AuthHelper::esLider()); // true
var_dump(AuthHelper::esJefeFamilia()); // true
var_dump(AuthHelper::getRoles()); // [2, 3]
```

---

## 📝 Notas Importantes

1. **El rol secundario es opcional**: Si `id_rol_secundario` es `NULL`, el usuario solo tiene su rol principal
2. **No hay límite de combinaciones**: Cualquier rol puede ser secundario de cualquier otro
3. **Cascada en eliminación**: Si se elimina un rol de la tabla `rol`, el `id_rol_secundario` se pone en `NULL` automáticamente
4. **Sesión actualizada**: El rol secundario se guarda en la sesión al hacer login

---

## 🚀 Próximos Pasos

1. **Ejecutar la migración SQL** en la base de datos
2. Actualizar el formulario de edición de usuarios para permitir asignar rol secundario
3. Mostrar ambos roles en la interfaz de usuario
4. Actualizar la lógica de permisos en los controladores usando `AuthHelper`

---

## 📞 Soporte

Si tienes dudas sobre la implementación, revisa:
- `app/models/Usuario.php` - Métodos del modelo
- `app/helpers/AuthHelper.php` - Helper de autenticación
- `database/migrations/add_rol_secundario.sql` - Script SQL
