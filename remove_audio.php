<?php
session_start();

if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redireciona se não estiver logado
    exit();
}

// Conectar ao banco de dados
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "cielopulse";
$dbname = "meu_sistema_de_arquivos";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verifica se o nome do arquivo foi passado
if (isset($_POST['file_name'])) {
    $fileName = $_POST['file_name'];
    $user = $_SESSION['username'];

    // Consulta para obter o caminho do arquivo
    $query = "SELECT file_path FROM files WHERE file_name = ? AND username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $fileName, $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $file = $result->fetch_assoc();
        $filePath = $file['file_path'];

        // Remove o arquivo do sistema de arquivos
        if (file_exists($filePath)) {
            unlink($filePath); // Remove o arquivo do servidor
        }

        // Remove o registro do banco de dados
        $deleteQuery = "DELETE FROM files WHERE file_name = ? AND username = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("ss", $fileName, $user);
        $deleteStmt->execute();
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Arquivo não encontrado.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Nome do arquivo não fornecido.']);
}

$conn->close();
?>

