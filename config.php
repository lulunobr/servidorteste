<?php
// Configurações do banco de dados
$host = "localhost";  // ou o endereço do seu servidor de banco de dados
$db   = "meu_sistema_de_arquivos";  // nome do banco de dados
$user = "root";  // usuário do banco de dados
$pass = "cielopulse";    // senha do banco de dados

// Conexão com o banco de dados
$conn = new mysqli($host, $user, $pass, $db);

// Verificar a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}
?>

