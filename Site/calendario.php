<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendário</title>
    <link rel="stylesheet" href="css/calendario.css">
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

// Recupera o mês e ano do filtro
$property_id = isset($_GET['property_id']) ? intval($_GET['property_id']) : '';
$mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('m');
$ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

// Busca os dados de aluguel
$sql_aluguel = "SELECT data_inicio, data_fim FROM alugueis WHERE (MONTH(data_inicio) = ? OR MONTH(data_fim) = ?) AND (YEAR(data_inicio) = ? OR YEAR(data_fim) = ?)";
$stmt_aluguel = $conn->prepare($sql_aluguel);
$stmt_aluguel->bind_param("iiii", $mes, $mes, $ano, $ano);
$stmt_aluguel->execute();
$result_aluguel = $stmt_aluguel->get_result();

$aluguel_data = [];
while ($row = $result_aluguel->fetch_assoc()) {
    $start_date = new DateTime($row['data_inicio']);
    $end_date = new DateTime($row['data_fim']);
    $interval = new DateInterval('P1D');
    $period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));
    
    foreach ($period as $date) {
        $aluguel_data[$date->format('Y-m-d')] = 'busy';
    }
}

// Busca os dados de faxina
$sql_faxina = "SELECT data FROM faxina WHERE MONTH(data) = ? AND YEAR(data) = ?";
$stmt_faxina = $conn->prepare($sql_faxina);
$stmt_faxina->bind_param("ii", $mes, $ano);
$stmt_faxina->execute();
$result_faxina = $stmt_faxina->get_result();

$faxina_data = [];
while ($row = $result_faxina->fetch_assoc()) {
    $faxina_data[$row['data']] = 'cleaning';
}

// Função para gerar o calendário
function generateCalendar($mes, $ano, $aluguel_data, $faxina_data) {
    $daysOfWeek = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
    $firstDayOfMonth = date('w', strtotime("$ano-$mes-01"));
    $daysInMonth = date('t', strtotime("$ano-$mes-01"));
    
    echo '<div class="calendar">';
    foreach ($daysOfWeek as $day) {
        echo '<div class="day-header">' . $day . '</div>';
    }
    for ($i = 0; $i < $firstDayOfMonth; $i++) {
        echo '<div class="day"></div>';
    }
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $currentDate = "$ano-$mes-" . str_pad($day, 2, '0', STR_PAD_LEFT);
        $dayClass = '';
        if (isset($aluguel_data[$currentDate])) {
            $dayClass = 'busy';
        } elseif (isset($faxina_data[$currentDate])) {
            $dayClass = 'cleaning';
        }
        echo '<div class="day ' . $dayClass . '">';
        echo '<div class="day-number">' . $day . '</div>';
        echo '</div>';
    }
    echo '</div>';
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
            <button class="back-button" onclick="window.history.back();">Voltar</button>
            <h1>Calendário</h1>
        </div>
        <form class="filter-form" method="GET" action="calendario.php">
            <input type="hidden" name="property_id" value="<?php echo ($property_id); ?>">
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
                for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
                    $selected = ($i == $ano) ? 'selected' : '';
                    echo "<option value=\"$i\" $selected>$i</option>";
                }
                ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>
        <?php
        generateCalendar($mes, $ano, $aluguel_data, $faxina_data);
        ?>
    </div>
    <script src="js/main.js"></script>
</body>
</html>
