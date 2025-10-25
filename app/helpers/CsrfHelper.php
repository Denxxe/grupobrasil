<?php
// grupobrasil/app/helpers/CsrfHelper.php

class CsrfHelper {
    // Genera (si no existe) y devuelve el token CSRF de sesión
    public static function getToken(): string {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    // Devuelve el input HTML para incluir en formularios
    public static function getTokenInput(): string {
        $token = self::getToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    // Valida un token enviado por POST/Request
    public static function validateToken(?string $token): bool {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token']) || empty($token)) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}
