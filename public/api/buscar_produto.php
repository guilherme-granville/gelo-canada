<?php
/**
 * API para buscar produto por código
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Produto.php';

try {
    // Verificar se é POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido');
    }
    
    // Obter dados do JSON
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['codigo']) || empty($input['codigo'])) {
        throw new Exception('Código não fornecido');
    }
    
    $codigo = trim($input['codigo']);
    
    // Buscar produto
    $produto = new Produto();
    $produtoEncontrado = $produto->buscarPorCodigo($codigo);
    
    if (!$produtoEncontrado) {
        echo json_encode([
            'success' => false,
            'message' => 'Produto não encontrado'
        ]);
        exit;
    }
    
    // Buscar estoque atual
    $db = Database::getInstance();
    $estoque = $db->fetchOne(
        "SELECT quantidade_atual FROM estoque WHERE produto_id = ?",
        [$produtoEncontrado['id']]
    );
    
    $produtoEncontrado['quantidade_atual'] = $estoque['quantidade_atual'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'produto' => $produtoEncontrado
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
