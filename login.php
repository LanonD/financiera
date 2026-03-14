<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
    <form method="POST" action="php/validacion.php">

    <h2>Iniciar Sesión</h2>

    <label>Usuario</label>
    <input type="text" name="user" required>

    <label>Contraseña</label>
    <input type="password" name="pwd" required>

    <button type="submit">Entrar</button>

    </form>
</body>
</html>