<?php
// Archivo: contact_action.php
// Controlador de acciones sobre contactos y foto de perfil.

session_start();
require_once __DIR__ . '/conexion.php';
require_once __DIR__ . '/upload_helper.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$action = $_POST['action'] ?? '';
$userId = (int)$_SESSION['user_id'];
$_SESSION['contact_message'] = '';
$_SESSION['contact_message_type'] = 'success';

// Normaliza teléfono dejando únicamente dígitos.
$sanitizePhone = static function (string $phone): string {
    return preg_replace('/\D+/', '', $phone) ?? '';
};

// Valida prefijo internacional simple (1 a 999).
$validateDialCode = static function ($dialCode): bool {
    return ctype_digit((string)$dialCode) && (int)$dialCode >= 1 && (int)$dialCode <= 999;
};

// Reglas de longitud de número local por prefijo.
$phoneLengthByDialCode = [
    '502' => ['min' => 8, 'max' => 8],
    '1' => ['min' => 10, 'max' => 10],
    '52' => ['min' => 10, 'max' => 10],
    '34' => ['min' => 9, 'max' => 9],
    '57' => ['min' => 10, 'max' => 10],
    '54' => ['min' => 10, 'max' => 10],
    '51' => ['min' => 9, 'max' => 9],
    '56' => ['min' => 9, 'max' => 9],
    '593' => ['min' => 9, 'max' => 9],
    '58' => ['min' => 10, 'max' => 10],
    '55' => ['min' => 10, 'max' => 11],
    '49' => ['min' => 10, 'max' => 11],
    '33' => ['min' => 9, 'max' => 9],
    '39' => ['min' => 9, 'max' => 10],
    '44' => ['min' => 10, 'max' => 10],
    '81' => ['min' => 10, 'max' => 10]
];

$validatePhoneLengthByPrefix = static function (string $dialCode, string $phone) use ($phoneLengthByDialCode): bool {
    $len = strlen($phone);
    $rule = $phoneLengthByDialCode[$dialCode] ?? ['min' => 6, 'max' => 15];
    return $len >= (int)$rule['min'] && $len <= (int)$rule['max'];
};

$phoneLengthMessage = static function (string $dialCode) use ($phoneLengthByDialCode): string {
    $rule = $phoneLengthByDialCode[$dialCode] ?? ['min' => 6, 'max' => 15];
    $min = (int)$rule['min'];
    $max = (int)$rule['max'];
    if ($min === $max) {
        return 'El teléfono debe tener exactamente ' . $min . ' dígitos para el prefijo seleccionado.';
    }
    return 'El teléfono debe tener entre ' . $min . ' y ' . $max . ' dígitos para el prefijo seleccionado.';
};

// Resuelve el valor final de parentesco (incluyendo opción "Otro").
$resolveParentesco = static function (string $selected, string $otherValue, ?string &$error = null): ?string {
    $error = null;
    $selected = trim($selected);
    $otherValue = trim($otherValue);

    if ($selected === '') {
        return null;
    }

    $allowed = ['Padre', 'Madre', 'Hijo', 'Hija', 'Hermano', 'Hermana', 'Pareja', 'Amigo', 'Trabajo', 'Otro'];
    if (!in_array($selected, $allowed, true)) {
        $error = 'El parentesco seleccionado no es válido.';
        return null;
    }

    if ($selected === 'Otro') {
        if ($otherValue === '') {
            $error = 'Escribe el parentesco cuando selecciones la opción Otro.';
            return null;
        }
        if (strlen($otherValue) > 30) {
            $error = 'El parentesco personalizado permite máximo 30 caracteres.';
            return null;
        }
        return $otherValue;
    }

    return $selected;
};

// Comprueba si ya existe nombre o número repetido para el usuario.
$hasDuplicateContact = static function (mysqli $conn, int $userId, string $nombre, int $dialCodeInt, string $telefono, int $excludeId = 0): bool {
    $sql = 'SELECT id FROM contactos WHERE usuario_id = ? AND (LOWER(nombre) = LOWER(?) OR (codigo_postal = ? AND telefono = ?)) AND (? = 0 OR id <> ?) LIMIT 1';
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false;
    }

    $stmt->bind_param('isisii', $userId, $nombre, $dialCodeInt, $telefono, $excludeId, $excludeId);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
};

// Acción: actualizar foto de perfil.
if ($action === 'update_profile_photo') {
    $uploadError = null;
    $newPhotoPath = agenda_upload_image('foto_perfil', 'users', $uploadError);

    if ($uploadError !== null) {
        $_SESSION['contact_message'] = $uploadError ?: 'No se pudo actualizar la foto de perfil.';
        $_SESSION['contact_message_type'] = 'error';
    } elseif ($newPhotoPath === null) {
        $_SESSION['contact_message'] = 'Selecciona una imagen para tu perfil.';
        $_SESSION['contact_message_type'] = 'error';
    } else {
        $oldPhotoPath = null;
        $selectStmt = $conn->prepare('SELECT foto_perfil FROM usuarios WHERE id = ?');
        if ($selectStmt) {
            $selectStmt->bind_param('i', $userId);
            $selectStmt->execute();
            $selectStmt->bind_result($oldPhotoPath);
            $selectStmt->fetch();
            $selectStmt->close();
        }

        $updateStmt = $conn->prepare('UPDATE usuarios SET foto_perfil = ? WHERE id = ?');
        if ($updateStmt) {
            $updateStmt->bind_param('si', $newPhotoPath, $userId);
            if ($updateStmt->execute()) {
                $_SESSION['contact_message'] = 'Foto de perfil actualizada.';
                if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                    agenda_delete_image($oldPhotoPath);
                }
            } else {
                $_SESSION['contact_message'] = 'No se pudo guardar la foto de perfil.';
                $_SESSION['contact_message_type'] = 'error';
                agenda_delete_image($newPhotoPath);
            }
            $updateStmt->close();
        } else {
            $_SESSION['contact_message'] = 'Error en la conexión con la base de datos.';
            $_SESSION['contact_message_type'] = 'error';
            agenda_delete_image($newPhotoPath);
        }
    }
// Acción: crear contacto.
} elseif ($action === 'add') {
    $nombre = trim($_POST['nombre'] ?? '');
    $codigoPostal = trim((string)($_POST['codigo_postal'] ?? ''));
    $telefono = $sanitizePhone(trim($_POST['telefono'] ?? ''));
    $parentescoInput = trim($_POST['parentesco'] ?? '');
    $parentescoOtroInput = trim($_POST['parentesco_otro'] ?? '');
    $parentescoError = null;
    $parentesco = $resolveParentesco($parentescoInput, $parentescoOtroInput, $parentescoError);
    $email = trim($_POST['email'] ?? '');

    if ($nombre === '' || $codigoPostal === '' || $telefono === '') {
        $_SESSION['contact_message'] = 'Completa nombre, código de país y teléfono para guardar el contacto.';
        $_SESSION['contact_message_type'] = 'error';
    } elseif (!$validateDialCode($codigoPostal)) {
        $_SESSION['contact_message'] = 'El prefijo de país no es válido.';
        $_SESSION['contact_message_type'] = 'error';
    } elseif (!$validatePhoneLengthByPrefix($codigoPostal, $telefono)) {
        $_SESSION['contact_message'] = $phoneLengthMessage($codigoPostal);
        $_SESSION['contact_message_type'] = 'error';
    } elseif ($parentescoError !== null) {
        $_SESSION['contact_message'] = $parentescoError;
        $_SESSION['contact_message_type'] = 'error';
    } else {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        $dialCodeInt = (int)$codigoPostal;
        if ($hasDuplicateContact($conn, $userId, $nombre, $dialCodeInt, $telefono)) {
            $_SESSION['contact_message'] = 'Ya existe un contacto con ese nombre o ese número.';
            $_SESSION['contact_message_type'] = 'error';
            header('Location: dashboard.php');
            exit;
        }

        $uploadError = null;
        $photoPath = agenda_upload_image('foto_contacto', 'contacts', $uploadError);
        if ($uploadError !== null) {
            $_SESSION['contact_message'] = $uploadError ?: 'La foto del contacto no es válida.';
            $_SESSION['contact_message_type'] = 'error';
        } else {
            $stmt = $conn->prepare('INSERT INTO contactos (usuario_id, nombre, codigo_postal, telefono, email, parentesco, foto_contacto, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
            if ($stmt) {
                $stmt->bind_param('isissss', $userId, $nombre, $dialCodeInt, $telefono, $email, $parentesco, $photoPath);
                if ($stmt->execute()) {
                    $_SESSION['contact_message'] = 'Contacto guardado correctamente.';
                } else {
                    $_SESSION['contact_message'] = 'No se pudo guardar el contacto. Intenta de nuevo.';
                    $_SESSION['contact_message_type'] = 'error';
                    if ($photoPath) {
                        agenda_delete_image($photoPath);
                    }
                }
                $stmt->close();
            } else {
                $_SESSION['contact_message'] = 'Error en la conexión con la base de datos.';
                $_SESSION['contact_message_type'] = 'error';
                if ($photoPath) {
                    agenda_delete_image($photoPath);
                }
            }
        }
    }
// Acción: eliminar contacto.
} elseif ($action === 'delete') {
    $contactoId = (int)($_POST['contacto_id'] ?? 0);
    if ($contactoId > 0) {
        $oldPhotoPath = null;
        $selectStmt = $conn->prepare('SELECT foto_contacto FROM contactos WHERE id = ? AND usuario_id = ?');
        if ($selectStmt) {
            $selectStmt->bind_param('ii', $contactoId, $userId);
            $selectStmt->execute();
            $selectStmt->bind_result($oldPhotoPath);
            $selectStmt->fetch();
            $selectStmt->close();
        }

        $stmt = $conn->prepare('DELETE FROM contactos WHERE id = ? AND usuario_id = ?');
        if ($stmt) {
            $stmt->bind_param('ii', $contactoId, $userId);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $_SESSION['contact_message'] = 'Contacto eliminado correctamente.';
                if ($oldPhotoPath) {
                    agenda_delete_image($oldPhotoPath);
                }
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
// Acción: editar contacto.
} elseif ($action === 'edit') {
    $contactoId = (int)($_POST['contacto_id'] ?? 0);
    $nombre = trim($_POST['nombre'] ?? '');
    $codigoPostal = trim((string)($_POST['codigo_postal'] ?? ''));
    $telefono = $sanitizePhone(trim($_POST['telefono'] ?? ''));
    $parentescoInput = trim($_POST['parentesco'] ?? '');
    $parentescoOtroInput = trim($_POST['parentesco_otro'] ?? '');
    $parentescoError = null;
    $parentesco = $resolveParentesco($parentescoInput, $parentescoOtroInput, $parentescoError);
    $email = trim($_POST['email'] ?? '');

    if ($contactoId <= 0 || $nombre === '' || $codigoPostal === '' || $telefono === '') {
        $_SESSION['contact_message'] = 'Completa nombre, código de país y teléfono para actualizar el contacto.';
        $_SESSION['contact_message_type'] = 'error';
    } elseif (!$validateDialCode($codigoPostal)) {
        $_SESSION['contact_message'] = 'El prefijo de país no es válido.';
        $_SESSION['contact_message_type'] = 'error';
    } elseif (!$validatePhoneLengthByPrefix($codigoPostal, $telefono)) {
        $_SESSION['contact_message'] = $phoneLengthMessage($codigoPostal);
        $_SESSION['contact_message_type'] = 'error';
    } elseif ($parentescoError !== null) {
        $_SESSION['contact_message'] = $parentescoError;
        $_SESSION['contact_message_type'] = 'error';
    } else {
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $email = null;
        }

        $dialCodeInt = (int)$codigoPostal;
        if ($hasDuplicateContact($conn, $userId, $nombre, $dialCodeInt, $telefono, $contactoId)) {
            $_SESSION['contact_message'] = 'Ya existe un contacto con ese nombre o ese número.';
            $_SESSION['contact_message_type'] = 'error';
            header('Location: dashboard.php');
            exit;
        }

        $currentPhotoPath = null;
        $selectStmt = $conn->prepare('SELECT foto_contacto FROM contactos WHERE id = ? AND usuario_id = ?');
        if ($selectStmt) {
            $selectStmt->bind_param('ii', $contactoId, $userId);
            $selectStmt->execute();
            $selectStmt->bind_result($currentPhotoPath);
            $selectStmt->fetch();
            $selectStmt->close();
        }

        $uploadError = null;
        $newPhotoPath = agenda_upload_image('foto_contacto', 'contacts', $uploadError);
        if ($uploadError !== null) {
            $_SESSION['contact_message'] = $uploadError ?: 'La foto del contacto no es válida.';
            $_SESSION['contact_message_type'] = 'error';
        } else {
            $photoPathToSave = $newPhotoPath ?: $currentPhotoPath;
            $stmt = $conn->prepare('UPDATE contactos SET nombre = ?, codigo_postal = ?, telefono = ?, email = ?, parentesco = ?, foto_contacto = ? WHERE id = ? AND usuario_id = ?');
            if ($stmt) {
                $stmt->bind_param('sissssii', $nombre, $dialCodeInt, $telefono, $email, $parentesco, $photoPathToSave, $contactoId, $userId);
                if ($stmt->execute()) {
                    $_SESSION['contact_message'] = 'Contacto actualizado correctamente.';
                    if ($newPhotoPath && $currentPhotoPath && $newPhotoPath !== $currentPhotoPath) {
                        agenda_delete_image($currentPhotoPath);
                    }
                } else {
                    $_SESSION['contact_message'] = 'No se pudo actualizar el contacto. Intenta de nuevo.';
                    $_SESSION['contact_message_type'] = 'error';
                    if ($newPhotoPath) {
                        agenda_delete_image($newPhotoPath);
                    }
                }
                $stmt->close();
            } else {
                $_SESSION['contact_message'] = 'Error en la conexión con la base de datos.';
                $_SESSION['contact_message_type'] = 'error';
                if ($newPhotoPath) {
                    agenda_delete_image($newPhotoPath);
                }
            }
        }
    }
} else {
    $_SESSION['contact_message'] = 'Acción no válida.';
    $_SESSION['contact_message_type'] = 'error';
}

// Volver siempre al dashboard mostrando el mensaje de estado.
header('Location: dashboard.php');
exit;
