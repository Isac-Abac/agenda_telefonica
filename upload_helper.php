<?php
// Archivo: upload_helper.php
// Este archivo concentra utilidades para subir y eliminar imágenes.

// Sube una imagen validando tamaño, MIME y carpeta de destino.
function agenda_upload_image(string $inputName, string $subDir, ?string &$error = null): ?string
{
    $error = null;

    // Si no existe el campo en $_FILES, no hay archivo para procesar.
    if (!isset($_FILES[$inputName])) {
        return null;
    }

    $file = $_FILES[$inputName];

    // Si no se eligió archivo, no lo tratamos como error.
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    // Cualquier otro error de subida sí se reporta.
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        $error = 'No se pudo subir la imagen.';
        return null;
    }

    // Validación básica del archivo temporal.
    $tmpName = $file['tmp_name'] ?? '';
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        $error = 'Archivo de imagen inválido.';
        return null;
    }

    // Limitar peso máximo a 2MB.
    $maxBytes = 2 * 1024 * 1024;
    $fileSize = (int)($file['size'] ?? 0);
    if ($fileSize <= 0 || $fileSize > $maxBytes) {
        $error = 'La imagen debe pesar máximo 2 MB.';
        return null;
    }

    // Detectar MIME real del archivo para evitar extensiones falsificadas.
    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = (string)finfo_file($finfo, $tmpName);
            finfo_close($finfo);
        }
    }

    if ($mime === '' && function_exists('mime_content_type')) {
        $mime = (string)mime_content_type($tmpName);
    }

    // Lista de tipos de imagen permitidos.
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif'
    ];

    if (!isset($allowed[$mime])) {
        $error = 'Solo se permiten imágenes JPG, PNG, WEBP o GIF.';
        return null;
    }

    // Construir ruta final en carpeta uploads/<subdir>.
    $rootDir = __DIR__;
    $uploadsDir = $rootDir . DIRECTORY_SEPARATOR . 'uploads';
    $safeSubDir = trim(str_replace(['..', '/', '\\'], '', $subDir));
    $targetDir = $uploadsDir . DIRECTORY_SEPARATOR . $safeSubDir;

    if (!is_dir($uploadsDir) && !mkdir($uploadsDir, 0755, true)) {
        $error = 'No se pudo crear la carpeta de uploads.';
        return null;
    }

    if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
        $error = 'No se pudo crear la carpeta de destino.';
        return null;
    }

    // Generar nombre único para evitar colisiones.
    try {
        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    } catch (Throwable $e) {
        $filename = uniqid('img_', true) . '.' . $allowed[$mime];
    }

    // Mover archivo desde temporal a destino final.
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        $error = 'No se pudo guardar la imagen en el servidor.';
        return null;
    }

    // Devolver ruta relativa para guardarla en base de datos.
    return 'uploads/' . $safeSubDir . '/' . $filename;
}

// Elimina una imagen física cuando la app ya no la necesita.
function agenda_delete_image(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }

    // Solo permitir eliminación dentro de uploads por seguridad.
    $safePath = str_replace('\\', '/', $relativePath);
    if (strpos($safePath, 'uploads/') !== 0) {
        return;
    }

    $fullPath = __DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $safePath);
    if (is_file($fullPath)) {
        @unlink($fullPath);
    }
}
