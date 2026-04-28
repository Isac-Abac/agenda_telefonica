<?php
// Archivo: dashboard.php
// Vista principal de la agenda: listado, alta y edición de contactos.

session_start();
header('Content-Type: text/html; charset=UTF-8');
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Invitado';

// Catálogo de prefijos internacionales y reglas de longitud por país.
$countryDialOptions = [
    '502' => ['name' => 'Guatemala', 'min' => 8, 'max' => 8],
    '1' => ['name' => 'Estados Unidos/Canadá', 'min' => 10, 'max' => 10],
    '52' => ['name' => 'México', 'min' => 10, 'max' => 10],
    '34' => ['name' => 'España', 'min' => 9, 'max' => 9],
    '57' => ['name' => 'Colombia', 'min' => 10, 'max' => 10],
    '54' => ['name' => 'Argentina', 'min' => 10, 'max' => 10],
    '51' => ['name' => 'Perú', 'min' => 9, 'max' => 9],
    '56' => ['name' => 'Chile', 'min' => 9, 'max' => 9],
    '593' => ['name' => 'Ecuador', 'min' => 9, 'max' => 9],
    '58' => ['name' => 'Venezuela', 'min' => 10, 'max' => 10],
    '55' => ['name' => 'Brasil', 'min' => 10, 'max' => 11],
    '49' => ['name' => 'Alemania', 'min' => 10, 'max' => 11],
    '33' => ['name' => 'Francia', 'min' => 9, 'max' => 9],
    '39' => ['name' => 'Italia', 'min' => 9, 'max' => 10],
    '44' => ['name' => 'Reino Unido', 'min' => 10, 'max' => 10],
    '81' => ['name' => 'Japón', 'min' => 10, 'max' => 10]
];
// Opciones de parentesco predefinidas para el formulario.
$relationshipOptions = ['Padre', 'Madre', 'Hijo', 'Hija', 'Hermano', 'Hermana', 'Pareja', 'Amigo', 'Trabajo', 'Otro'];

$message = $_SESSION['contact_message'] ?? '';
$messageType = $_SESSION['contact_message_type'] ?? 'success';
unset($_SESSION['contact_message'], $_SESSION['contact_message_type']);

// Cargar foto de perfil del usuario actual.
$userPhotoPath = null;
$userStmt = $conn->prepare('SELECT foto_perfil FROM usuarios WHERE id = ?');
if ($userStmt) {
    $userStmt->bind_param('i', $userId);
    $userStmt->execute();
    $userStmt->bind_result($userPhotoPath);
    $userStmt->fetch();
    $userStmt->close();
}

// Consultar contactos del usuario en orden descendente de creación.
$stmt = $conn->prepare('SELECT id, nombre, telefono, codigo_postal, email, parentesco, foto_contacto, created_at FROM contactos WHERE usuario_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$contactos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$userInitial = strtoupper(substr($username, 0, 1));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Agenda - YouGenda</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body data-alert-message="<?php echo htmlspecialchars($message); ?>" data-alert-type="<?php echo htmlspecialchars($messageType); ?>">
    <div class="dashboard-shell">
        <aside class="sidebar">
            <div class="brand-sidebar">
                <div>
                    <h2>YouGenda</h2>
                    <p>Bienvenido, <?php echo htmlspecialchars($username); ?></p>
                </div>
            </div>

            <section class="profile-panel">
                <button
                    type="button"
                    class="profile-avatar preview-image-trigger"
                    data-image-src="<?php echo htmlspecialchars($userPhotoPath ?? ''); ?>"
                    data-image-alt="Foto de perfil de <?php echo htmlspecialchars($username); ?>"
                >
                    <?php if (!empty($userPhotoPath)): ?>
                        <img src="<?php echo htmlspecialchars($userPhotoPath); ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($userInitial); ?></span>
                    <?php endif; ?>
                </button>

                <form action="contact_action.php" method="post" enctype="multipart/form-data" class="profile-upload-form">
                    <input type="hidden" name="action" value="update_profile_photo">
                    <input type="file" name="foto_perfil" id="profilePhotoInput" class="hidden-file-input" accept="image/*">
                    <button type="button" class="btn btn-primary profile-upload-trigger" id="profilePhotoTrigger">Cambiar foto</button>
                </form>
            </section>

            <nav class="menu-list">
                <a href="#" class="menu-item active">Contactos</a>
                <a href="logout.php" class="menu-item">Cerrar sesión</a>
            </nav>
        </aside>

        <main class="dashboard-content">
            <header class="top-bar">
                <div>
                    <h1>Contactos</h1>
                    <p>Administra tus números de teléfono.</p>
                </div>
                <button type="button" class="theme-toggle" id="themeToggle">Modo oscuro</button>
            </header>

            <section class="panel grid-panel">
                <button type="button" class="btn btn-primary fab-add-contact" id="openAddContact">+ Agregar contacto</button>

                <div id="addModal" class="modal">
                    <div class="modal-content">
                        <span id="closeAddModal" class="close">&times;</span>
                        <h3>Agregar contacto</h3>
                        <form action="contact_action.php" method="post" enctype="multipart/form-data" class="contact-form clear-on-reload" autocomplete="off">
                            <input type="hidden" name="action" value="add">
                            <label>
                                <span>Nombre</span>
                                <input type="text" name="nombre" required autocomplete="off">
                            </label>
                            <label>
                                <span>Código de país</span>
                                <select name="codigo_postal" id="addCodigoPostal" required>
                                    <option value="">Selecciona país y prefijo</option>
                                    <?php foreach ($countryDialOptions as $dialCode => $country): ?>
                                        <option value="<?php echo htmlspecialchars($dialCode); ?>" data-min="<?php echo (int)$country['min']; ?>" data-max="<?php echo (int)$country['max']; ?>"><?php echo htmlspecialchars($country['name'] . ' +' . $dialCode); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label>
                                <span>Teléfono</span>
                                <input type="text" name="telefono" id="addTelefono" required autocomplete="off" inputmode="numeric" pattern="[0-9]{6,15}" maxlength="15" placeholder="Número sin prefijo">
                            </label>
                            <label>
                                <span>Correo (opcional)</span>
                                <input type="email" name="email" autocomplete="off">
                            </label>
                            <label>
                                <span>Parentesco (opcional)</span>
                                <select name="parentesco" id="addParentesco">
                                    <option value="">Selecciona parentesco</option>
                                    <?php foreach ($relationshipOptions as $option): ?>
                                        <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label id="addParentescoOtroWrap" class="is-hidden">
                                <span>Especifica parentesco</span>
                                <input type="text" name="parentesco_otro" id="addParentescoOtro" maxlength="30" placeholder="Máximo 30 caracteres" autocomplete="off">
                            </label>
                            <label>
                                <span>Foto del contacto</span>
                                <input type="file" name="foto_contacto" accept="image/*">
                            </label>
                            <button type="submit" class="btn btn-primary">Guardar contacto</button>
                        </form>
                    </div>
                </div>

                <div class="panel-card contacts-card">
                    <div class="contacts-header">
                        <div>
                            <h3>Mis contactos</h3>
                            <p>Filtra por nombre, teléfono o email.</p>
                        </div>
                        <input type="search" id="searchContacts" placeholder="Buscar contacto...">
                    </div>

                    <div class="contact-list" id="contactList">
                        <?php if (empty($contactos)): ?>
                            <div class="empty-state">
                                <p>No tienes contactos aún. Añade el primero usando el formulario.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($contactos as $contacto): ?>
                                <?php
                                $dialCode = (string)($contacto['codigo_postal'] ?? '');
                                $fullPhone = ($dialCode !== '' ? '+' . $dialCode . ' ' : '') . $contacto['telefono'];
                                $contactPhoto = $contacto['foto_contacto'] ?? '';
                                $contactInitial = strtoupper(substr((string)$contacto['nombre'], 0, 1));
                                ?>
                                <div class="contact-card" data-name="<?php echo htmlspecialchars(strtolower($contacto['nombre'])); ?>" data-phone="<?php echo htmlspecialchars(strtolower($fullPhone)); ?>" data-email="<?php echo htmlspecialchars(strtolower((string)$contacto['email'])); ?>">
                                    <button
                                        type="button"
                                        class="contact-avatar preview-image-trigger"
                                        data-image-src="<?php echo htmlspecialchars($contactPhoto); ?>"
                                        data-image-alt="Foto de <?php echo htmlspecialchars($contacto['nombre']); ?>"
                                    >
                                        <?php if (!empty($contactPhoto)): ?>
                                            <img src="<?php echo htmlspecialchars($contactPhoto); ?>" alt="Foto de contacto">
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($contactInitial); ?></span>
                                        <?php endif; ?>
                                    </button>

                                    <div class="contact-info">
                                        <h4><?php echo htmlspecialchars($contacto['nombre']); ?></h4>
                                        <p><?php echo htmlspecialchars($fullPhone); ?></p>
                                        <?php if (!empty($contacto['email'])): ?>
                                            <p class="small-text"><?php echo htmlspecialchars($contacto['email']); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($contacto['parentesco'])): ?>
                                            <p class="small-text"><?php echo htmlspecialchars($contacto['parentesco']); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="contact-actions">
                                        <button
                                            type="button"
                                            class="btn-menu"
                                            data-id="<?php echo (int)$contacto['id']; ?>"
                                            data-nombre="<?php echo htmlspecialchars($contacto['nombre']); ?>"
                                            data-telefono="<?php echo htmlspecialchars($contacto['telefono']); ?>"
                                            data-codigopostal="<?php echo htmlspecialchars($dialCode); ?>"
                                            data-email="<?php echo htmlspecialchars((string)($contacto['email'] ?? '')); ?>"
                                            data-parentesco="<?php echo htmlspecialchars((string)($contacto['parentesco'] ?? '')); ?>"
                                        >
                                            <span></span><span></span><span></span>
                                        </button>
                                        <div class="dropdown-menu">
                                            <button type="button" class="btn-edit" data-id="<?php echo (int)$contacto['id']; ?>">Editar</button>
                                            <form action="contact_action.php" method="post" class="delete-form">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="contacto_id" value="<?php echo (int)$contacto['id']; ?>">
                                                <button type="submit" class="btn-delete">Eliminar</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span id="closeModal" class="close">&times;</span>
            <h3>Editar contacto</h3>
            <form action="contact_action.php" method="post" enctype="multipart/form-data" class="contact-form clear-on-reload" autocomplete="off">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="contacto_id" id="editId">
                <label>
                    <span>Nombre</span>
                    <input type="text" name="nombre" id="editNombre" required autocomplete="off">
                </label>
                <label>
                    <span>Código de país</span>
                    <select name="codigo_postal" id="editCodigoPostal" required>
                        <option value="">Selecciona país y prefijo</option>
                        <?php foreach ($countryDialOptions as $dialCode => $country): ?>
                            <option value="<?php echo htmlspecialchars($dialCode); ?>" data-min="<?php echo (int)$country['min']; ?>" data-max="<?php echo (int)$country['max']; ?>"><?php echo htmlspecialchars($country['name'] . ' +' . $dialCode); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Teléfono</span>
                    <input type="text" name="telefono" id="editTelefono" required autocomplete="off" inputmode="numeric" pattern="[0-9]{6,15}" maxlength="15" placeholder="Número sin prefijo">
                </label>
                <label>
                    <span>Correo (opcional)</span>
                    <input type="email" name="email" id="editEmail" autocomplete="off">
                </label>
                <label>
                    <span>Parentesco (opcional)</span>
                    <select name="parentesco" id="editParentesco">
                        <option value="">Selecciona parentesco</option>
                        <?php foreach ($relationshipOptions as $option): ?>
                            <option value="<?php echo htmlspecialchars($option); ?>"><?php echo htmlspecialchars($option); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label id="editParentescoOtroWrap" class="is-hidden">
                    <span>Especifica parentesco</span>
                    <input type="text" name="parentesco_otro" id="editParentescoOtro" maxlength="30" placeholder="Máximo 30 caracteres" autocomplete="off">
                </label>
                <label>
                    <span>Nueva foto del contacto (opcional)</span>
                    <input type="file" name="foto_contacto" accept="image/*">
                </label>
                <button type="submit" class="btn btn-primary">Actualizar contacto</button>
            </form>
        </div>
    </div>

    <div id="imageViewer" class="image-viewer" aria-hidden="true">
        <button type="button" class="image-viewer-close" id="imageViewerClose" aria-label="Cerrar">&times;</button>
        <img id="imageViewerContent" src="" alt="Vista previa">
    </div>

    <script src="assets/app.js"></script>
</body>
</html>
