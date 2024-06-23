<?php
include './conect.php';
include '../Site/PHP/session.php';
header('Content-Type: application/json');



try{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitação não permitido');
    }

    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    $email = $data['nome'];
    $senha = $data['senha'];

    $stmt = $conn->prepare("SELECT id, senha FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result=$stmt->get_result();

    if ($result->num_rows>0){
        $row = $result->fetch_assoc();
        if (password_verify($senha, $row['senha'])) {
            $response["id"] = $row['id']; 
            $_SESSION['user_id'] = $row['id'];
        } else {
            $response["errologin"] = "Email ou senha incorretos";
        }
    } else {
        $response["erronotfound"] = "O email não está registrado";
    }


    // Fecha a consulta
    $stmt->close();

    // Fecha a conexão
    $conn->close();
    
    echo json_encode($response);
} catch (Exception $e) {
    // Em caso de erro, retorna um JSON com a mensagem de erro
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>