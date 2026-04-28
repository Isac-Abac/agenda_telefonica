<?php
// Archivo: register.php
// Pantalla para crear una cuenta nueva.

session_start();
header('Content-Type: text/html; charset=UTF-8');
// Cargar la conexiÃ³n con la base de datos.
require_once __DIR__ . '/conexion.php';

// Si ya hay un usuario con sesiÃ³n, enviarlo al dashboard.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = 'error';
// Validar formulario de registro.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer datos del formulario de registro.
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$username || !$email || !$password) {
        $message = 'Completa todos los campos para registrarte.';
    } elseif ($password !== $confirm) {
        $message = 'Las contraseÃ±as no coinciden.';
    } else {
        // Verificar si ya existe un usuario con el email o nombre elegido.
        $stmt = $conn->prepare('SELECT id FROM usuarios WHERE email = ? OR username = ?');
        if ($stmt) {
            $stmt->bind_param('ss', $email, $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $message = 'El usuario o email ya estÃ¡n en uso.';
            } else {
                $stmt->close();
                $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare('INSERT INTO usuarios (username, email, password_hash, created_at) VALUES (?, ?, ?, NOW())');
                if ($stmt) {
                    // Guardar usuario con contraseña en hash seguro.
                    $stmt->bind_param('sss', $username, $email, $passwordHash);
                    if ($stmt->execute()) {
                        $_SESSION['message'] = 'Registro correcto, ya puedes iniciar sesiÃ³n.';
                        $_SESSION['message_type'] = 'success';
                        header('Location: index.php');
                        exit;
                    }
                }
                $message = 'Error al crear la cuenta. Intenta de nuevo.';
            }
            $stmt->close();
        } else {
            $message = 'Error en la conexiÃ³n con la base de datos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - Agenda Instagram</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body data-alert-message="<?php echo htmlspecialchars($message); ?>" data-alert-type="<?php echo htmlspecialchars($messageType); ?>">
    <div class="page-shell login-shell">
        <div class="glass-card auth-card">
            <div class="brand-header">
                <div>
                    <div class="brand-icon"></div>
                    <h1>InstaAgenda</h1>
                </div>
                <button type="button" class="theme-toggle" id="themeToggle">Modo oscuro</button>
            </div>
            <p class="subtitle">Crea tu cuenta y comienza a guardar tus contactos.</p>
            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="post" class="auth-form clear-on-reload" autocomplete="off">
                <label>
                    <span>Usuario</span>
                    <input type="text" name="username" required autocomplete="off">
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" required autocomplete="off">
                </label>
                <label>
                    <span>ContraseÃ±a</span>
                    <input type="password" name="password" id="regPassword" required autocomplete="new-password">
                </label>
                <label>
                    <span>Confirmar contraseÃ±a</span>
                    <input type="password" name="confirm_password" id="regConfirmPassword" required autocomplete="new-password">
                </label>
                <label class="password-toggle-row">
                    <input type="checkbox" data-toggle-password="#regPassword,#regConfirmPassword">
                    <span>Ver contraseÃ±as</span>
                </label>
                <button type="submit" class="btn btn-primary">Crear cuenta</button>
            </form>
            <p class="small-text">Â¿Ya tienes cuenta? <a href="index.php">Iniciar sesiÃ³n</a></p>
        </div>
    </div>
    <script src="assets/app.js"></script>
</body>
</html>
