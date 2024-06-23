<?php
include './conect.php';
header('Content-Type: application/json');

$options_hash = [
    'cost' => 04, //  (quanto maior, mais seguro, mas também mais lento)
];

try{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método de solicitação não permitido');
    }

    $input = file_get_contents('php://input');
    // Transforma em objetos PHP
    $data = json_decode($input, true);

    $nome = $data['nome'];
    $senha = $data['senha'];
    $email = $data['email'];
    $telefone = $data['telefone'];
    $senha_hash = password_hash($senha, PASSWORD_BCRYPT, $options_hash);

    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows >0){
        $response['erro'] = 'Email já registrado';
    } else {
        $query = $conn->prepare("INSERT INTO usuarios (nome,telefone,email,senha) VALUES (?, ?, ?, ?)");
        $query->bind_param("ssss",$nome,$telefone,$email,$senha_hash);
        if ($query->execute()){
            $response['Concluido'] = 'Usuário foi registrado com sucesso';
        } else {
            $response['erro2'] = 'Erro ao registrar';
        }
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