<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Propriedades</title>
    <link rel="stylesheet" href="css/menu.css">
</head>
<body>
<?php
include 'PHP/connect.php';
include 'PHP/session.php';

// Verifica se o usuário está logado
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Busca o nome do usuário no banco de dados
    $sql = "SELECT nome FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = ucfirst($user['nome']);
    } else {
        // Se não encontrar o usuário, redireciona para a página de login
        header("Location: index.html");
        exit();
    }
} else {
    // Se não estiver logado, redireciona para a página de login
    header("Location: index.html");
    exit();
}

// Processa o formulário de adição de nova propriedade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nome']) && isset($_POST['endereco'])) {
    $nome = $_POST['nome'];
    $endereco = $_POST['endereco'];

    $sql = "INSERT INTO imoveis (nome, endereco, dono) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $nome, $endereco, $user_id);
    $stmt->execute();

    $property_id = $stmt->insert_id;
    header("Location: menu.php?new_property_id=$property_id");
    exit();
}

// Processa o upload da imagem da propriedade
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['imagem']) && isset($_POST['property_id'])) {
    $property_id = $_POST['property_id'];
    $target_dir = "images/";
    $target_file = $target_dir . $property_id . ".png";

    // Verifica se houve algum erro no upload
    if ($_FILES['imagem']['error'] != UPLOAD_ERR_OK) {
        echo "Erro no upload: " . $_FILES['imagem']['error'];
        exit();
    }

    // Verifica se o diretório de destino existe
    if (!is_dir($target_dir)) {
        echo "O diretório de destino não existe";
        exit();
    }

    // Verifica se o diretório de destino é gravável
    if (!is_writable($target_dir)) {
        echo "O diretório não é gravável";
        exit();
    }

    // Move o arquivo enviado para o diretório de destino
    if (move_uploaded_file($_FILES['imagem']['tmp_name'], $target_file)) {
        header("Location: menu.php");
    } else {
        echo "Desculpe, houve um erro ao enviar sua imagem.";
    }
    exit();
}



// Busca as propriedades do usuário logado
$sql = "SELECT * FROM imoveis WHERE dono = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$imoveis = $stmt->get_result();
?>
    <div class="header">
        <button class="logout-button" onclick="deslogar()">Deslogar</button>
        <div class="site-title">RentalMate</div>
        <div class="user-info">
            <?php echo htmlspecialchars($user_name); ?>
        </div>
    </div>
    <div class="container">
        <div class="page-title">
            <h1>Minhas Propriedades</h1>
        </div>
        <div class="property-list">
            <?php
            if ($imoveis->num_rows > 0) {
                while ($imovel = $imoveis->fetch_assoc()) {
                    echo '<div class="property-item">';
                    echo '<img src="images/' . htmlspecialchars($imovel['id']) . '.png" alt="Propriedade ' . htmlspecialchars($imovel['id']) . '">';
                    echo '<h2>' . htmlspecialchars($imovel['nome']) . '</h2>';
                    echo '<p>' . htmlspecialchars($imovel['endereco']) . '</p>';
                    echo '<button onclick="propriedade(' . htmlspecialchars($imovel['id']) . ', \'' . htmlspecialchars($imovel['nome']) . '\')">Gerenciar</button>';
                    echo '</div>';
                }
            }
            ?>
        </div>
        <div class="button-container">
            <button class="add-button" onclick="openModal()">Adicionar Nova Propriedade</button>
        </div>
    </div>

    <!-- Modal para adicionar nova propriedade -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h2>Adicionar Nova Propriedade</h2>
            <form action="menu.php" method="post">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
                <label for="endereco">Endereço:</label>
                <input type="text" id="endereco" name="endereco" required>
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </div>

    <!-- Modal para enviar imagem da propriedade -->
    <?php if (isset($_GET['new_property_id'])): ?>
    <div id="imageModal" class="modal" style="display: block;">
        <div class="modal-content">
            <span class="close-button" onclick="closeImageModal()">&times;</span>
            <h2>Enviar Imagem da Propriedade</h2>
            <form action="menu.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="property_id" value="<?php echo htmlspecialchars($_GET['new_property_id']); ?>">
                <label for="imagem">Imagem:</label>
                <input type="file" id="imagem" name="imagem" accept="image/*" required>
                <button type="submit">Enviar</button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <script>
   
        function deslogar(){
            window.location.href="index.html"
        }

        function propriedade(id, nome){
            // Codifica o nome do imóvel para inclusão na URL
            const nomeCodificado = encodeURIComponent(nome);
            // Redireciona para a página de detalhes da propriedade com o nome do imóvel na URL
            window.location.href = "property.php?id=" + id + "&nome=" + nomeCodificado;
        }

        function openModal() {
            document.getElementById('modal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        function closeImageModal() {
            document.getElementById('imageModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('modal')) {
                closeModal();
            } else if (event.target == document.getElementById('imageModal')) {
                closeImageModal();
            }
        }
    </script>
</body>
</html>
