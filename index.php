<?php
session_start(); // Inicia a sessão
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Página de Login</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #5c44a4;
            color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #241c3c;
        }

        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            border: none;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            height: 50px;
            cursor: pointer;
            border-radius: 5px;
            width: 100%;
            font-size: 18px;
            line-height: 50px;
        }

        a {
            color: #FFD700;
        }

        input[type="submit"]:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (!empty($_SESSION['error_message'])): ?>
            <p class="error"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></p>
        <?php endif; ?>
        <form method="post" action="login.php">
            <label for="username">Nome de Usuário:</label>
            <input type="text" id="username" name="username" required>

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Entrar">
        </form>
        <p>Ainda não tem uma conta? <a href="register.php">Registrar</a></p>
    </div>
</body>
</html>

