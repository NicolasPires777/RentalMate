<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aluguéis</title>
    <link rel="stylesheet" href="css/aluguel.css">
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

// Recupera o mês e ano do filtro
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

// Função para formatar datas
function formatDate($date) {
    return date("d/m/Y", strtotime($date));
}

// Atualiza o status do aluguel
if (isset($_POST['aluguel_id']) && isset($_POST['update_status'])) {
    $aluguel_id = $_POST['aluguel_id'];
    $sql_update = "UPDATE alugueis SET status = 1 WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("i", $aluguel_id);
    $stmt_update->execute();
}

// Adiciona um novo aluguel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['novo_aluguel'])) {
    $cliente_id = $_POST['cliente'];
    $data_inicio = $_POST['data_inicio'];
    $data_fim = $_POST['data_fim'];
    $valor_dia = $_POST['valor_dia'];
    $status = $_POST['status'];

    // Convertendo as datas para o formato correto
    $data_inicio = date('Y-m-d', strtotime($data_inicio));
    $data_fim = date('Y-m-d', strtotime($data_fim));

    // Verificação de data já alugada
    $sql_verifica_aluguel = "SELECT * FROM alugueis WHERE imovel = ? AND ((data_inicio BETWEEN ? AND ?) OR (data_fim BETWEEN ? AND ?) OR (? BETWEEN data_inicio AND data_fim) OR (? BETWEEN data_inicio AND data_fim))";
    $stmt_verifica_aluguel = $conn->prepare($sql_verifica_aluguel);
    $stmt_verifica_aluguel->bind_param("issssss", $property_id, $data_inicio, $data_fim, $data_inicio, $data_fim, $data_inicio, $data_fim);
    $stmt_verifica_aluguel->execute();
    $result_verifica_aluguel = $stmt_verifica_aluguel->get_result();
    
    if ($data_inicio>$data_fim){
        $erro_data="Erro: A data Final não pode ser menor que a data Inicial";
    }  elseif ($result_verifica_aluguel->num_rows > 0) {
        $erro_aluguel="Erro: A data selecionada já está alugada.";
    } else {
        // Verificação de pagamento atrasado
        $sql_verifica_pagamento = "SELECT * FROM alugueis WHERE cliente = ? AND status = 0 AND data_inicio<NOW()";
        $stmt_verifica_pagamento = $conn->prepare($sql_verifica_pagamento);
        $stmt_verifica_pagamento->bind_param("i", $cliente_id);
        $stmt_verifica_pagamento->execute();
        $result_verifica_pagamento = $stmt_verifica_pagamento->get_result();
        
        if ($result_verifica_pagamento->num_rows > 0) {
            $erro_pagamento="Erro: O cliente selecionado possui pagamentos atrasados.";
        } else {
            $data_inicio_date = new DateTime($data_inicio);
            $data_fim_date = new DateTime($data_fim);
            $interval = $data_inicio_date->diff($data_fim_date);
            $dias = $interval->days; // Inclui o dia de início no cálculo
            $valor_total = $dias * $valor_dia;

            $sql_novo_aluguel = "INSERT INTO alugueis (imovel, cliente, data_inicio, data_fim, valor_dia, valor_total, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt_novo_aluguel = $conn->prepare($sql_novo_aluguel);
            $stmt_novo_aluguel->bind_param("iisssdi", $property_id, $cliente_id, $data_inicio, $data_fim, $valor_dia, $valor_total, $status);
            if ($stmt_novo_aluguel->execute()) {
                // Calcular o dia seguinte ao término do aluguel
                $data_faxina = date('Y-m-d', strtotime($data_fim . ' +1 day'));

                // Inserir nova faxina
                $sql_nova_faxina = "INSERT INTO faxina (data, agendamento, status, contratado, contato, imovel) VALUES (?, 0, 0, NULL, NULL, ?)";
                $stmt_nova_faxina = $conn->prepare($sql_nova_faxina);
                $stmt_nova_faxina->bind_param("si", $data_faxina, $property_id);
                $stmt_nova_faxina->execute();
            } else {
                echo "Erro ao adicionar o aluguel: " . $stmt_novo_aluguel->error;
            }
        }
    }
    header("Location: aluguel.php?property_id=$property_id&nome=" . urlencode($property_name));
}


// Busca os clientes do usuário logado
$sql_clientes = "SELECT id, nome FROM clientes WHERE dono = ?";
$stmt_clientes = $conn->prepare($sql_clientes);
$stmt_clientes->bind_param("i", $property_id);
$stmt_clientes->execute();
$clientes = $stmt_clientes->get_result();

// Busca os aluguéis futuros e anteriores do imóvel selecionado com filtro de mês e ano
$sql_futuros = "SELECT alugueis.*, clientes.nome as cliente_nome 
                FROM alugueis 
                JOIN clientes ON alugueis.cliente = clientes.id 
                WHERE alugueis.imovel = ? AND alugueis.data_fim > NOW() 
                AND MONTH(alugueis.data_inicio) = ? AND YEAR(alugueis.data_inicio) = ?
                ORDER BY alugueis.data_fim ASC";
$stmt_futuros = $conn->prepare($sql_futuros);
$stmt_futuros->bind_param("iii", $property_id, $mes, $ano);
$stmt_futuros->execute();
$futuros_alugueis = $stmt_futuros->get_result();

$sql_anteriores = "SELECT alugueis.*, clientes.nome as cliente_nome 
                   FROM alugueis 
                   JOIN clientes ON alugueis.cliente = clientes.id 
                   WHERE alugueis.imovel = ? AND alugueis.data_fim < NOW() 
                   AND MONTH(alugueis.data_inicio) = ? AND YEAR(alugueis.data_inicio) = ?
                   ORDER BY alugueis.data_fim DESC";
$stmt_anteriores = $conn->prepare($sql_anteriores);
$stmt_anteriores->bind_param("iii", $property_id, $mes, $ano);
$stmt_anteriores->execute();
$anteriores_alugueis = $stmt_anteriores->get_result();
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
            <div class="button-container-back">
                <button class="back-button" onclick="window.location.href='property.php?id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>'">Voltar</button>
            </div>
            <h1>Aluguéis de <?php echo htmlspecialchars($property_name); ?></h1>
            <div class="button-container-new">
                <button class="new-rental-button" onclick="openModal()"> Novo </button>
            </div>
        </div>
        <div class="erro">
        <?php 
                    if (!empty($erro_data)){
                        echo '<p style="color: red;">' . $erro_data . '</p>';
                    } elseif (!empty($erro_aluguel)){
                        echo '<p style="color: red;">' . $erro_aluguel . '</p>';
                    } elseif (!empty($erro_pagamento)){
                        echo '<p style="color: red;">' . $erro_pagamento . '</p>';
                    }
                ?>
        </div>
        <form class="filter-form" method="GET" action="aluguel.php">
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
        <div class="rental-lists">
            <div class="past-rentals">
                <h2>Aluguéis Anteriores</h2>
                <?php
                if ($anteriores_alugueis->num_rows > 0) {
                    while ($aluguel = $anteriores_alugueis->fetch_assoc()) {
                        $status = $aluguel['status'] == 0 ? '<span class="status-pendente">Pendente</span>' : '<span class="status-pago">Pago</span>';
                        echo '<div class="rental-item">';
                        echo '<h3>' . htmlspecialchars($aluguel['cliente_nome']) . '</h3>';
                        echo '<p>Início: ' . htmlspecialchars(formatDate($aluguel['data_inicio'])) . '</p>';
                        echo '<p>Fim: ' . htmlspecialchars(formatDate($aluguel['data_fim'])) . '</p>';
                        echo '<p>Status: ' . $status . '</p>';
                        echo '<p>Valor: R$ ' . htmlspecialchars(number_format($aluguel['valor_total'], 2, ',', '.')) . '</p>';
                        if ($aluguel['status'] == 0) {
                            echo '<form method="post" class="update-status-form">';
                            echo '<input type="hidden" name="aluguel_id" value="' . htmlspecialchars($aluguel['id']) . '">';
                            echo '<button type="submit" name="update_status" class="status-button">Marcar como Pago</button>';
                            echo '</form>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<p>Não há aluguéis anteriores.</p>';
                }
                ?>
            </div>
            <div class="future-rentals">
                <h2>Futuros Aluguéis</h2>
                <?php
                if ($futuros_alugueis->num_rows > 0) {
                    while ($aluguel = $futuros_alugueis->fetch_assoc()) {
                        $status = $aluguel['status'] == 0 ? '<span class="status-pendente">Pendente</span>' : '<span class="status-pago">Pago</span>';
                        echo '<div class="rental-item">';
                        echo '<h3>' . htmlspecialchars($aluguel['cliente_nome']) . '</h3>';
                        echo '<p>Início: ' . htmlspecialchars(formatDate($aluguel['data_inicio'])) . '</p>';
                        echo '<p>Fim: ' . htmlspecialchars(formatDate($aluguel['data_fim'])) . '</p>';
                        echo '<p>Status: ' . $status . '</p>';
                        echo '<p>Valor: R$ ' . htmlspecialchars(number_format($aluguel['valor_total'], 2, ',', '.')) . '</p>';
                        if ($aluguel['status'] == 0) {
                            echo '<form method="post" class="update-status-form">';
                            echo '<input type="hidden" name="aluguel_id" value="' . htmlspecialchars($aluguel['id']) . '">';
                            echo '<button type="submit" name="update_status" class="status-button">Marcar como Pago</button>';
                            echo '</form>';
                        }
                        echo '</div>';
                    }
                } else {
                    echo '<p>Não há aluguéis futuros.</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar novo aluguel -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeModal()">&times;</span>
            <h2>Novo Aluguel</h2>
            <form action="aluguel.php?property_id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>" method="post">
                <label for="cliente">Cliente:</label>
                <select id="cliente" name="cliente" required>
                    <?php
                    if ($clientes->num_rows > 0) {
                        while ($cliente = $clientes->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($cliente['id']) . '">' . htmlspecialchars($cliente['nome']) . '</option>';
                        }
                    } else {
                        echo '<option value="">Não há clientes cadastrados.</option>';
                    }
                    ?>
                </select>
                <label for="data_inicio">Data Inicial:</label>
                <input type="date" id="data_inicio" name="data_inicio" required>
                <label for="data_fim">Data Final:</label>
                <input type="date" id="data_fim" name="data_fim" required>
                <label for="valor_dia">Valor Diária (R$):</label>
                <input type="number" id="valor_dia" name="valor_dia" step="0.01" required>
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="0">Pendente</option>
                    <option value="1">Pago</option>
                </select>
                <button type="submit" name="novo_aluguel">Adicionar</button>
            </form>
        </div>
    </div>

    <script>
        function deslogar(){
            window.location.href="index.html"
        }

        function openModal() {
            document.getElementById('modal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('modal')) {
                closeModal();
            }
        }
    </script>
</body>
</html>
