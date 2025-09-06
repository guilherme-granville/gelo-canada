<?php
/**
 * Script de Sincronização Automática
 * Para ser executado via cron no Raspberry Pi
 * 
 * Exemplo de cron:
 * */5 * * * * /usr/bin/php /var/www/html/gelo-canada/scripts/sync_cron.php
 */

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Incluir configurações
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/SyncService.php';

// Log do início da execução
$logFile = LOG_PATH . 'sync_cron.log';
$timestamp = date('Y-m-d H:i:s');
$logMessage = "[{$timestamp}] Iniciando sincronização automática\n";

file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);

try {
    // Executar sincronização
    $resultado = SyncService::executarSincronizacaoAutomatica();
    
    // Log do resultado
    $logMessage = "[{$timestamp}] Sincronização concluída: " . json_encode($resultado) . "\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Se houver erro, enviar notificação (opcional)
    if ($resultado['status'] === 'error') {
        $errorMessage = "[{$timestamp}] ERRO na sincronização: " . $resultado['message'] . "\n";
        file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
        
        // Aqui você pode adicionar notificação por email ou outro método
        if (EMAIL_ENABLED) {
            // enviarEmailAlerta($resultado['message']);
        }
    }
    
    // Limpar logs antigos (manter apenas últimos 7 dias)
    limparLogsAntigos($logFile, 7);
    
} catch (Exception $e) {
    $errorMessage = "[{$timestamp}] EXCEÇÃO na sincronização: " . $e->getMessage() . "\n";
    file_put_contents($logFile, $errorMessage, FILE_APPEND | LOCK_EX);
}

/**
 * Limpa logs antigos
 */
function limparLogsAntigos($logFile, $dias) {
    if (file_exists($logFile)) {
        $linhas = file($logFile);
        $agora = time();
        $linhasFiltradas = [];
        
        foreach ($linhas as $linha) {
            if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $linha, $matches)) {
                $dataLog = strtotime($matches[1]);
                if (($agora - $dataLog) < ($dias * 24 * 60 * 60)) {
                    $linhasFiltradas[] = $linha;
                }
            }
        }
        
        file_put_contents($logFile, implode('', $linhasFiltradas));
    }
}

/**
 * Envia email de alerta (implementação opcional)
 */
function enviarEmailAlerta($mensagem) {
    if (!EMAIL_ENABLED) {
        return;
    }
    
    $assunto = 'Alerta - Erro na Sincronização do Sistema de Estoque';
    $corpo = "
    <h2>Alerta de Sincronização</h2>
    <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
    <p><strong>Erro:</strong> {$mensagem}</p>
    <p>Verifique o sistema de sincronização do Raspberry Pi.</p>
    ";
    
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=utf-8',
        'From: ' . EMAIL_FROM,
        'Reply-To: ' . EMAIL_FROM
    ];
    
    mail(EMAIL_FROM, $assunto, $corpo, implode("\r\n", $headers));
}

echo "Sincronização executada em: " . date('d/m/Y H:i:s') . "\n";
