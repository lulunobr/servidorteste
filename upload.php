<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES["files"])) {
    $targetDir = "/var/www/html/uploads/";
    $username = $_SESSION['username'];
    $uploadSuccess = true;
    $errors = []; // Armazenará os erros específicos de upload
    $maxCategorySize = 1 * 1024 * 1024 * 1024; // 1 GB em bytes

    // Verifica se a URL de redirecionamento foi enviada
    $redirectUrl = isset($_POST['redirect_url']) ? $_POST['redirect_url'] : 'dashboard.php';

    // Conectar ao banco de dados
    $servername = "localhost";
    $dbUsername = "root";
    $dbPassword = "cielopulse";
    $dbname = "meu_sistema_de_arquivos";
    $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
    if ($conn->connect_error) {
        die("Erro ao conectar ao banco de dados: " . $conn->connect_error);
    }

    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES["files"]["name"][$key]);
        $fileSize = $_FILES["files"]["size"][$key];
        $fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $category = 'other';
        if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $category = 'images';
        } elseif (in_array($fileType, ['mp4', 'avi', 'mov'])) {
            $category = 'videos';
        } elseif (in_array($fileType, ['mp3', 'wav'])) {
            $category = 'audios';
        }

        // Verificar o espaço já utilizado na categoria
        $stmt = $conn->prepare("SELECT SUM(filesize) as total_size FROM files WHERE username = ? AND category = ?");
        $stmt->bind_param("ss", $username, $category);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $totalSize = $row['total_size'] ?? 0;
        $stmt->close();

        if (($totalSize + $fileSize) > $maxCategorySize) {
            $errors[] = "O total de arquivos na categoria '{$category}' excede o limite de 1GB.";
            $uploadSuccess = false;
            continue;
        }

        if ($_FILES["files"]["error"][$key] !== UPLOAD_ERR_OK) {
            $errors[] = "Erro no upload do arquivo '{$fileName}': " . $_FILES["files"]["error"][$key];
            $uploadSuccess = false;
            continue;
        }

        $allowedFileTypes = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'mp3', 'wav'];
        if (!in_array($fileType, $allowedFileTypes)) {
            $errors[] = "Tipo de arquivo não permitido para '{$fileName}'";
            $uploadSuccess = false;
            continue;
        }

        $categoryDir = $targetDir . $category . '/';
        if (!file_exists($categoryDir)) {
            mkdir($categoryDir, 0755, true);
        }

        $newFileName = $fileName;
        $counter = 1;
        while (file_exists($categoryDir . $newFileName)) {
            $newFileName = pathinfo($fileName, PATHINFO_FILENAME) . "_" . $counter . "." . $fileType;
            $counter++;
        }

        $finalTargetFile = $categoryDir . $newFileName;

        if (move_uploaded_file($tmpName, $finalTargetFile)) {
            $stmt = $conn->prepare("INSERT INTO files (file_name, file_path, filesize, category, username) VALUES (?, ?, ?, ?, ?)");
            $filePath = "uploads/" . $category . '/' . $newFileName;
            $stmt->bind_param("ssiss", $newFileName, $filePath, $fileSize, $category, $username);

            if (!$stmt->execute()) {
                $errors[] = "Erro ao inserir dados no banco para '{$newFileName}'";
                $uploadSuccess = false;
            }

            $stmt->close();
        } else {
            $errors[] = "Erro ao mover o arquivo '{$fileName}'";
            $uploadSuccess = false;
        }
    }

    $conn->close();

    // Definir as mensagens de sucesso ou erro
    if ($uploadSuccess) {
        $_SESSION['upload_message'] = "Upload feito com sucesso!";
    } else {
        $_SESSION['upload_message'] = "Ocorreram erros durante o upload:";
        $_SESSION['upload_errors'] = $errors;
    }

    // Redirecionar para a página de origem
    header("Location: $redirectUrl");
    exit();
} else {
    echo "Nenhum arquivo enviado ou método de requisição inválido.";
}
?>

