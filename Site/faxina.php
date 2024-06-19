<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faxinas</title>
    <link rel="stylesheet" href="css/faxina.css">
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

// Verifica se o ID do imóvel é válido
if (!$property_id) {
    die("Imóvel não especificado.");
}

// Recupera o mês e ano do filtro
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('m');
$ano = isset($_GET['ano']) ? $_GET['ano'] : date('Y');

// Processa a exclusão de faxina
if (isset($_GET['excluir_id'])) {
    $excluir_id = $_GET['excluir_id'];
    $sql_excluir = "DELETE FROM faxina WHERE id = ?";
    $stmt_excluir = $conn->prepare($sql_excluir);
    $stmt_excluir->bind_param("i", $excluir_id);
    $stmt_excluir->execute();
    header("Location: faxina.php?property_id=$property_id&nome=" . urlencode($property_name));
    exit();
}

// Processa o agendamento de faxina
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['agendar_id'])) {
    $agendar_id = $_POST['agendar_id'];
    $contratado = $_POST['contratado'];
    $contato = $_POST['contato'];
    $valor = $_POST['valor'];
    $status = $_POST['status'];

    $sql_agendar = "UPDATE faxina SET agendamento = 1, contratado = ?, contato = ?, valor = ?, status = ? WHERE id = ?";
    $stmt_agendar = $conn->prepare($sql_agendar);
    $stmt_agendar->bind_param("ssisi", $contratado, $contato, $valor, $status, $agendar_id);
    $stmt_agendar->execute();
    header("Location: faxina.php?property_id=$property_id&nome=" . urlencode($property_name));
    exit();
}

// Processa a adição de faxina
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['contratado'])) {
    $contratado = $_POST['contratado'];
    $contato = $_POST['contato'];
    $data = $_POST['data'];
    $status = $_POST['status'];
    $valor = $_POST['valor'];

    $sql_adicionar = "INSERT INTO faxina (data, agendamento, status, contratado, contato, imovel, valor) VALUES (?, 1, ?, ?, ?, ?, ?)";
    $stmt_adicionar = $conn->prepare($sql_adicionar);
    $stmt_adicionar->bind_param("sissii", $data, $status, $contratado, $contato, $property_id, $valor);
    if ($stmt_adicionar->execute()) {
        header("Location: faxina.php?property_id=$property_id&nome=" . urlencode($property_name));
        exit();
    } else {
        echo "Erro ao adicionar a faxina: " . $stmt_adicionar->error;
    }
}

// Processa a atualização do status para pago
if (isset($_GET['pagar_id'])) {
    $pagar_id = $_GET['pagar_id'];
    $sql_pagar = "UPDATE faxina SET status = 1 WHERE id = ?";
    $stmt_pagar = $conn->prepare($sql_pagar);
    $stmt_pagar->bind_param("i", $pagar_id);
    $stmt_pagar->execute();
    header("Location: faxina.php?property_id=$property_id&nome=" . urlencode($property_name));
    exit();
}

function formatDateMinusOneDay($date) {
    $date_minus_one_day = date("Y-m-d", strtotime($date . ' -1 day'));
    return date("d/m/Y", strtotime($date_minus_one_day));
}

// Busca as faxinas agendadas e pendentes do imóvel selecionado
$sql_agendadas = "SELECT * FROM faxina WHERE imovel = ? AND agendamento = 1 AND MONTH(data) = ? AND YEAR(data) = ? ORDER BY data DESC";
$stmt_agendadas = $conn->prepare($sql_agendadas);
$stmt_agendadas->bind_param("iii", $property_id, $mes, $ano);
$stmt_agendadas->execute();
$faxinas_agendadas = $stmt_agendadas->get_result();

$sql_pendentes = "SELECT * FROM faxina WHERE imovel = ? AND agendamento = 0 AND MONTH(data) = ? AND YEAR(data) = ? ORDER BY data DESC";
$stmt_pendentes = $conn->prepare($sql_pendentes);
$stmt_pendentes->bind_param("iii", $property_id, $mes, $ano);
$stmt_pendentes->execute();
$faxinas_pendentes = $stmt_pendentes->get_result();

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
            <h1>Faxinas de <?php echo htmlspecialchars($property_name); ?></h1>
            <button class="add-button" onclick="openAddModal()">Adicionar</button>
        </div>
        <form class="filter-form" method="GET" action="faxina.php">
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
        <div class="cleaning-lists">
            <div class="scheduled-cleanings">
                <h2>Agendadas</h2>
                <?php
                if ($faxinas_agendadas->num_rows > 0) {
                    while ($faxina = $faxinas_agendadas->fetch_assoc()) {
                        echo '<div class="cleaning-item">';
                        echo '<div class="cleaning-top-bar"></div>';
                        echo '<h3>' . htmlspecialchars($faxina['contratado']) . '</h3>';
                        echo '<p>Data: ' . htmlspecialchars(formatDate($faxina['data'])) . '</p>';
                        echo '<p>Contato: ' . htmlspecialchars($faxina['contato']) . '</p>';
                        echo '<p>Valor: R$ ' . htmlspecialchars(number_format($faxina['valor'], 2, ',', '.')) . '</p>';
                        if ($faxina['status'] == 0) {
                            echo '<p>Status: <span style="color: red;">Pendente</span></p>';
                            echo '<button class="pay-button" onclick="pagarFaxina(' . htmlspecialchars($faxina['id']) . ')">Pago</button>';
                        } else {
                            echo '<p>Status: <span style="color: green;">Pago</span></p>';
                        }
                        echo '<button class="delete-button" onclick="excluirFaxina(' . htmlspecialchars($faxina['id']) . ')">Excluir</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Não há faxinas agendadas.</p>';
                }
                ?>
            </div>
            <div class="pending-cleanings">
                <h2>Pendentes</h2>
                <?php
                if ($faxinas_pendentes->num_rows > 0) {
                    while ($faxina = $faxinas_pendentes->fetch_assoc()) {
                        echo '<div class="cleaning-item">';
                        echo '<div class="cleaning-top-bar"></div>';
                        echo '<h3>' . htmlspecialchars($faxina['contratado']) . '</h3>';
                        echo '<p>Data: ' . htmlspecialchars(formatDate($faxina['data'])) . '</p>';
                        echo '<p>Após aluguel: '.htmlspecialchars(formatDateMinusOneDay($faxina['data'])).'</p>';
                        echo '<button class="schedule-button" onclick="openScheduleModal(' . htmlspecialchars($faxina['id']) . ')">Agendar</button>';
                        echo '<button class="delete-button" onclick="excluirFaxina(' . htmlspecialchars($faxina['id']) . ')">Excluir</button>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>Não há faxinas pendentes.</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <!-- Modal para adicionar faxina -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeAddModal()">&times;</span>
            <h2>Adicionar Faxina</h2>
            <form action="faxina.php?property_id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>" method="post">
                <label for="contratado">Contratado:</label>
                <input type="text" id="contratado" name="contratado" required>
                <label for="contato">Contato:</label>
                <input type="text" id="contato" name="contato" required>
                <label for="data">Data:</label>
                <input type="date" id="data" name="data" required>
                <label for="valor">Valor:</label>
                <input type="number" id="valor" name="valor" required>
                <label for="status">Pagamento:</label>
                <select id="status" name="status" required>
                    <option value="0">Pendente</option>
                    <option value="1">Pago</option>
                </select>
                <button type="submit">Adicionar</button>
            </form>
        </div>
    </div>

    <!-- Modal para agendar faxina -->
    <div id="scheduleModal" class="modal">
        <div class="modal-content">
            <span class="close-button" onclick="closeScheduleModal()">&times;</span>
            <h2>Agendar Faxina</h2>
            <form id="scheduleForm" action="faxina.php?property_id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>" method="post">
                <input type="hidden" name="agendar_id" id="agendar_id">
                <label for="contratado">Contratado:</label>
                <input type="text" id="schedule_contratado" name="contratado" required>
                <label for="contato">Contato:</label>
                <input type="text" id="schedule_contato" name="contato" required>
                <label for="valor">Valor:</label>
                <input type="text" id="schedule_valor" name="valor" required>
                <label for="status">Pagamento:</label>
                <select id="schedule_status" name="status" required>
                    <option value="0">Pendente</option>
                    <option value="1">Pago</option>
                </select>
                <button type="submit">Agendar</button>
            </form>
        </div>
    </div>

    <script>
        function deslogar(){
            window.location.href="index.php"
        }

        function openAddModal() {
            document.getElementById('addModal').style.display = 'block';
        }

        function closeAddModal() {
            document.getElementById('addModal').style.display = 'none';
        }

        function openScheduleModal(id) {
            document.getElementById('scheduleForm').reset();
            document.getElementById('agendar_id').value = id;
            document.getElementById('scheduleModal').style.display = 'block';
        }

        function closeScheduleModal() {
            document.getElementById('scheduleModal').style.display = 'none';
        }

        function excluirFaxina(id) {
            if (confirm("Tem certeza que deseja excluir esta faxina?")) {
                window.location.href = "faxina.php?excluir_id=" + id + "&property_id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>";
            }
        }

        function pagarFaxina(id) {
            if (confirm("Tem certeza que deseja marcar esta faxina como paga?")) {
                window.location.href = "faxina.php?pagar_id=" + id + "&property_id=<?php echo $property_id; ?>&nome=<?php echo urlencode($property_name); ?>";
            }
        }

        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal')) {
                closeAddModal();
            }
            if (event.target == document.getElementById('scheduleModal')) {
                closeScheduleModal();
            }
        }
    </script>
</body>
</html>
