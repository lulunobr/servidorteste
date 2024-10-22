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

// Verifica se o nome do vídeo foi enviado
if (isset($_POST['video_name'])) {
    $videoName = $_POST['video_name'];
    
    // Consulta para obter o caminho do vídeo
    $query = "SELECT file_path FROM files WHERE file_name = ? AND category = 'videos' AND username = ?";
    $stmt = $conn->prepare($query);
    
    // Bind parameters
    $username = $_SESSION['username'];
    $stmt->bind_param("ss", $videoName, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $video = $result->fetch_assoc();
        $filePath = $video['file_path'];
        
        // Remove o arquivo do sistema de arquivos
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Remove o registro do banco de dados
        $deleteQuery = "DELETE FROM files WHERE file_name = ? AND username = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param("ss", $videoName, $username);
        $deleteStmt->execute();
        
        echo "Vídeo removido com sucesso!";
    } else {
        echo "Vídeo não encontrado!";
    }
} else {
    echo "Nenhum vídeo foi especificado para remoção.";
}

$conn->close();
?>

