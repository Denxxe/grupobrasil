<?php
// grupobrasil/app/helpers/AuthHelper.php
// Helper para verificar permisos y roles de usuarios

class AuthHelper {
    
    /**
     * Verifica si el usuario actual tiene un rol específico (primario o secundario)
     * @param int $id_rol ID del rol a verificar
     * @return bool True si el usuario tiene ese rol
     */
    public static function tieneRol(int $id_rol): bool {
        if (!isset($_SESSION['id_usuario'])) {
            return false;
        }
        
        // Verificar rol primario
        if (isset($_SESSION['id_rol']) && $_SESSION['id_rol'] == $id_rol) {
            return true;
        }
        
        // Verificar rol secundario
        if (isset($_SESSION['id_rol_secundario']) && $_SESSION['id_rol_secundario'] == $id_rol) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verifica si el usuario actual es Administrador (rol 1)
     * @return bool
     */
    public static function esAdmin(): bool {
        return self::tieneRol(1);
    }
    
    /**
     * Verifica si el usuario actual es Líder (rol 2)
     * @return bool
     */
    public static function esLider(): bool {
        return self::tieneRol(2);
    }
    
    /**
     * Verifica si el usuario actual es Jefe de Familia (rol 3)
     * @return bool
     */
    public static function esJefeFamilia(): bool {
        return self::tieneRol(3);
    }
    
    /**
     * Verifica si el usuario tiene al menos uno de los roles especificados
     * @param array $roles Array de IDs de roles
     * @return bool
     */
    public static function tieneAlgunRol(array $roles): bool {
        foreach ($roles as $rol) {
            if (self::tieneRol($rol)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Obtiene todos los roles del usuario actual
     * @return array Array con los IDs de roles
     */
    public static function getRoles(): array {
        $roles = [];
        
        if (isset($_SESSION['id_rol'])) {
            $roles[] = (int)$_SESSION['id_rol'];
        }
        
        if (isset($_SESSION['id_rol_secundario']) && !empty($_SESSION['id_rol_secundario'])) {
            $roles[] = (int)$_SESSION['id_rol_secundario'];
        }
        
        return $roles;
    }
    
    /**
     * Obtiene el nombre del rol primario
     * @return string
     */
    public static function getNombreRolPrimario(): string {
        return $_SESSION['nombre_rol'] ?? 'Usuario';
    }
    
    /**
     * Obtiene el nombre del rol secundario
     * @return string|null
     */
    public static function getNombreRolSecundario(): ?string {
        return $_SESSION['nombre_rol_secundario'] ?? null;
    }
    
    /**
     * Verifica si el usuario tiene permisos para acceder a una sección
     * @param string $seccion Nombre de la sección
     * @return bool
     */
    public static function puedeAcceder(string $seccion): bool {
        switch ($seccion) {
            case 'admin':
                return self::esAdmin();
                
            case 'gestion_usuarios':
                return self::tieneAlgunRol([1, 2]); // Admin o Líder
                
            case 'viviendas':
                return self::tieneAlgunRol([1, 2, 3]); // Todos
                
            case 'noticias':
                return self::tieneAlgunRol([1, 2]); // Admin o Líder
                
            case 'reportes':
                return self::tieneAlgunRol([1, 2]); // Admin o Líder
                
            case 'configuracion':
                return self::esAdmin(); // Solo Admin
                
            default:
                return false;
        }
    }
    
    /**
     * Redirige si el usuario no tiene el rol requerido
     * @param int $rol_requerido ID del rol requerido
     * @param string $redirect_url URL de redirección si no tiene permisos
     */
    public static function requiereRol(int $rol_requerido, string $redirect_url = './index.php?route=admin/dashboard'): void {
        if (!self::tieneRol($rol_requerido)) {
            $_SESSION['error_message'] = 'No tienes permisos para acceder a esta sección.';
            header('Location: ' . $redirect_url);
            exit();
        }
    }
    
    /**
     * Redirige si el usuario no tiene al menos uno de los roles requeridos
     * @param array $roles_requeridos Array de IDs de roles
     * @param string $redirect_url URL de redirección si no tiene permisos
     */
    public static function requiereAlgunRol(array $roles_requeridos, string $redirect_url = './index.php?route=admin/dashboard'): void {
        if (!self::tieneAlgunRol($roles_requeridos)) {
            $_SESSION['error_message'] = 'No tienes permisos para acceder a esta sección.';
            header('Location: ' . $redirect_url);
            exit();
        }
    }
}
