<?php
// grupobrasil/config/database.php

class Database {
    private static $instance = null; // Almacena la única instancia de Database
    private $conn; // Propiedad para almacenar la conexión

    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $db_name = 'grupobrasil_db';

    // Constructor privado para evitar que se creen instancias directamente
    private function __construct() {
        $this->conn = new mysqli($this->host, $this->user, $this->pass, $this->db_name);

        if ($this->conn->connect_error) {
            die("Error de conexión a la base de datos: " . $this->conn->connect_error);
        }

        $this->conn->set_charset("utf8mb4");
    }

    // Método estático para obtener la única instancia de Database
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Método para obtener la conexión mysqli desde la instancia única
    public function getConnection() {
        return $this->conn;
    }

    // Método para cerrar la conexión (opcional, ya que Singleton mantendrá la conexión abierta)
    // Puedes llamarlo explícitamente al final de tu aplicación si lo deseas,
    // pero el destructor automático de PHP la cerrará al final de la ejecución del script.
    public function closeConnection() {
        if ($this->conn && !$this->conn->connect_error) { // Solo cerrar si está abierta
            $this->conn->close();
        }
    }

    // El destructor ya no es tan crítico con Singleton, pero lo mantenemos para seguridad
    // en caso de que la instancia se elimine explícitamente.
    public function __destruct() {
        $this->closeConnection();
    }
}