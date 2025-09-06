<?php
/**
 * API para buscar produto por ID
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../app/core/Database.php';
require_once __DIR__ . '/../../../app/core/Usuario.php';
require_once __DIR__ . '/../../../app/core/Produto.php';

session_start();

try {
    // Verificar se usuário está logado
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception('Usuário não autenticado');
    }

    $usuario = new Usuario();
    $usuarioAtual = $usuario->buscarPorId($_SESSION['usuario_id']);

    // Verificar se é admin
    if ($usuarioAtual['perfil'] !== 'admin') {
        throw new Exception('Acesso negado');
    }

    // Verificar método
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }

    // Obter dados do POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id']) || empty($input['id'])) {
        throw new Exception('ID do produto é obrigatório');
    }

    $produto = new Produto();
    $dadosProduto = $produto->buscarPorId($input['id']);

    if (!$dadosProduto) {
        throw new Exception('Produto não encontrado');
    }

    echo json_encode([
        'success' => true,
        'produto' => $dadosProduto
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}