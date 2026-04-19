<?php
session_start();
// Cargar conexión y verificar sesión.
require_once __DIR__ . '/conexion.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'Invitado';

// Mensajes de acciones de contacto (agregar/eliminar).
$message = $_SESSION['contact_message'] ?? '';
$messageType = $_SESSION['contact_message_type'] ?? 'success';
unset($_SESSION['contact_message'], $_SESSION['contact_message_type']);

// Cargar los contactos del usuario desde la base de datos.
$stmt = $conn->prepare('SELECT id, nombre, telefono, email, created_at FROM contactos WHERE usuario_id = ? ORDER BY created_at DESC');
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$contactos = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
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
                <div class="brand-icon"></div>
                <div>
                    <h2>YouGenda</h2>
                    <p>Bienvenido, <?php echo htmlspecialchars($username); ?></p>
                </div>
            </div>
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
                <div class="panel-card add-card">
                    <h3>Agregar contacto</h3>
                    <form action="contact_action.php" method="post" class="contact-form clear-on-reload" autocomplete="off">
                        <input type="hidden" name="action" value="add">
                        <label>
                            <span>Nombre</span>
                            <input type="text" name="nombre" required autocomplete="off">
                        </label>
                        <label>
                            <span>Teléfono</span>
                            <input type="text" name="telefono" required autocomplete="off">
                        </label>
                        <label>
                            <span>Email</span>
                            <input type="email" name="email" autocomplete="off">
                        </label>
                        <button type="submit" class="btn btn-primary">Guardar contacto</button>
                    </form>
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
                                <div class="contact-card" data-name="<?php echo htmlspecialchars(strtolower($contacto['nombre'])); ?>" data-phone="<?php echo htmlspecialchars(strtolower($contacto['telefono'])); ?>" data-email="<?php echo htmlspecialchars(strtolower($contacto['email'])); ?>">
                                    <div class="contact-avatar"></div>
                                    <div class="contact-info">
                                        <h4><?php echo htmlspecialchars($contacto['nombre']); ?></h4>
                                        <p><?php echo htmlspecialchars($contacto['telefono']); ?></p>
                                        <?php if ($contacto['email']): ?>
                                            <p class="small-text"><?php echo htmlspecialchars($contacto['email']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <form action="contact_action.php" method="post" class="delete-form">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="contacto_id" value="<?php echo (int)$contacto['id']; ?>">
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <script src="assets/app.js"></script>
</body>
</html>