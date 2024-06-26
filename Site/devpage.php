<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página em Desenvolvimento</title>
    <link rel="stylesheet" href="css/menu.css">  <!-- Reutilizando o mesmo CSS -->
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        .container {
            width: 100%;
        }

        .back-button {
            display: block;
            margin: 0 auto;
            padding: 10px 20px;
            background-color: #4682B4;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .back-button:hover {
            background-color: #5a9bd4;
        }
    </style>
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
            <h1>Página em desenvolvimento</h1>
        </div>
        <div class="content">
            <button class="back-button" onclick="history.back()">Voltar</button>
        </div>
    </div>
    <script>
    function deslogar(){
        window.location.href="index.html"
    }
    </script>
</body>
</html>
