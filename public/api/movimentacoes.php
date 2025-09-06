<?php
/**
 * API para listar movimentações
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Movimentacao.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$limite = $_GET['limite'] ?? 10;

try {
    $movimentacao = new Movimentacao();
    $movimentacoes = $movimentacao->listar([
        'limite' => $limite
    ]);

    echo json_encode([
        'success' => true, 
        'movimentacoes' => $movimentacoes
    ]);
} catch (Exception $e) {
    error_log("Erro na API de movimentações: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro interno do servidor.'
    ]);
}
?>

