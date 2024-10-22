<?php
session_start(); // Inicia a sessão

// Verifica se o usuário está autenticado
if (!isset($_SESSION['username'])) {
    header("Location: index.php"); // Redireciona para a página de login se o usuário não estiver autenticado
    exit();
}

// Lógica de confirmação de logout
if (isset($_POST['confirm_logout'])) {
    // Destruir todas as variáveis de sessão
    $_SESSION = array();

    // Se estiver usando cookies de sessão, também apaga o cookie da sessão
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente, destruir a sessão
    session_destroy();

    // Adicionar uma mensagem de sucesso à sessão
    $_SESSION['logout_message'] = "Você foi deslogado com sucesso!";
    
    // Redirecionar para a página de login (index.php)
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Confirmar Logout</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
    <style>
        .confirmation-container {
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

        /* Estilos do botão de deslogar */
        .logout-button {
            background-color: #4CAF50; /* Cor do botão */
            color: white; /* Cor do texto */
            border: none; /* Sem borda */
            height: 50px; /* Altura do botão */
            cursor: pointer; /* Cursor como pointer */
            border-radius: 5px; /* Cantos arredondados */
            width: 100%; /* Largura total */
            font-size: 18px; /* Tamanho da fonte */
            line-height: 50px; /* Altura da linha */
            margin: 5px 0; /* Margem para separar os botões */
        }

        .logout-button:hover {
            opacity: 0.8; /* Efeito hover */
        }

        /* Estilos do botão voltar */
        .back-button {
            background-color: #f44336; /* Cor do botão (pode ser alterada) */
            color: white; /* Cor do texto */
            border: none; /* Sem borda */
            height: 50px; /* Altura do botão */
            cursor: pointer; /* Cursor como pointer */
            border-radius: 5px; /* Cantos arredondados */
            width: 100%; /* Largura total */
            font-size: 18px; /* Tamanho da fonte */
            line-height: 50px; /* Altura da linha */
            margin: 5px 0; /* Margem para separar os botões */
        }

        .back-button:hover {
            opacity: 0.8; /* Efeito hover */
        }

        a {
            text-decoration: none; /* Remove underline do link */
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <h2>Confirmar Logout</h2>
        <p>Você tem certeza que deseja deslogar?</p>
        <form method="post" action="">
            <input type="submit" name="confirm_logout" class="logout-button" value="Deslogar">
            <a href="dashboard.php"><input type="button" class="back-button" value="Voltar"></a>
        </form>
    </div>
</body>
</html>

