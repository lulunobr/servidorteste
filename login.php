<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "cielopulse";
$dbname = "meu_sistema_de_arquivos";

// Conecta ao banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    // Consulta para verificar o usuário
    $stmt = $conn->prepare("SELECT password FROM users WHERE username=?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($pass, $row['password'])) {
            $_SESSION['username'] = $user; // Salva o nome de usuário na sessão
            header('Location: dashboard.php');
            exit();
        } else {
            $_SESSION['error_message'] = "Senha incorreta!";
            header('Location: index.php');
            exit();
        }
    } else {
        $_SESSION['error_message'] = "Usuário não encontrado!";
        header('Location: index.php');
        exit();
    }
}

$conn->close();
?>

