<?php
// Archivo: index.php
// Pantalla principal para iniciar sesiÃ³n.

session_start();
header('Content-Type: text/html; charset=UTF-8');
// Conectar con la base de datos usando el archivo de conexiÃƒÂ³n compartido.
require_once __DIR__ . '/conexion.php';

// Si ya existe sesiÃƒÂ³n, redirigir al panel principal.
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$messageType = 'success';

// Leer mensaje flash enviado desde la pÃƒÂ¡gina de registro.
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'success';
    unset($_SESSION['message'], $_SESSION['message_type']);
}

// Procesar envÃƒÂ­o del formulario de inicio de sesiÃƒÂ³n.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Leer credenciales enviadas por el usuario.
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
                // Iniciar sesión del usuario autenticado.
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header('Location: dashboard.php');
                exit;
            }
        }
    }

    $message = 'Email o contraseÃƒÂ±a incorrectos.';
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
                    <span>ContraseÃƒÂ±a</span>
                    <input type="password" name="password" id="loginPassword" required autocomplete="new-password">
                </label>
                <label class="password-toggle-row">
                    <input type="checkbox" data-toggle-password="#loginPassword">
                    <span>Ver contraseÃƒÂ±a</span>
                </label>
                <button type="submit" class="btn btn-primary">Iniciar sesiÃƒÂ³n</button>
            </form>
            <p class="small-text">Ã‚Â¿AÃƒÂºn no tienes cuenta? <a href="register.php">RegÃƒÂ­strate</a></p>
        </div>
    </div>
    <script src="assets/app.js"></script>
</body>
</html>
