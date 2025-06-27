<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="Estilo/styles.css">
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST" action="Controller/loguear.php">
            <img src="logo-newHarvest.png" name="logo">
            <h2>Iniciar Sesión</h2>
            <label>Usuario: </label>
            <input type="text" id="user" name="user"><br>
            <label>Contraseña: </label>
            <input type="password" id="pass" name="pass"><br>
            <input class="buttom" type="submit" value="Entrar">
            <?php if (isset($_GET['error'])): ?>
                <div class="error-message">
                    <?php echo '<u>' . htmlspecialchars($_GET['error']) . '</u>'; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>
