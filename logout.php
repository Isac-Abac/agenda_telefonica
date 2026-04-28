<?php
// Archivo: logout.php
// Cierra la sesión actual y redirige al login.

// Inicializar sesión para poder destruirla.
session_start();
// Limpiar variables de sesión.
session_unset();
// Destruir sesión por completo.
session_destroy();
// Volver a la pantalla de inicio.
header('Location: index.php');
exit;
