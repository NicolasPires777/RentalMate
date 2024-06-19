<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamentos</title>
    <link rel="stylesheet" href="css/pagamentos.css">
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
        header("Location: index.php");
        exit();
    }
} else {
    // Se não estiver logado, redireciona para a página de login
    header("Location: index.php");
    exit();
}

// Recupera o ID do imóvel da URL
$property_id = isset($_GET['property_id']) ? $_GET['property_id'] : '';
$property_name = isset($_GET['nome']) ? $_GET['nome'] : '';

// Recupera o mês e ano do filtro
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

// Atualiza o status do aluguel para pago (1)
if (isset($_POST['update_status'])) {
    $aluguel_id = $_POST['aluguel_id'];
    $sql_update = "UPDATE alugueis SET status = 1 WHERE id = ? AND imovel = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("ii", $aluguel_id, $property_id);
    $stmt_update->execute();
    header("Location: pagamentos.php?property_id=$property_id&nome=$property_name&mes=$mes&ano=$ano");
    exit();
}

// Busca os alugueis atrasados
$sql_atrasados = "SELECT alugueis.*, clientes.nome as cliente_nome 
                  FROM alugueis 
                  JOIN clientes ON alugueis.cliente = clientes.id 
                  WHERE alugueis.imovel = ? AND alugueis.data_fim < NOW() AND alugueis.status = 0 
                  ORDER BY alugueis.data_fim ASC";
$stmt_atrasados = $conn->prepare($sql_atrasados);
$stmt_atrasados->bind_param("i", $property_id);
$stmt_atrasados->execute();
$atrasados = $stmt_atrasados->get_result();

// Busca os alugueis pendentes
$sql_pendentes = "SELECT alugueis.*, clientes.nome as cliente_nome 
                  FROM alugueis 
                  JOIN clientes ON alugueis.cliente = clientes.id 
                  WHERE alugueis.imovel = ? AND alugueis.data_fim >= NOW() AND alugueis.status = 0 
                  ORDER BY alugueis.data_fim ASC";
$stmt_pendentes = $conn->prepare($sql_pendentes);
$stmt_pendentes->bind_param("i", $property_id);
$stmt_pendentes->execute();
$pendentes = $stmt_pendentes->get_result();

// Busca os alugueis concluidos
$sql_concluidos = "SELECT alugueis.*, clientes.nome as cliente_nome 
                   FROM alugueis 
                   JOIN clientes ON alugueis.cliente = clientes.id 
                   WHERE alugueis.imovel = ? AND alugueis.status = 1 AND MONTH(alugueis.data_fim) = ? AND YEAR(alugueis.data_fim) = ?
                   ORDER BY alugueis.data_fim DESC";
$stmt_concluidos = $conn->prepare($sql_concluidos);
$stmt_concluidos->bind_param("iii", $property_id, $mes, $ano);
$stmt_concluidos->execute();
$concluidos = $stmt_concluidos->get_result();

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
            <h1>Pagamentos de <?php echo htmlspecialchars($property_name); ?></h1>
        </div>
        <form class="filter-form" method="GET" action="pagamentos.php">
            <input type="hidden" name="property_id" value="<?php echo $property_id; ?>">
            <input type="hidden" name="nome" value="<?php echo htmlspecialchars($property_name); ?>">
            <label for="mes">Mês:</label>
            <select name="mes" id="mes">
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    $selected = ($i == $mes) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>" . date("F", mktime(0, 0, 0, $i, 10)) . "</option>";
                }
                ?>
            </select>
            <label for="ano">Ano:</label>
            <select name="ano" id="ano">
                <?php
                $currentYear = date('Y');
                for ($i = $currentYear; $i >= $currentYear - 10; $i--) {
                    $selected = ($i == $ano) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>$i</option>";
                }
                ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>
        <div class="payment-lists">
            <div class="payment-section">
                <h2>Atrasados</h2>
                <?php
                if ($atrasados->num_rows > 0) {
                    while ($aluguel = $atrasados->fetch_assoc()) {
                        echo '<div class="payment-item">';
                        echo '<h3>' . htmlspecialchars($aluguel['cliente_nome']) . '</h3>';
                        echo '<p>Início: ' . htmlspecialchars(formatDate($aluguel['data_inicio'])) . '</p>';
                        echo '<p>Fim: ' . htmlspecialchars(formatDate($aluguel['data_fim'])) . '</p>';
                        echo '<p>Status: <span class="status-pendente">Pendente</span></p>';
                        echo '<p>Valor Total: R$ ' . htmlspecialchars(number_format($aluguel['valor_total'], 2, ',', '.')) . '</p>';
                        echo '<form action="pagamentos.php?property_id=' . htmlspecialchars($property_id) . '&mes=' . htmlspecialchars($mes) . '&ano=' . htmlspecialchars($ano) . '" method="post" class="update-status-form">';
                        echo '<input type="hidden" name="aluguel_id" value="' . htmlspecialchars($aluguel['id']) . '">';
                        echo '<button type="submit" name="update_status" class="status-button">Marcar como Pago</button>';
                        echo '</form>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Não há pagamentos atrasados.</p>';
                }
                ?>
            </div>
            <div class="payment-section">
                <h2>Pendentes</h2>
                <?php
                if ($pendentes->num_rows > 0) {
                    while ($aluguel = $pendentes->fetch_assoc()) {
                        echo '<div class="payment-item">';
                        echo '<h3>' . htmlspecialchars($aluguel['cliente_nome']) . '</h3>';
                        echo '<p>Início: ' . htmlspecialchars(formatDate($aluguel['data_inicio'])) . '</p>';
                        echo '<p>Fim: ' . htmlspecialchars(formatDate($aluguel['data_fim'])) . '</p>';
                        echo '<p>Status: <span class="status-pendente">Pendente</span></p>';
                        echo '<p>Valor Total: R$ ' . htmlspecialchars(number_format($aluguel['valor_total'], 2, ',', '.')) . '</p>';
                        echo '<form action="pagamentos.php?property_id=' . htmlspecialchars($property_id) . '&mes=' . htmlspecialchars($mes) . '&ano=' . htmlspecialchars($ano) . '" method="post" class="update-status-form">';
                        echo '<input type="hidden" name="aluguel_id" value="' . htmlspecialchars($aluguel['id']) . '">';
                        echo '<button type="submit" name="update_status" class="status-button">Marcar como Pago</button>';
                        echo '</form>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Não há pagamentos pendentes.</p>';
                }
                ?>
            </div>
            <div class="payment-section">
                <h2>Concluídos</h2>
                <?php
                if ($concluidos->num_rows > 0) {
                    while ($aluguel = $concluidos->fetch_assoc()) {
                        echo '<div class="payment-item">';
                        echo '<h3>' . htmlspecialchars($aluguel['cliente_nome']) . '</h3>';
                        echo '<p>Início: ' . htmlspecialchars(formatDate($aluguel['data_inicio'])) . '</p>';
                        echo '<p>Fim: ' . htmlspecialchars(formatDate($aluguel['data_fim'])) . '</p>';
                        echo '<p>Status: <span class="status-pago">Pago</span></p>';
                        echo '<p>Valor Total: R$ ' . htmlspecialchars(number_format($aluguel['valor_total'], 2, ',', '.')) . '</p>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Não há pagamentos concluídos.</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <script>
        function deslogar(){
            window.location.href="index.php"
        }

        function voltar(){
            window.history.back(); // Volta para a página anterior
        }
    </script>
</body>
</html>
