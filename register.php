<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$servername = "localhost";
$username = "root";
$password = "cielopulse";
$dbname = "meu_sistema_de_arquivos";

// Conecta ao banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica se a conexão falhou
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = ""; // Variável para armazenar mensagens de erro ou sucesso

// Verifica o número atual de usuários cadastrados
$result = $conn->query("SELECT COUNT(*) AS total_users FROM users");
$row = $result->fetch_assoc();
$total_users = $row['total_users'];

if ($total_users >= 10) {
    $error_message = "O número máximo de usuários cadastrados foi atingido. Não é possível registrar novos usuários.";
} else {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user = $_POST['username'];
        $pass = $_POST['password'];

        // Verifica se o nome de usuário ou senha estão vazios
        if (empty($user) || empty($pass)) {
            $error_message = "Por favor, preencha todos os campos.";
        } elseif (strlen($user) > 25) { // Verifica se o nome de usuário é maior que 25 caracteres
            $error_message = "O nome de usuário deve ter no máximo 25 caracteres.";
        } else {
            // Verifica se a senha tem pelo menos 8 caracteres
            if (strlen($pass) < 8) {
                $error_message = "A senha deve ter no mínimo 8 caracteres.";
            } else {
                // Verifica se o nome de usuário já existe
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username=?");
                $stmt->bind_param("s", $user);
                $stmt->execute();
                $stmt->bind_result($count);
                $stmt->fetch();
                $stmt->close();

                if ($count > 0) {
                    $error_message = "O nome de usuário já está em uso.";
                } else {
                    // Hash da senha
                    $pass_hashed = password_hash($pass, PASSWORD_DEFAULT);

                    // Preparar a inserção no banco de dados
                    $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
                    $stmt->bind_param("ss", $user, $pass_hashed);

                    // Verificar se a inserção foi bem-sucedida
                    if ($stmt->execute()) {
                        $error_message = "Registro criado com sucesso! <a href='index.php'>Voltar ao Login</a>";
                    } else {
                        $error_message = "Erro: " . $stmt->error;
                    }

                    $stmt->close();
                }
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE HTML>
<html lang="pt-br">
<head>
    <title>Registrar Novo Usuário</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
    <style>
        /* Estilo para centralizar a área de registro */
        .register-container {
            max-width: 400px; /* Largura máxima do contêiner de registro */
            margin: 0 auto; /* Centraliza horizontalmente */
            padding: 20px; /* Espaçamento interno */
            background-color: #5c44a4; /* Cor de fundo roxa */
            color: white; /* Cor do texto branca */
            border-radius: 10px; /* Bordas arredondadas */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Sombra suave */
            text-align: center; /* Centraliza o conteúdo dentro da caixa */
        }

        /* Ajustes adicionais para centralizar na tela */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Altura total da tela */
            margin: 0;
            background-color: #241c3c; /* Cor de fundo geral */
        }

        /* Estilo dos campos de entrada no formulário */
        input[type="text"],
        input[type="password"] {
            width: 100%; /* Largura completa dentro do contêiner */
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px; /* Bordas arredondadas */
            border: none;
        }

        /* Estilo do botão de registrar */
        input[type="submit"] {
            background-color: #4CAF50; /* Cor de fundo verde para o botão */
            color: white;
            border: none;
            height: 50px; 
            cursor: pointer;
            border-radius: 5px; /* Bordas arredondadas */
            width: 100%; /* Largura completa do botão */
            font-size: 18px; /* Aumenta o tamanho da fonte */
            text-align: center;
            line-height: 1.4;
        }

        /* Adiciona transição suave ao hover */
        input[type="submit"]:hover {
            opacity: 0.8; /* Efeito de opacidade no hover */
        }

        /* Estilo do link de login */
        a {
            color: #FFD700; /* Cor dourada para o link de login */
        }

        /* Estilo para mensagens de erro ou sucesso */
        .message {
            color: #FFD700; /* Cor dourada */
            margin-bottom: 15px; /* Margem abaixo da mensagem */
        }
    </style>
</head>
<body>

    <div class="register-container">
        <h2>Registrar Novo Usuário</h2>
        <!-- Exibe a mensagem de erro ou sucesso, se houver -->
        <?php if (!empty($error_message)): ?>
            <p class="message"><?php echo $error_message; ?></p>
        <?php endif; ?>
        
        <form method="post" action="">
            <label for="username">Nome de Usuário:</label>
            <input type="text" id="username" name="username" required <?php echo ($total_users >= 10) ? 'disabled' : ''; ?>>

            <label for="password">Senha:</label>
            <input type="password" id="password" name="password" required <?php echo ($total_users >= 10) ? 'disabled' : ''; ?>>

            <input type="submit" value="Registrar" <?php echo ($total_users >= 10) ? 'disabled' : ''; ?>>
        </form>

        <p>Já tem uma conta? <a href="index.php">Login</a></p>
    </div>

</body>
</html>

