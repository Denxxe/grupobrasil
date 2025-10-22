<?php
// grupobrasil/app/models/Categoria.php

require_once 'ModelBase.php'; // Asegúrate de que la ruta sea correcta

class Categoria extends ModelBase {
    public function __construct() {
        parent::__construct();
        $this->table = 'categorias'; // Nombre de tu tabla de categorías
        $this->primaryKey = 'id_categoria'; // Clave primaria
    }

    public function getAllCategories() {
        // CORRECCIÓN: Se cambia 'nombre_categoria' por 'nombre'
        $sql = "SELECT id_categoria, nombre FROM " . $this->table . " ORDER BY nombre ASC";
        $stmt = $this->conn->prepare($sql); // Línea 15 donde se generaba el error

        if ($stmt === false) {
            error_log("Error al preparar la consulta getAllCategories: " . $this->conn->error);
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $categorias = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categorias[] = $row;
            }
            $result->free();
        } else {
            error_log("Error al ejecutar getAllCategories: " . $stmt->error);
        }
        $stmt->close();
        return $categorias;
    }

    // Aquí podrías añadir otros métodos como getById, create, update, deleteCategory si los necesitas
}