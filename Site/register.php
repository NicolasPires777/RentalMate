<?php
include 'PHP/connect.php';

// Verifica se o email já está registrado
$stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Verifica se o formulário foi enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'];
    $telefone = $_POST['telefone'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Verifica se o email já está registrado
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Email já registrado
        $email_error = "O email já está registrado. Por favor, use outro email.";
    } else {
        $options = [
            'cost' => 04, //  (quanto maior, mais seguro, mas também mais lento)
        ];
        // Criptografa a senha
        $senha_hash = password_hash($senha, PASSWORD_BCRYPT, $options);

        // Prepara a consulta SQL para evitar SQL injection
        $stmt = $conn->prepare("INSERT INTO usuarios (nome, telefone, email, senha) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $nome, $telefone, $email, $senha_hash);

        // Executa a consulta
        if ($stmt->execute()) {
            $sucess = "Sua conta foi criada, acesse clicando aqui";
        } else {
            echo "Erro ao registrar o usuário.";
        }
    }
        // Fecha a consulta
        $stmt->close();

        // Fecha a conexão
        $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - RentalMate</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="register-container">
        <div class="site-name">RentalMate</h1>
        <p>Crie sua conta</p>
        <form method="POST">
            <div class="input-group">
                <i class="fa fa-user"></i>
                <input type="text" placeholder="Nome" name="nome" required>
            </div>
            <div class="input-group">
                <i class="fa fa-phone"></i>
                <input type="text" placeholder="Telefone" name="telefone" minlength="10" maxlength="11" required>
            </div>
            <div class="input-group">
                <i class="fa fa-envelope"></i>
                <input type="email" placeholder="Email" name="email" required>
            </div>
            <div class="input-group">
                <i class="fa fa-lock"></i>
                <input type="password" placeholder="Senha" name="senha" required>
            </div>
            <button type="submit">Registrar</button>
            <?php
            if (!empty($email_error)) {
                echo '<p style="color: red;">' . $email_error . '</p>';
            }
            if (!empty($sucess)) {
                echo '<p style="color: green;"><a href="index.php">' . $sucess . '</a></p>';
            }
            ?>
            <a href="index.php">Já tenho conta</a>
        </form>
    </div>
    <script src="https://kit.fontawesome.com/a076d05399.js"></script>
</body>
</html>
