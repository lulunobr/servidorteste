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

// Consultar arquivos com caminhos inválidos
$sql = "SELECT id, file_path FROM files";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $filePath = $row['file_path'];
        $id = $row['id'];

        // Corrigir o caminho se necessário
        if (strpos($filePath, '/var/www/html/uploads/') === 0) {
            // Remove a parte inicial do caminho
            $newPath = str_replace('/var/www/html/', '', $filePath);
            $newPath = ltrim($newPath, '/'); // Remove qualquer '/' no início

            // Atualiza o caminho no banco de dados
            $stmt = $conn->prepare("UPDATE files SET file_path = ? WHERE id = ?");
            $stmt->bind_param("si", $newPath, $id);
            $stmt->execute();
        }
    }
    echo "Caminhos inválidos atualizados com sucesso.";
} else {
    echo "Nenhum arquivo encontrado.";
}

$conn->close();
?>

