-- Archivo: create_tables.sql
-- Propósito: crear estructuras base de la agenda telefónica.

-- Crear base de datos principal.
CREATE DATABASE IF NOT EXISTS bd_usuarios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bd_usuarios;

-- Tabla de usuarios.
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL,
    INDEX (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Consulta de apoyo para ver usuarios registrados (opcional).
SELECT * FROM usuarios;

-- Tabla de contactos por usuario.
CREATE TABLE IF NOT EXISTS contactos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL,
    telefono VARCHAR(60) NOT NULL,
    email VARCHAR(120) DEFAULT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX (usuario_id),
    INDEX (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migración histórica: agrega código postal/prefijo si no existía.
ALTER TABLE contactos ADD COLUMN codigo_postal INT NOT NULL DEFAULT 10 AFTER telefono;

-- Consulta de apoyo para validar contactos (opcional).
SELECT * FROM contactos;
