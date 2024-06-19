<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes</title>
    <link rel="stylesheet" href="css/clientes.css">
</head>
<body>
<?php
include 'PHP/connect.php';
include 'PHP/session.php';

// Verifica se o usuário está logado
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Busca o nome do usuário no banco de dados
    $sql = "SELECT Nome FROM usuarios WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = ucfirst($user['Nome']);
    } else {
        // Se não encontrar o usuário, redireciona para a página de login
        header("Location: index.php");
        exit();
    }
} else {
    // Se não estiver logado, redireciona para a página de login
    header("Location: index.php");
    exit();
}

// Processa a exclusão de cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    $sql_delete = "DELETE FROM clientes WHERE id = ? AND dono = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $delete_id, $user_id);
    $stmt_delete->execute();
    header("Location: clientes.php");
    exit();
}

// Processa a edição de cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $edit_id = $_POST['edit_id'];
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $pet = isset($_POST['pet']) ? 1 : 0;

    $sql_edit = "UPDATE clientes SET Nome = ?, telefone = ?, pet = ? WHERE id = ? AND dono = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    $stmt_edit->bind_param("ssiii", $nome, $telefone, $pet, $edit_id, $user_id);
    $stmt_edit->execute();
    header("Location: clientes.php");
    exit();
}

// Processa a adição de novo cliente
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_cliente'])) {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $pet = isset($_POST['pet']) ? 1 : 0;

    $sql_add = "INSERT INTO clientes (Nome, telefone, pet, dono) VALUES (?, ?, ?, ?)";
    $stmt_add = $conn->prepare($sql_add);
    $stmt_add->bind_param("ssii", $nome, $telefone, $pet, $user_id);
    $stmt_add->execute();
    header("Location: clientes.php");
    exit();
}

// Busca os clientes do usuário logado
$sql = "SELECT * FROM clientes WHERE dono = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$clientes = $stmt->get_result();
?>
    <div class="header">
        <button class="logout-button" onclick="deslogar()">Deslogar</button>
        <div class="site-title">RentalMate</div>
        <div class="user-info">
            <?php echo htmlspecialchars($user_name); ?>
        </div>
    </div>
    <div class="container">
        <div class="page-header">
            <button class="back-button" onclick="voltar()">Voltar</button>
            <h1>Meus Clientes</h1>
        </div>
        <div class="client-list">
            <?php
            if ($clientes->num_rows > 0) {
                while ($cliente = $clientes->fetch_assoc()) {
                    echo '<div class="client-item">';
                    echo '<h3>' . htmlspecialchars($cliente['Nome']) . '</h3>';
                    echo '<p>Telefone: ' . htmlspecialchars($cliente['telefone']) . '</p>';
                    echo '<p>Pet: ' . ($cliente['pet'] ? 'Sim' : 'Não') . '</p>';
                    echo '<button class="edit-button" onclick="openEditModal(' . $cliente['id'] . ', \'' . htmlspecialchars($cliente['Nome']) . '\', \'' . htmlspecialchars($cliente['telefone']) . '\', ' . $cliente['pet'] . ')">Editar</button>';
                    echo '<form action="clientes.php" method="post" onsubmit="return confirmDelete()" style="display:inline-block;">
                            <input type="hidden" name="delete_id" value="' . $cliente['id'] . '">
                          </form>';
                    echo '</div>';
                }
            } else {
                echo '<p>Não há clientes cadastrados.</p>';
            }
            ?>
        </div>
        <div class="button-container">
            <button class="add-button" onclick="openAddModal()">Adicionar Novo Cliente</button>
        </div>
    </div>

    <!-- Modal para edição de cliente -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeEditModal()">&times;</span>
            <h2>Editar Cliente</h2>
            <form action="clientes.php" method="post">
                <input type="hidden" id="edit_id" name="edit_id">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
                <label for="telefone">Telefone:</label>
                <input type="text" id="telefone" name="telefone" required>
                <label for="pet">Pet:</label>
                <input type="checkbox" id="pet" name="pet">
                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>

    <!-- Modal para adição de novo cliente -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddModal()">&times;</span>
            <h2>Adicionar Novo Cliente</h2>
            <form action="clientes.php" method="post">
                <input type="hidden" name="add_cliente" value="1">
                <label for="add_nome">Nome:</label>
                <input type="text" id="add_nome" name="nome" required>
                <label for="add_telefone">Telefone:</label>
                <input type="text" id="add_telefone" name="telefone" required>
                <label for="add_pet">Pet:</label>
                <input type="checkbox" id="add_pet" name="pet">
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </div>

    <script>
        function deslogar(){
            window.location.href="index.php"
        }

        function openEditModal(id, nome, telefone, pet) {
            document.getElementById('edit_id').value = id;
            document.getElementById('nome').value = nome;
            document.getElementById('telefone').value = telefone;
            document.getElementById('pet').checked = pet == 1 ? true : false;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function voltar() {
            window.history.back();
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('editModal')) {
                closeEditModal();
            } else if (event.target == document.getElementById('addModal')) {
                closeAddModal();
            }
        }
    </script>
</body>
</html>
