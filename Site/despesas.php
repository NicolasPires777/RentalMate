<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Despesas</title>
    <link rel="stylesheet" href="css/despesas.css">
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

// Recupera o ID do imóvel da URL
$property_id = isset($_GET['property_id']) ? $_GET['property_id'] : '';
$property_name = isset($_GET['nome']) ? $_GET['nome'] : '';

// Filtro de mês e ano
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Processa o formulário de adição de nova despesa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['titulo']) && isset($_POST['descricao']) && isset($_POST['valor']) && isset($_POST['date']) && isset($_POST['categoria'])) {
    $titulo = $_POST['titulo'];
    $descricao = $_POST['descricao'];
    $valor = $_POST['valor'];
    $date = $_POST['date'];
    $categoria = $_POST['categoria'];

    $sql = "INSERT INTO despesas (imovel, titulo, descricao, valor, date, categoria) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issdss", $property_id, $titulo, $descricao, $valor, $date, $categoria);
    $stmt->execute();
    header("Location: despesas.php?property_id=$property_id&nome=" . urlencode($property_name));
    exit();
}

// Processa o pedido de exclusão de despesa
if (isset($_GET['delete_id'])) {
    $delete_id = $_GET['delete_id'];

    $sql = "DELETE FROM despesas WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    header("Location: despesas.php?property_id=$property_id&nome=" . urlencode($property_name));
    exit();
}

// Função para formatar datas
function formatDate($date) {
    return date("d/m/Y", strtotime($date));
}
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
            <button class="back-button" onclick="window.location.href='property.php?id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>'">Voltar</button>
            <h1>Despesas de <?php echo htmlspecialchars($property_name); ?></h1>
            <button class="add-expense-button" onclick="openAddExpenseModal()">Adicionar</button>
        </div>
        <div class="filter-form">
            <form action="despesas.php" method="get">
                <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
                <input type="hidden" name="nome" value="<?php echo htmlspecialchars($property_name); ?>">
                <label for="month">Mês:</label>
                <select name="month" id="month" required>
                    <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?php echo $m; ?>" <?php if ($month == $m) echo 'selected'; ?>><?php echo date('F', mktime(0, 0, 0, $m, 10)); ?></option>
                    <?php endfor; ?>
                </select>
                <label for="year">Ano:</label>
                <select name="year" id="year" required>
                    <?php for ($y = date('Y'); $y >= 2000; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php if ($year == $y) echo 'selected'; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit">Filtrar</button>
            </form>
        </div>
        <div class="expense-categories">
            <div class="category">
                <h2>Manutenção</h2>
                <div class="expense-list">
                    <?php
                    $sql_despesas_categoria = "SELECT * FROM despesas WHERE imovel = ? AND categoria = 'Manutencao' AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC";
                    $stmt_despesas_categoria = $conn->prepare($sql_despesas_categoria);
                    $stmt_despesas_categoria->bind_param("iii", $property_id, $month, $year);
                    $stmt_despesas_categoria->execute();
                    $despesas_categoria = $stmt_despesas_categoria->get_result();

                    if ($despesas_categoria->num_rows > 0) {
                        while ($despesa = $despesas_categoria->fetch_assoc()) {
                            echo '<div class="expense-item">';
                            echo '<div class="expense-top-bar"></div>'; // Borda azul no topo
                            echo '<h3>' . htmlspecialchars($despesa['titulo']) . '</h3>';
                            echo '<p>Descrição: ' . htmlspecialchars($despesa['descricao']) . '</p>';
                            echo '<p>Data: ' . htmlspecialchars(formatDate($despesa['date'])) . '</p>';
                            echo '<p>Valor: R$ ' . htmlspecialchars(number_format($despesa['valor'], 2, ',', '.')) . '</p>';
                            echo '<button class="delete-button" onclick="confirmDelete(' . htmlspecialchars($despesa['id']) . ')">Cancelar</button>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>Não há despesas cadastradas.</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="category">
                <h2>Gastos Fixos</h2>
                <div class="expense-list">
                    <?php
                    $sql_despesas_categoria = "SELECT * FROM despesas WHERE imovel = ? AND categoria = 'Fixos' AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC";
                    $stmt_despesas_categoria = $conn->prepare($sql_despesas_categoria);
                    $stmt_despesas_categoria->bind_param("iii", $property_id, $month, $year);
                    $stmt_despesas_categoria->execute();
                    $despesas_categoria = $stmt_despesas_categoria->get_result();

                    if ($despesas_categoria->num_rows > 0) {
                        while ($despesa = $despesas_categoria->fetch_assoc()) {
                            echo '<div class="expense-item">';
                            echo '<div class="expense-top-bar"></div>'; // Borda azul no topo
                            echo '<h3>' . htmlspecialchars($despesa['titulo']) . '</h3>';
                            echo '<p>Descrição: ' . htmlspecialchars($despesa['descricao']) . '</p>';
                            echo '<p>Data: ' . htmlspecialchars(formatDate($despesa['date'])) . '</p>';
                            echo '<p>Valor: R$ ' . htmlspecialchars(number_format($despesa['valor'], 2, ',', '.')) . '</p>';
                            echo '<button class="delete-button" onclick="confirmDelete(' . htmlspecialchars($despesa['id']) . ')">Cancelar</button>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>Não há despesas cadastradas.</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="category">
                <h2>Melhorias</h2>
                <div class="expense-list">
                    <?php
                    $sql_despesas_categoria = "SELECT * FROM despesas WHERE imovel = ? AND categoria = 'Melhorias' AND MONTH(date) = ? AND YEAR(date) = ? ORDER BY date DESC";
                    $stmt_despesas_categoria = $conn->prepare($sql_despesas_categoria);
                    $stmt_despesas_categoria->bind_param("iii", $property_id, $month, $year);
                    $stmt_despesas_categoria->execute();
                    $despesas_categoria = $stmt_despesas_categoria->get_result();

                    if ($despesas_categoria->num_rows > 0) {
                        while ($despesa = $despesas_categoria->fetch_assoc()) {
                            echo '<div class="expense-item">';
                            echo '<div class="expense-top-bar"></div>'; // Borda azul no topo
                            echo '<h3>' . htmlspecialchars($despesa['titulo']) . '</h3>';
                            echo '<p>Descrição: ' . htmlspecialchars($despesa['descricao']) . '</p>';
                            echo '<p>Data: ' . htmlspecialchars(formatDate($despesa['date'])) . '</p>';
                            echo '<p>Valor: R$ ' . htmlspecialchars(number_format($despesa['valor'], 2, ',', '.')) . '</p>';
                            echo '<button class="delete-button" onclick="confirmDelete(' . htmlspecialchars($despesa['id']) . ')">Cancelar</button>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p>Não há despesas cadastradas.</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar despesa -->
    <div id="addExpenseModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddExpenseModal()">&times;</span>
            <h2>Adicionar Despesa</h2>
            <form action="despesas.php?property_id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>" method="post">
                <label for="titulo">Título:</label>
                <input type="text" id="titulo" name="titulo" required>
                <label for="descricao">Descrição:</label>
                <input type="text" id="descricao" name="descricao" required>
                <label for="valor">Valor:</label>
                <input type="number" id="valor" name="valor" step="0.01" required>
                <label for="date">Data:</label>
                <input type="date" id="date" name="date" required>
                <label for="categoria">Categoria:</label>
                <select id="categoria" name="categoria" required>
                    <option value="Manutencao">Manutenção</option>
                    <option value="Fixos">Gastos Fixos</option>
                    <option value="Melhorias">Melhorias</option>
                </select>
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </div>

    <script>
        function deslogar() {
            window.location.href = "index.html";
        }

        function confirmDelete(expenseId) {
            if (confirm('Tem certeza que deseja cancelar esta despesa?')) {
                window.location.href = 'despesas.php?property_id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>&delete_id=' + expenseId;
            }
        }

        function openAddExpenseModal() {
            document.getElementById('addExpenseModal').style.display = 'block';
        }

        function closeAddExpenseModal() {
            document.getElementById('addExpenseModal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('addExpenseModal')) {
                closeAddExpenseModal();
            }
        }
    </script>
</body>
</html>
