<?php
session_start();
// Conectar con la base de datos usando el archivo de conexión compartido.
require_once __DIR__ . '/conexion.php';

// Si ya existe sesión, redirigir al panel principal.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = 'success';

// Leer mensaje flash enviado desde la página de registro.
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Procesar envío del formulario de inicio de sesión.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginInput = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($loginInput && $password) {
        // Consultar el usuario por email o nombre de usuario.
        $stmt = $conn->prepare('SELECT id, username, password_hash FROM usuarios WHERE email = ? OR username = ?');
        if ($stmt) {
            $stmt->bind_param('ss', $loginInput, $loginInput);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            }
        }
    }

    $message = 'Email o contraseña incorrectos.';
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agenda Instagram</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body data-alert-message="<?php echo htmlspecialchars($message); ?>" data-alert-type="<?php echo htmlspecialchars($messageType); ?>">
    <div class="page-shell login-shell">
        <div class="glass-card auth-card">
            <div class="brand-header">
                <div>
                    <div class="brand-icon"></div>
                    <h1>YouGenda</h1>
                </div>
                <button type="button" class="theme-toggle" id="themeToggle">Modo oscuro</button>
            </div>
            <p class="subtitle">Agrega a tus contactos especiales.</p>
            <?php if ($message): ?>
                <div class="alert <?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            <form method="post" class="auth-form clear-on-reload" autocomplete="off">
                <label>
                    <span>Email o usuario</span>
                    <input type="text" name="email" required autocomplete="off">
                </label>
                <label>
                    <span>Contraseña</span>
                    <input type="password" name="password" required autocomplete="new-password">
                </label>
                <button type="submit" class="btn btn-primary">Iniciar sesión</button>
            </form>
            <p class="small-text">¿Aún no tienes cuenta? <a href="register.php">Regístrate</a></p>
        </div>
    </div>
    <script src="assets/app.js"></script>
</body>
</html>