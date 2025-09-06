<?php
/**
 * Download de arquivos de backup
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Usuario.php';

session_start();

// Verificar se usuário está logado e é admin
if (!isset($_SESSION['usuario_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso negado');
}

$usuario = new Usuario();
$usuarioAtual = $usuario->buscarPorId($_SESSION['usuario_id']);

if ($usuarioAtual['perfil'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    exit('Acesso negado');
}

// Verificar se arquivo foi especificado
if (!isset($_GET['arquivo']) || empty($_GET['arquivo'])) {
    header('HTTP/1.1 400 Bad Request');
    exit('Arquivo não especificado');
}

$arquivo = $_GET['arquivo'];

// Validar nome do arquivo (apenas nomes seguros)
if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}\.sql$/', $arquivo)) {
    header('HTTP/1.1 400 Bad Request');
    exit('Nome de arquivo inválido');
}

$caminhoArquivo = __DIR__ . '/../../backups/' . $arquivo;

// Verificar se arquivo existe
if (!file_exists($caminhoArquivo)) {
    header('HTTP/1.1 404 Not Found');
    exit('Arquivo não encontrado');
}

// Configurar headers para download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Content-Length: ' . filesize($caminhoArquivo));
header('Cache-Control: must-revalidate');
header('Pragma: public');

// Enviar arquivo
readfile($caminhoArquivo);
exit();
?>
