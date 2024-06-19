<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Página de Login - RentalMate</title>
    <link rel="stylesheet" href="css/index.css">
</head>
<body>
    <?php
    include 'PHP/connect.php';
    include 'PHP/session.php';

    $login_error = ""; // erro

    // Verifica se o formulário foi enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Coleta os valores digitados nos campos de email e senha
        $email = $_POST['email'];
        $senha = $_POST['senha'];

        // Prepara a consulta SQL para evitar SQL injection
        $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);

        // Executa a consulta
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            //se existe um usuário com o email informado
            $row = $result->fetch_assoc();
            if (password_verify($senha, $row['senha'])) {
                // Senha correta
                $_SESSION['user_id'] = $row['id']; // Armazena o ID do usuário na sessão
                header("Location: menu.php");
                exit();
            } else {
                // Senha incorreta
                $login_error = "Email/Senha Inválidos";
            }
        } else {
            // Não existe um usuário com o email informado
            $login_error = "Email/Senha Inválidos";
        }

        // Fecha a consulta
        $stmt->close();

        // Fecha a conexão
        $conn->close();
    }
    ?>

    <div class="login-container">
        <div class="site-name">Bem-vindo ao RentalMate</div> <!-- Nome do site aqui -->
        <p>Faça login para continuar</p>
        <form method="POST">
            <div class="input-group">
                <i class="fa fa-user"></i>
                <input type="email" placeholder="Email" id="email" name="email" required>
            </div>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" placeholder="Senha" id="senha" name="senha" required>
            </div>
            <?php
            if (!empty($login_error)) {
                echo '<p style="color: red;">' . $login_error . '</p>';
            }
            ?>
            <button type="submit">Entrar</button>
            <a href="#">Esqueceu a senha?</a>
            <br>
            <a href="register.php">Registrar-se</a>
        </form>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
