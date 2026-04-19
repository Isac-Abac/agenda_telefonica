<?php
session_start();
// Conectar con la base de datos y validar sesión.
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Acción enviada desde el formulario (agregar o eliminar contacto).
$action = $_POST['action'] ?? '';
$userId = $_SESSION['user_id'];
$_SESSION['contact_message'] = '';
$_SESSION['contact_message_type'] = 'success';

// Agregar nuevo contacto al usuario autenticado.
if ($action === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (!$nombre || !$telefono) {
        $_SESSION['contact_message'] = 'Completa nombre y teléfono para guardar el contacto.';
        $_SESSION['contact_message_type'] = 'error';
    } else {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        $stmt = $conn->prepare('INSERT INTO contactos (usuario_id, nombre, telefono, email, created_at) VALUES (?, ?, ?, ?, NOW())');
        if ($stmt) {
            $stmt->bind_param('isss', $userId, $nombre, $telefono, $email);
            if ($stmt->execute()) {
                $_SESSION['contact_message'] = 'Contacto guardado correctamente.';
                $_SESSION['contact_message_type'] = 'success';
            } else {
                $_SESSION['contact_message'] = 'No se pudo guardar el contacto. Intenta de nuevo.';
                $_SESSION['contact_message_type'] = 'error';
            }
            $stmt->close();
        } else {
            $_SESSION['contact_message'] = 'Error en la conexión con la base de datos.';
            $_SESSION['contact_message_type'] = 'error';
        }
    }
} elseif ($action === 'delete') {
    // Eliminar contacto solo si pertenece al usuario actual.
    $contactoId = intval($_POST['contacto_id'] ?? 0);
    if ($contactoId > 0) {
        $stmt = $conn->prepare('DELETE FROM contactos WHERE id = ? AND usuario_id = ?');
        if ($stmt) {
            $stmt->bind_param('ii', $contactoId, $userId);
            if ($stmt->execute()) {
                $_SESSION['contact_message'] = 'Contacto eliminado correctamente.';
                $_SESSION['contact_message_type'] = 'success';
            } else {
                $_SESSION['contact_message'] = 'No se pudo eliminar el contacto.';
                $_SESSION['contact_message_type'] = 'error';
            }
            $stmt->close();
        } else {
            $_SESSION['contact_message'] = 'Error en la conexión con la base de datos.';
            $_SESSION['contact_message_type'] = 'error';
        }
    } else {
        $_SESSION['contact_message'] = 'Contacto inválido.';
        $_SESSION['contact_message_type'] = 'error';
    }
} else {
    $_SESSION['contact_message'] = 'Acción no válida.';
    $_SESSION['contact_message_type'] = 'error';
}

header('Location: dashboard.php');
exit;
