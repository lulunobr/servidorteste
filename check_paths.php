<?php
// Conectar ao banco de dados
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "cielopulse";
$dbname = "meu_sistema_de_arquivos";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Consultar todos os caminhos de arquivos
$sql = "SELECT file_path FROM files";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Caminhos de Arquivos</h2>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        $filePath = $row['file_path'];
        // Verificar se o caminho está no formato correto
        if (preg_match("/^uploads\/(images|videos|audios)\//", $filePath)) {
            echo "<li style='color: green;'>$filePath - Válido</li>";
        } else {
            echo "<li style='color: red;'>$filePath - Inválido</li>";
        }
    }
    echo "</ul>";
} else {
    echo "Nenhum arquivo encontrado.";
}

$conn->close();
?>

