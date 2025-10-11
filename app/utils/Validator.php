<?php
// grupobrasil/app/utils/Validator.php

class Validator {

    /**
     * Verifica si una cadena está vacía o solo contiene espacios en blanco.
     * @param string $value La cadena a verificar.
     * @return bool True si la cadena está vacía o en blanco, false en caso contrario.
     */
    public static function isEmpty(?string $value): bool {
        return empty(trim($value ?? ''));
    }

    /**
     * Valida el formato de una cédula de identidad ecuatoriana.
     * Adapta esta lógica según el formato de CI/DNI de tu país.
     * Este es un ejemplo básico.
     * @param string $ci La cédula de identidad a validar.
     * @return bool True si el formato es válido, false en caso contrario.
     */
    public static function isValidCI(string $ci): bool {
        // Ejemplo de validación básica: 8 a 10 dígitos numéricos.
        // Puedes implementar una lógica de validación más robusta según el país (ej. algoritmo de dígitos verificadores)
        return preg_match('/^[0-9]{8,10}$/', $ci);
    }

    /**
     * Valida el formato de un correo electrónico.
     * @param string $email El correo electrónico a validar.
     * @return bool True si el formato es válido, false en caso contrario.
     */
    public static function isValidEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Valida si una fecha tiene el formato YYYY-MM-DD.
     * @param string $date La fecha a validar.
     * @return bool True si la fecha tiene el formato válido, false en caso contrario.
     */
    public static function isValidDate(string $date): bool {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }

    public static function isValidPassword(string $password): bool {
        // Mínimo 6 caracteres
        return strlen($password) >= 6;
    }
}
