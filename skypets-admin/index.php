<?php
require_once __DIR__ . '/auth.php';
iniciarSesionAdmin();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $espera = loginBloqueo();
    if ($espera > 0) {
        rlResponder429($espera);
        $error = 'Demasiados intentos. Espera ' . max(1, (int)ceil($espera / 60)) . ' minuto(s) y vuelve a intentar.';
    } elseif (doLogin((string)($_POST['usuario'] ?? ''), (string)($_POST['clave'] ?? ''))) {
        loginExito();
        header('Location: dashboard.php');
        exit;
    } else {
        loginFallo();
        $error = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SkyPets — Certificados</title>
<link rel="icon" type="image/png" href="/assets/images/logo.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@600;700&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-body">
<div class="login-card">
    <img src="assets/images/logo.png" alt="SkyPets" class="login-logo">
    <h1>Certificados SkyPets</h1>
    <p class="login-sub">Panel de la veterinaria</p>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Usuario</label>
        <input type="text" name="usuario" placeholder="usuario" autocomplete="username" required>
        <label>Contraseña</label>
        <input type="password" name="clave" placeholder="••••••••" autocomplete="current-password" required>
        <button type="submit" class="btn-primary" style="margin-top:20px;">Ingresar</button>
    </form>
</div>
</body>
</html>
