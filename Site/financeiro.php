<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financeiro</title>
    <link rel="stylesheet" href="css/financeiro.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

// Função para formatar datas
function formatDate($date) {
    return date("d/m/Y", strtotime($date));
}

// Função para obter dados financeiros de um determinado mês e ano
function obterDadosFinanceiros($conn, $property_id, $mes, $ano) {
    $sql_aluguel = "SELECT SUM(valor_total) as total_aluguel, COUNT(id) as num_alugueis FROM alugueis WHERE imovel = ? AND status=1 AND MONTH(data_fim) = ? AND YEAR(data_fim) = ?";
    $sql_despesas = "SELECT SUM(valor) as total_despesas FROM despesas WHERE imovel = ? AND MONTH(date) = ? AND YEAR(date) = ?";
    $sql_faxina = "SELECT SUM(valor) as total_faxina FROM faxina WHERE imovel = ? AND MONTH(data) = ? AND YEAR(data) = ?";

    $stmt_aluguel = $conn->prepare($sql_aluguel);
    $stmt_aluguel->bind_param("iii", $property_id, $mes, $ano);
    $stmt_aluguel->execute();
    $result_aluguel = $stmt_aluguel->get_result();
    $dados_aluguel = $result_aluguel->fetch_assoc();
    $total_aluguel = $dados_aluguel['total_aluguel'] ?? 0;
    $num_alugueis = $dados_aluguel['num_alugueis'] ?? 0;

    $stmt_despesas = $conn->prepare($sql_despesas);
    $stmt_despesas->bind_param("iii", $property_id, $mes, $ano);
    $stmt_despesas->execute();
    $result_despesas = $stmt_despesas->get_result();
    $total_despesas = $result_despesas->fetch_assoc()['total_despesas'] ?? 0;

    $stmt_faxina = $conn->prepare($sql_faxina);
    $stmt_faxina->bind_param("iii", $property_id, $mes, $ano);
    $stmt_faxina->execute();
    $result_faxina = $stmt_faxina->get_result();
    $total_faxina = $result_faxina->fetch_assoc()['total_faxina'] ?? 0;

    $total_despesas_com_faxina = $total_despesas + $total_faxina;
    $receita_liquida = $total_aluguel - $total_despesas_com_faxina;

    return [
        'total_aluguel' => $total_aluguel,
        'num_alugueis' => $num_alugueis,
        'total_despesas_com_faxina' => $total_despesas_com_faxina,
        'receita_liquida' => $receita_liquida
    ];
}

// Obter dados financeiros dos últimos 5 meses
$dados_financeiros = [];
for ($i = 4; $i >= 0; $i--) {
    $mes_atual = date('m', strtotime("-$i month", strtotime("$ano-$mes-01")));
    $ano_atual = date('Y', strtotime("-$i month", strtotime("$ano-$mes-01")));
    $dados_financeiros[] = obterDadosFinanceiros($conn, $property_id, $mes_atual, $ano_atual);
}

// Calcular comparações com o mês anterior
$comparacoes = [];
for ($i = 1; $i < count($dados_financeiros); $i++) {
    $dados_atual = $dados_financeiros[$i];
    $dados_anterior = $dados_financeiros[$i - 1];
    
    $variacao_num_alugueis = $dados_atual['num_alugueis'] - $dados_anterior['num_alugueis'];
    $variacao_despesas = $dados_atual['total_despesas_com_faxina'] - $dados_anterior['total_despesas_com_faxina'];
    $variacao_receita_liquida = $dados_atual['receita_liquida'] - $dados_anterior['receita_liquida'];

    $comparacoes[] = [
        'variacao_num_alugueis' => $variacao_num_alugueis,
        'variacao_despesas' => $variacao_despesas,
        'variacao_receita_liquida' => $variacao_receita_liquida
    ];
}

// Dados do mês atual
$dados_atual = end($dados_financeiros);
$comparacao_atual = end($comparacoes);

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
            <button class="back-button" onclick="window.location.href='property.php?id=<?php echo $property_id; ?>&nome=<?php echo htmlspecialchars($property_name); ?>'">Voltar</button>
            <h1>Financeiro de <?php echo htmlspecialchars($property_name); ?></h1>
        </div>
        <form class="filter-form" method="GET" action="financeiro.php">
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
        <div class="financial-cards">
            <div class="card">
                <h3>Faturamento Bruto</h3>
                <p>R$ <?php echo number_format($dados_atual['total_aluguel'], 2, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h3>Despesas</h3>
                <p>R$ <?php echo number_format($dados_atual['total_despesas_com_faxina'], 2, ',', '.'); ?></p>
            </div>
            <div class="card">
                <h3>Receita Líquida</h3>
                <p>R$ <?php echo number_format($dados_atual['receita_liquida'], 2, ',', '.'); ?></p>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="financeChart"></canvas>
        </div>
        <div class="comparison-summary">
            <div class="comparison-item">
                <h3>Quantidade de Aluguéis</h3>
                <p style="color: <?php echo $comparacao_atual['variacao_num_alugueis'] >= 0 ? 'green' : 'red'; ?>">
                    <?php echo $dados_atual['num_alugueis']; ?>
                </p>
            </div>
            <div class="comparison-item">
                <h3>Diferença de Gastos (mês anterior)</h3>
                <p style="color: <?php echo $comparacao_atual['variacao_despesas'] >= 0 ? 'red' : 'green'; ?>">
                    R$ <?php echo number_format($comparacao_atual['variacao_despesas'], 2, ',', '.'); ?>
                </p>
            </div>
            <div class="comparison-item">
                <h3>Diferença de Receita (mês anterior)</h3>
                <p style="color: <?php echo $comparacao_atual['variacao_receita_liquida'] >= 0 ? 'green' : 'red'; ?>">
                    R$ <?php echo number_format($comparacao_atual['variacao_receita_liquida'], 2, ',', '.'); ?>
                </p>
            </div>
        </div>
    </div>
    <script>
        function deslogar() {
            window.location.href = "index.php";
        }

        const ctx = document.getElementById('financeChart').getContext('2d');
        const financeChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: [
                    <?php
                    foreach ($dados_financeiros as $index => $dados) {
                        $mes_ano = date('F Y', strtotime("-" . (4 - $index) . " month", strtotime("$ano-$mes-01")));
                        echo "'$mes_ano', ";
                    }
                    ?>
                ],
                datasets: [{
                    label: 'Receita Líquida',
                    data: [
                        <?php
                        foreach ($dados_financeiros as $dados) {
                            echo $dados['receita_liquida'] . ', ';
                        }
                        ?>
                    ],
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
