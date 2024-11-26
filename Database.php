<?php
// Database.php

class Database {
    private $connection;
    private static $instance = null;

    private function __construct() {
        try {
            $config = getConfig('DB_CONFIG');
            $this->connection = new mysqli(
                $config['host'],
                $config['username'],
                $config['password'],
                $config['database']
            );

            if ($this->connection->connect_error) {
                throw new Exception("Error de conexión: " . $this->connection->connect_error);
            }

            $this->connection->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log("Error de base de datos: " . $e->getMessage());
            throw $e;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function insertCliente($nombre, $email, $telefono, $pipedrive_id = null) {
        try {
            // Escapar valores para prevenir SQL injection
            $nombre = $this->connection->real_escape_string($nombre);
            $email = $this->connection->real_escape_string($email);
            $telefono = $this->connection->real_escape_string($telefono);
            $pipedrive_id = $pipedrive_id ? (int)$pipedrive_id : 'NULL';

            $sql = "INSERT INTO clientes (nombre, email, telefono, pipedrive_id, fecha_registro) 
                    VALUES ('$nombre', '$email', '$telefono', $pipedrive_id, NOW())";

            if (!$this->connection->query($sql)) {
                throw new Exception("Error al insertar cliente: " . $this->connection->error);
            }

            return $this->connection->insert_id;
        } catch (Exception $e) {
            error_log("Error en insertCliente: " . $e->getMessage());
            throw $e;
        }
    }

    public function getClienteByEmail($email) {
        $email = $this->connection->real_escape_string($email);
        $result = $this->connection->query("SELECT * FROM clientes WHERE email = '$email' LIMIT 1");
        return $result ? $result->fetch_assoc() : null;
    }

    public function __destruct() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
}
?>