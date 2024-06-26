<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Propriedade</title>
    <link rel="stylesheet" href="css/menu.css">  <!-- Reutilizando o mesmo CSS -->
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

    // Recupera o ID e o nome do imóvel da URL
    $property_id = isset($_GET['id']) ? $_GET['id'] : '';
    $property_name = isset($_GET['nome']) ? $_GET['nome'] : '';
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
            <h1><?php echo htmlspecialchars($property_name); ?></h1>
        </div>
        <div class="options">
            <a href="aluguel.php?property_id=<?php echo htmlspecialchars($property_id); ?>&nome=<?php echo htmlspecialchars($property_name); ?>" class="option-item">
                <img src="icones/alugueis.png" alt="Aluguéis">
            </a>
            <a href="calendario.php?property_id=<?php echo htmlspecialchars($property_id); ?>&nome=<?php echo htmlspecialchars($property_name); ?>" class="option-item">
                <img src="icones/calendario.png" alt="Calendário">
            </a>
            <a href="clientes.php?property_id=<?php echo htmlspecialchars($property_id); ?>&nome=<?php echo htmlspecialchars($property_name); ?>" class="option-item">
                <img src="icones/clientes.png" alt="Clientes">
            </a>
            <a href="despesas.php?property_id=<?php echo htmlspecialchars($property_id); ?>&nome=<?php echo htmlspecialchars($property_name); ?>" class="option-item" class="option-item" class="option-item">
                <img src="icones/reformas.png" alt="Reformas">
            </a>
            <a href="faxina.php?property_id=<?php echo htmlspecialchars($property_id); ?>&nome=<?php echo htmlspecialchars($property_name); ?>" class="option-item" class="option-item">
                <img src="icones/limpeza.png" alt="Faxinas">
            </a>
            <a href="devpage.php" class="option-item">
                <img src="icones/Contratos.png" alt="Contratos">
            </a>
            <a href="financeiro.php?property_id=<?php echo htmlspecialchars($property_id); ?>&nome=<?php echo htmlspecialchars($property_name); ?>" class="option-item">
                <img src="icones/financeiro.png" alt="Financeiro">
            </a>
            <a href="pagamentos.php?property_id=<?php echo htmlspecialchars($property_id); ?>&nome=<?php echo htmlspecialchars($property_name); ?>" class="option-item">
                <img src="icones/Pagamentos.png" alt="Pagamentos">
            </a>
        </div>
        <div class="lalala">
            <button class="back-button" onclick="voltar()">Voltar</button>
        </div>
    </div>
    <script>
        function deslogar(){
            window.location.href="index.html"
        }
        function voltar() {
            window.location.href="menu.php";
        }
    </script>
</body>
</html>
