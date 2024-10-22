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

// Consultar a quantidade de arquivos em cada seção (convertido para MB)
$queryCounts = [
    "images" => "SELECT COUNT(*) as total, COALESCE(SUM(filesize) / 1024 / 1024, 0) as size FROM files WHERE category = 'images' AND username = ?",
    "videos" => "SELECT COUNT(*) as total, COALESCE(SUM(filesize) / 1024 / 1024, 0) as size FROM files WHERE category = 'videos' AND username = ?",
    "audios" => "SELECT COUNT(*) as total, COALESCE(SUM(filesize) / 1024 / 1024, 0) as size FROM files WHERE category = 'audios' AND username = ?"
];

$counts = [];
foreach ($queryCounts as $type => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $counts[$type] = $result;
}

// Últimos 10 arquivos de cada categoria
$recentFiles = [];
$queryRecent = [
    "images" => "SELECT file_path, file_name FROM files WHERE category = 'images' AND username = ? ORDER BY id DESC LIMIT 10",
    "videos" => "SELECT file_path, file_name FROM files WHERE category = 'videos' AND username = ? ORDER BY id DESC LIMIT 10",
    "audios" => "SELECT file_path, file_name FROM files WHERE category = 'audios' AND username = ? ORDER BY id DESC LIMIT 10"
];

foreach ($queryRecent as $type => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $recentFiles[$type] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$conn->close();
?>

<!DOCTYPE HTML>
<html>
<head>
    <title>Dashboard - Hyperspace Style</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <link rel="stylesheet" href="assets/css/main.css" />
    <noscript><link rel="stylesheet" href="assets/css/noscript.css" /></noscript>
    <style>
    /* Ajustando a grid para vídeos e imagens */
.file-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* Para imagens e vídeos */
    gap: 1rem;
}

.file-item {
    background: #281c3c;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    text-align: center;
    padding: 10px;
    height: 220px; /* Defina uma altura fixa para manter o tamanho consistente */
}

.file-img {
    max-width: 100%;
    max-height: 150px; /* Ajuste a altura máxima para vídeos e imagens */
    object-fit: cover; /* Mantém a proporção da imagem/vídeo */
}

.file-title {
    margin-top: 5px;
    font-weight: bold;
    max-width: 200px; /* Limita a largura */
    white-space: nowrap; /* Não permitir quebra de linha */
    overflow: hidden; /* Ocultar o texto que excede o limite */
    text-overflow: ellipsis; /* Adicionar reticências (...) para indicar que o texto foi cortado */
}

/* Ajustando para áudios */
.audio-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr); /* Para dois áudios por linha */
    gap: 1rem;
}

.audio-item {
    background: #281c3c;
    border: 1px solid #ddd;
    border-radius: 4px; 
    overflow: hidden;
    text-align: center;
    padding: 10px;
    height: 120px; /* Ajuste a altura para os itens de áudio */
}

.audio-img {
    max-width: 100%;
    max-height: 50px; /* Ajuste a altura máxima para o elemento de áudio */
}

</style>


</head>
<body class="is-preload">

<!-- Sidebar -->
<section id="sidebar">
    <div class="inner">
        <nav>
            <ul>
                <li>Bem-vindo, <?php echo htmlspecialchars($user); ?></li>
            
                <li><a href="images.php" class="category-link">Imagens: <?php echo $counts['images']['total']; ?> (<?php echo round($counts['images']['size'], 2); ?>MB / 1GB)</a></li>
                <li><a href="video.php" class="category-link">Vídeos: <?php echo $counts['videos']['total']; ?> (<?php echo round($counts['videos']['size'], 2); ?>MB / 1GB)</a></li>
                <li><a href="audio.php" class="category-link">Áudios: <?php echo $counts['audios']['total']; ?> (<?php echo round($counts['audios']['size'], 2); ?>MB / 1GB)</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </div>
</section>

<!-- Wrapper -->
<div id="wrapper">

    <!-- Dashboard Section -->
    <section id="dashboard" class="wrapper style1 fullscreen fade-up">
        <div class="inner">
            <h1>Bem-vindo ao seu drive pessoal</h1>
            <h2>Usuário: <?php echo htmlspecialchars($user); ?></h2>

            <!-- Button to Upload Section -->
            <ul class="actions">
                <li><a href="#upload" class="button scrolly">Fazer Upload de Arquivos</a></li>  <li><a href="logout.php" class="button">Logout</a></li>
            </ul>
          
        </div>
    </section>

    <!-- Files Section -->
    <section id="files" class="wrapper style2 spotlights">
        <section>
    <div class="content">
        <div class="inner">
            <h2>Últimos Arquivos de Imagem Enviados</h2>
            <div class="category-link">
                <a href="images.php" class="button">Imagens: <?php echo $counts['images']['total']; ?> (<?php echo round($counts['images']['size'], 2); ?>MB / 1GB)</a>
            </div>
            <ul class="file-grid" style="margin-top: 20px;"> <!-- Adicionando uma margem superior aqui -->
                <?php foreach ($recentFiles['images'] as $file): ?>
                    <li class="file-item">
                        <img src="<?php echo $file['file_path']; ?>" alt="<?php echo $file['file_name']; ?>" class="file-img">
                        <div class="file-title"><?php echo htmlspecialchars($file['file_name']); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>


       <section>
    <div class="content">
        <div class="inner">
            <h2>Últimos Arquivos de Vídeo Enviados</h2>
            <div class="category-link">
                <a href="video.php" class="button">Vídeos: <?php echo $counts['videos']['total']; ?> (<?php echo round($counts['videos']['size'], 2); ?>MB / 1GB)</a>
            </div>
            <ul class="file-grid" style="margin-top: 20px;"> <!-- Adicionando uma margem superior aqui -->
                <?php foreach ($recentFiles['videos'] as $file): ?>
                    <li class="file-item">
                        <video class="file-img" controls>
                            <source src="<?php echo $file['file_path']; ?>" type="video/mp4">
                            Seu navegador não suporta o elemento de vídeo.
                        </video>
                        <div class="file-title"><?php echo htmlspecialchars($file['file_name']); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>


       <!-- Últimos Arquivos de Áudio Enviados -->
<section>
    <div class="content">
        <div class="inner">
            <h2>Últimos Arquivos de Áudio Enviados</h2>
            <div class="category-link">
                <a href="audio.php" class="button">Áudios: <?php echo $counts['audios']['total']; ?> (<?php echo round($counts['audios']['size'], 2); ?>MB / 1GB)</a>
            </div>
            <ul class="audio-grid" style="margin-top: 20px;">
                <?php foreach ($recentFiles['audios'] as $file): ?>
                    <li class="audio-item">
                        <audio class="audio-img" controls>
                            <source src="<?php echo $file['file_path']; ?>" type="audio/mpeg">
                            Seu navegador não suporta o elemento de áudio.
                        </audio>
                        <div class="file-title"><?php echo htmlspecialchars($file['file_name']); ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</section>



    <!-- Upload Section -->
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



</div>

<!-- Footer -->
<footer id="footer" class="wrapper style1-alt">
    <div class="inner">
        <ul class="menu">
            <li>&copy; Seu Site. Todos os direitos reservados.</li>
            <li>Design: <a href="http://html5up.net">HTML5 UP</a></li>
        </ul>
    </div>
</footer>

<!-- Scripts -->
<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/jquery.scrollex.min.js"></script>
<script src="assets/js/jquery.scrolly.min.js"></script>
<script src="assets/js/browser.min.js"></script>
<script src="assets/js/breakpoints.min.js"></script>
<script src="assets/js/util.js"></script>
<script src="assets/js/main.js"></script>

<!-- Modal para Imagem Expandida -->
<div id="imageModal" class="modal">
    <span class="close" onclick="closeModal()">&times;</span>
    <img class="modal-content" id="expandedImage">
    <div id="caption"></div>
</div>

</body>
</html>

