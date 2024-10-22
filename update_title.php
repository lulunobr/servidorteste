<?php
// Conexão com o banco de dados
$servername = "localhost";
$usernameDB = "root"; // Nome de usuário do banco de dados
$passwordDB = ""; // Senha do banco de dados
$dbname = "meu_sistema_de_arquivos";

// Conectando ao banco de dados
$conn = new mysqli($servername, $usernameDB, $passwordDB, $dbname);

// Verificando a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Verifica se a requisição é para atualizar o título do arquivo
if (isset($_POST['update_title'])) {
    $oldTitle = $_POST['current_file_name']; // Nome original do arquivo
    $newTitle = $_POST['new_title']; // Novo título desejado
    $username = $_SESSION['username']; // Nome do usuário logado
    $category = $_POST['category']; // Categoria do arquivo (audio, video ou images)

    // Verifica se o título está vazio. Se estiver, mantém o título original.
    if (empty($newTitle)) {
        $newTitle = $oldTitle;
    }

    // Remove espaços em branco e substitui por underscores
    $newTitle = str_replace(' ', '_', $newTitle);

    // Adiciona a extensão ao novo título
    $fileInfo = pathinfo($oldTitle);
    $extension = isset($fileInfo['extension']) ? "." . $fileInfo['extension'] : ""; // Captura a extensão do arquivo original
    $newTitle .= $extension;

    // Verifica se já existe um arquivo com o novo nome para o usuário
    $query = "SELECT COUNT(*) AS count FROM files WHERE file_name = ? AND username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $newTitle, $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        // Se já existe um arquivo com o mesmo nome, adiciona um número ao título
        $count = 1;
        $baseName = $fileInfo['filename']; // Nome base do arquivo sem extensão
        do {
            $newTitle = $baseName . "_" . $count . $extension;
            $query = "SELECT COUNT(*) AS count FROM files WHERE file_name = ? AND username = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $newTitle, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $count++;
        } while ($row['count'] > 0);
    }

    // Caminhos antigos e novos dos arquivos
    $oldFilePath = "uploads/$category/" . $oldTitle;
    $newFilePath = "uploads/$category/" . $newTitle;

    // Renomeia o arquivo físico no servidor
    if (rename($oldFilePath, $newFilePath)) {
        // Atualiza o nome do arquivo no banco de dados, garantindo que estamos atualizando o registro correto
        $query = "UPDATE files SET file_name = ?, file_path = ? WHERE file_name = ? AND username = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $newTitle, $newFilePath, $oldTitle, $username);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "newTitle" => $newTitle]);
        } else {
            echo json_encode(["success" => false, "error" => $conn->error]);
        }
    } else {
        echo json_encode(["success" => false, "error" => "Erro ao renomear o arquivo."]);
    }

    $stmt->close();
}

$conn->close();
?>

