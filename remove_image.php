<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado.']);
    exit();
}

$servername = "localhost";
$dbUsername = "root";
$dbPassword = "cielopulse";
$dbname = "meu_sistema_de_arquivos";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$fileName = $_POST['fileName'];
$username = $_SESSION['username'];

// Consultar o caminho do arquivo
$query = "SELECT file_path FROM files WHERE file_name = ? AND username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $fileName, $username);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

if ($file) {
    // Remover o arquivo do sistema de arquivos
    $filePath = $file['file_path'];
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    // Remover a entrada do banco de dados
    $deleteQuery = "DELETE FROM files WHERE file_name = ? AND username = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    $deleteStmt->bind_param("ss", $fileName, $username);
    $deleteStmt->execute();

    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Arquivo não encontrado.']);
}

$conn->close();
?>

