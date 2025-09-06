<?php
/**
 * API para buscar usuário por ID
 */

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/core/Database.php';
require_once __DIR__ . '/../../../app/core/Usuario.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? '';

if (empty($id)) {
    echo json_encode(['success' => false, 'message' => 'ID do usuário não fornecido.']);
    exit();
}

try {
    $usuario = new Usuario();
    $usuarioEncontrado = $usuario->buscarPorId($id);

    if ($usuarioEncontrado) {
        // Remover a senha do retorno por segurança
        unset($usuarioEncontrado['senha']);
        echo json_encode(['success' => true, 'usuario' => $usuarioEncontrado]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Usuário não encontrado.']);
    }
} catch (Exception $e) {
    error_log("Erro na API de busca de usuário por ID: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor.']);
}
?>

