<?php
// Archivo: conexion.php
// Propósito: centralizar la conexión MySQL y asegurar columnas requeridas.

// Parámetros de conexión de la base de datos local.
$Servidor = "localhost";
$Usuario = "root";
$password = "";
$BaseDeDatos = "bd_usuarios";

// Crear conexión MySQL.
$conn = new mysqli($Servidor, $Usuario, $password, $BaseDeDatos);

// Validar conexión; detener ejecución si falla.
if ($conn->connect_error) {
    die("Conexion fallida: " . $conn->connect_error);
}

// Forzar UTF-8 para evitar problemas de caracteres.
$conn->set_charset('utf8mb4');

// Asegura que una columna exista en una tabla (migración ligera en runtime).
function ensure_column(mysqli $conn, string $table, string $column, string $definition): void
{
    // Saneamiento básico para evitar inyección en nombres de tabla/columna.
    $tableSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
    $columnSafe = preg_replace('/[^a-zA-Z0-9_]/', '', $column);
    if ($tableSafe === '' || $columnSafe === '') {
        return;
    }

    // Revisar si la columna ya existe.
    $sql = "SHOW COLUMNS FROM `$tableSafe` LIKE '$columnSafe'";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        return;
    }

    // Crear columna cuando no existe.
    $conn->query("ALTER TABLE `$tableSafe` ADD COLUMN `$columnSafe` $definition");
}

// Migraciones mínimas para campos usados por la app actual.
ensure_column($conn, 'usuarios', 'foto_perfil', 'VARCHAR(255) NULL');
ensure_column($conn, 'contactos', 'foto_contacto', 'VARCHAR(255) NULL');
ensure_column($conn, 'contactos', 'parentesco', 'VARCHAR(60) NULL');

?>
