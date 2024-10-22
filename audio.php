<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: index.php'); // Redireciona se não estiver logado
    exit();
}
// Exibe a mensagem de upload
if (isset($_SESSION['upload_message'])) {
    echo "<script>
        window.onload = function() {
            alert('" . addslashes($_SESSION['upload_message']) . "');
        }
    </script>";

    // Se houver erros, exibe cada erro
    if (isset($_SESSION['upload_errors'])) {
        echo "<script>
            window.onload = function() {
                let errors = '" . implode("\\n", array_map('addslashes', $_SESSION['upload_errors'])) . "';
                alert(errors);
            }
        </script>";
        unset($_SESSION['upload_errors']);
    }

    unset($_SESSION['upload_message']); // Limpa a mensagem após exibir
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

// Obter o nome do usuário
$user = $_SESSION['username'];

// Consultar todos os áudios do usuário
$query = "SELECT file_path, file_name FROM files WHERE category = 'audios' AND username = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$audios = $result->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Lógica de remoção e atualização do título
// Lógica de remoção e atualização do título
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove'])) {
        // Remover áudio
        $fileNameToRemove = $_POST['file_name'];
        $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
        $removeQuery = "DELETE FROM files WHERE file_name = ? AND username = ?";
        $removeStmt = $conn->prepare($removeQuery);
        $removeStmt->bind_param("ss", $fileNameToRemove, $user);
        $removeStmt->execute();
        $removeStmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Áudio removido com sucesso!']);
        exit();
    } elseif (isset($_POST['update_title'])) {
        // Atualizar título
        $newTitle = $_POST['new_title'];
        $currentFileName = $_POST['current_file_name'];

        // Conectar novamente ao banco de dados
        $conn = new mysqli($servername, $dbUsername, $dbPassword, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Função para verificar se o nome já existe e gerar um novo nome se necessário
        function getUniqueFileName($conn, $newTitle, $user) {
            $baseName = pathinfo($newTitle, PATHINFO_FILENAME);
            $extension = pathinfo($newTitle, PATHINFO_EXTENSION);
            $count = 0;
            $uniqueName = $newTitle;

            // Loop até encontrar um nome único
            while (true) {
                $query = "SELECT COUNT(*) FROM files WHERE file_name = ? AND username = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ss", $uniqueName, $user);
                $stmt->execute();
                $stmt->bind_result($exists);
                $stmt->fetch();
                $stmt->close();

                if ($exists == 0) {
                    break; // Nome é único, podemos usar
                }

                $count++;
                $uniqueName = $baseName . '_' . $count . '.' . $extension; // Gera novo nome
            }

            return $uniqueName;
        }

        // Obtém um nome único
        $uniqueTitle = getUniqueFileName($conn, $newTitle, $user);

        // Atualiza o nome do arquivo
        $updateQuery = "UPDATE files SET file_name = ? WHERE file_name = ? AND username = ?";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->bind_param("sss", $uniqueTitle, $currentFileName, $user);
        $updateStmt->execute();
        $updateStmt->close();
        $conn->close();
        echo json_encode(['success' => true, 'message' => 'Título atualizado com sucesso!']);
        exit();
    }
}

?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Galeria de Áudios</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
    <style>
    .audio-item {
        display: inline-block;
        margin: 10px; /* Espaçamento entre os áudios */
        text-align: center; /* Centraliza o texto abaixo do áudio */
        max-width: 23%; /* Limita a largura para 4 áudios por linha */
    }

    audio {
        width: 100%; /* Largura do player de áudio */
        border-radius: 8px; /* Bordas arredondadas (opcional) */
    }

    .gallery {
        display: flex; /* Usar flexbox para organizar os áudios */
        flex-wrap: wrap; /* Permite que os áudios se ajustem na linha */
        justify-content: center; /* Centraliza os áudios no contêiner */
    }

    .modal {
        display: none; /* Oculta o modal por padrão */
        position: fixed; /* Fixa o modal na tela */
        z-index: 1000; /* Coloca o modal acima de outros elementos */
        left: 0;
        top: 0;
        width: 100%; /* Largura total */
        height: 100%; /* Altura total */
        overflow: auto; /* Adiciona rolagem se necessário */
        background-color: rgb(0, 0, 0); /* Cor de fundo preta */
        background-color: rgba(0, 0, 0, 0.4); /* Cor de fundo com opacidade */
    }

    .modal-content {
        background-color: #5c44a4; /* Altera a cor de fundo do modal */
        color: white; /* Altera a cor do texto para branco */
        margin: 15% auto; /* Margem superior e centraliza horizontalmente */
        padding: 20px;
        border: 1px solid #888;
        width: 80%; /* Largura do modal */
        text-align: center; /* Centraliza o texto dentro do modal */
    }

    /* Estilos para o botão de remover, salvar e cancelar */
    .remove-button,
    .save-button,
    .cancel-button,
    .download-button {
        margin-top: 5px; /* Margem superior dos botões */
        border: none; /* Sem bordas */
        padding: 2px; /* Ajuste de altura */
        border-radius: 5px; /* Bordas arredondadas */
        cursor: pointer; /* Muda o cursor para indicar que é clicável */
        width: 103px; /* Largura do botão remover (90px + 15%) */
    }

    .save-button {
        background-color: #4CAF50; /* Cor de fundo para salvar */
        color: white; /* Cor do texto */
    }

    .remove-button {
        background-color: #241c3c; /* Cor de fundo para remover */
        color: white; /* Cor do texto */
    }

    .download-button {
        background-color: #6044a4; /* Cor de fundo para baixar */
        color: white; /* Cor do texto */
        width: 70px; /* Ajustar largura do botão de baixar */
        padding: 2px; /* Manter o padding para o mesmo formato */
    }

    /* Adiciona transição ao hover dos botões */
    .remove-button:hover,
    .save-button:hover,
    .cancel-button:hover,
    .download-button:hover {
        opacity: 0.8; /* Reduz a opacidade ao passar o mouse */
    }

    /* Limita o tamanho do título do áudio e adiciona reticências */
    .audio-title {
        white-space: nowrap; /* Não quebra linha */
        overflow: hidden; /* Esconde o excesso */
        text-overflow: ellipsis; /* Adiciona reticências */
        max-width: 150px; /* Limita a largura do título do áudio */
        margin: 5px 0; /* Margem para separação */
    }

    /* Media query para telas menores */
    @media (max-width: 768px) {
        .audio-item {
            max-width: 100%; /* Em telas menores, ocupa toda a largura */
        }
        
        audio {
            width: 100%; /* Garante que o reprodutor de áudio ocupe toda a largura */
            min-width: 250px; /* Largura mínima para o reprodutor de áudio em dispositivos móveis */
        }
    }
</style>

</head>
<body class="is-preload">

    <!-- Header -->
    <header id="header">
        <a href="dashboard.php" class="title">Página inicial</a>
        <nav>
            <ul>
                <li>Galeria de Áudios</li>
                <li>User: <?php echo htmlspecialchars($user);?></li>
            </ul>
        </nav>
    </header>

    <!-- Wrapper -->
    <div id="wrapper">

        <!-- Main -->
        <section id="main" class="wrapper">
            <div class="gallery">
                <?php foreach ($audios as $audio): ?>
                    <div class="audio-item">
                        <audio controls>
                            <source src="<?php echo htmlspecialchars($audio['file_path']); ?>" type="audio/mpeg">
                            Seu navegador não suporta o elemento de áudio.
                        </audio>
                        <div class="audio-title" ondblclick="openModal('<?php echo htmlspecialchars($audio['file_name']); ?>', '<?php echo htmlspecialchars($audio['file_path']); ?>', this)">
                            <?php echo htmlspecialchars($audio['file_name']); ?>
                        </div>
                        <a href="<?php echo htmlspecialchars($audio['file_path']); ?>" download>
                            <button class="download-button">Baixar</button>
                        </a>
                        <button class="remove-button" onclick="removeAudio('<?php echo htmlspecialchars($audio['file_name']); ?>')">Remover</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Modal para edição do título -->
            <div id="myModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">&times;</span>
                    <h2>Editar Título</h2>
                    <input type="text" id="editTitle" class="input-title" placeholder="Novo título" />
                    <div>
                        <button class="save-button" id="saveButton">Salvar</button>
                        <button class="cancel-button" onclick="closeModal()">Cancelar</button>
                    </div>
                </div>
            </div>
        </section>
        
      <section id="upload" class="wrapper style3">
    <div class="inner">
        <h2>Fazer Upload de Arquivos</h2>
        <form method="post" action="upload.php" enctype="multipart/form-data">
            <div class="fields">
                <div class="field">
                    <label for="file">Selecione os arquivos:</label>
                    <input type="file" name="files[]" id="file" multiple required>
                </div>
            </div>
            <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>"> <!-- Armazena a página de origem -->
            <ul class="actions">
                <li><input type="submit" value="Fazer Upload" class="primary" /></li>
            </ul>
        </form>
    </div>
</section>

	 <!-- Footer -->
<footer id="footer" class="wrapper alt">
    <div class="inner">
        <ul class="menu">
            <li>&copy; Untitled. All rights reserved.</li>
            <li>Design: <a href="http://html5up.net">HTML5 UP</a></li>
        </ul>
    </div>
</footer>

</div>

<script>
    let currentAudioFileName = '';

    function openModal(fileName, filePath, element) {
        currentAudioFileName = fileName;
        const modal = document.getElementById("myModal");
        const editTitle = document.getElementById("editTitle");
        editTitle.value = fileName; // Preenche o campo com o nome atual do arquivo
        modal.style.display = "block"; // Exibe o modal
    }

    function closeModal() {
        const modal = document.getElementById("myModal");
        modal.style.display = "none"; // Oculta o modal
    }

    function removeAudio(fileName) {
        if (confirm('Tem certeza que deseja remover este áudio?')) {
            const xhr = new XMLHttpRequest();
            xhr.open("POST", "audio.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    alert(response.message);
                    location.reload(); // Atualiza a página após remoção
                }
            };
            xhr.send("remove=true&file_name=" + encodeURIComponent(fileName));
        }
    }

    document.getElementById("saveButton").addEventListener("click", function () {
        const newTitle = document.getElementById("editTitle").value;
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "audio.php", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4 && xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                alert(response.message);
                location.reload(); // Atualiza a página após a atualização do título
            }
        };
        xhr.send("update_title=true&new_title=" + encodeURIComponent(newTitle) + "&current_file_name=" + encodeURIComponent(currentAudioFileName));
    });
</script>

</body>
</html>


