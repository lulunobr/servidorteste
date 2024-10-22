<?php
session_start();

// Conectar ao banco de dados
$servername = "localhost";
$dbUsername = "root";
$dbPassword = "cielopulse";
$dbname = "meu_sistema_de_arquivos";

$conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Consultar todos os usuários
$query = "SELECT username FROM users";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $username = $row['username'];
        
        // Consultar todos os arquivos do usuário
        $fileQuery = "SELECT file_path FROM files WHERE username = ?";
        $fileStmt = $conn->prepare($fileQuery);
        $fileStmt->bind_param("s", $username);
        $fileStmt->execute();
        $fileResult = $fileStmt->get_result();
        
        // Remover os arquivos do sistema de arquivos
        while ($fileRow = $fileResult->fetch_assoc()) {
            $filePath = $fileRow['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath); // Remove o arquivo
            }
        }
        
        // Remover os arquivos do banco de dados
        $deleteFilesQuery = "DELETE FROM files WHERE username = ?";
        $deleteFilesStmt = $conn->prepare($deleteFilesQuery);
        $deleteFilesStmt->bind_param("s", $username);
        $deleteFilesStmt->execute();
    }

    // Remover todos os usuários do banco de dados
    $deleteUsersQuery = "DELETE FROM users";
    $conn->query($deleteUsersQuery);

    echo "Todos os usuários e seus arquivos foram removidos com sucesso.";
} else {
    echo "Nenhum usuário encontrado.";
}

$conn->close();
?>

