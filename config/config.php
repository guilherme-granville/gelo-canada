<?php
/**
 * Configurações do Sistema de Controle de Estoque
 * Fábrica de Gelo
 */

// Configurações de ambiente
define('ENVIRONMENT', 'development'); // development, production
define('DEBUG', true);
define('TIMEZONE', 'America/Sao_Paulo');

// Configurações de banco de dados
define('DB_TYPE', 'sqlite'); // mysql, sqlite
define('DB_HOST', 'localhost');
define('DB_NAME', 'gelo_canada');
define('DB_USER', 'gelo_user');
define('DB_PASS', 'senha_segura_123');
define('DB_CHARSET', 'utf8mb4');

// Configurações SQLite (para Raspberry Pi)
define('SQLITE_PATH', __DIR__ . '/../data/gelo_local.db');

// Configurações de API e sincronização
define('SYNC_TOKEN', 'gelo_sync_token_2024_secreto');
define('API_TIMEOUT', 30);
define('SYNC_BATCH_SIZE', 100);

// Configurações de sessão
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'GELO_SESSION');

// Configurações de upload
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_PATH', __DIR__ . '/../public/uploads/');
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Configurações de backup
define('BACKUP_PATH', __DIR__ . '/../backups/');
define('BACKUP_RETENTION_DAYS', 30);
define('BACKUP_AUTO', true);

// Configurações de logs
define('LOG_PATH', __DIR__ . '/../logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Configurações de interface
define('ITEMS_PER_PAGE', 20);
define('DATE_FORMAT', 'd/m/Y H:i:s');
define('CURRENCY_SYMBOL', 'R$');

// Configurações de segurança
define('PASSWORD_MIN_LENGTH', 6);
define('LOGIN_MAX_ATTEMPTS', 3);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutos

// URLs e caminhos
define('BASE_URL', 'http://localhost/gelo-canada');
define('API_URL', BASE_URL . '/app/api');
define('ADMIN_URL', BASE_URL . '/public/admin.php');
define('TOTEM_URL', BASE_URL . '/public/totem.php');
define('UI_URL', BASE_URL . '/public/ui.php');

// Configurações específicas do Totem
define('TOTEM_AUTO_LOGOUT', 300); // 5 minutos
define('TOTEM_SOUND_ENABLED', true);
define('TOTEM_PRINTER_ENABLED', false);

// Configurações de relatórios
define('REPORT_DEFAULT_DAYS', 30);
define('REPORT_MAX_DAYS', 365);
define('EXPORT_MAX_ROWS', 10000);

// Configurações de cache
define('CACHE_ENABLED', true);
define('CACHE_PATH', __DIR__ . '/../cache/');
define('CACHE_LIFETIME', 3600); // 1 hora

// Configurações de email (para alertas)
define('EMAIL_ENABLED', false);
define('EMAIL_HOST', 'smtp.gmail.com');
define('EMAIL_PORT', 587);
define('EMAIL_USER', '');
define('EMAIL_PASS', '');
define('EMAIL_FROM', 'sistema@gelocanada.com');

// Configurações de notificações
define('NOTIFICATION_ESTOQUE_MINIMO', true);
define('NOTIFICATION_ESTOQUE_ZERO', true);
define('NOTIFICATION_SYNC_ERROR', true);

// Configurações de performance
define('QUERY_TIMEOUT', 30);
define('MAX_CONNECTIONS', 100);
define('MEMORY_LIMIT', '256M');

// Configurações de desenvolvimento
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    if (!defined('DEBUG')) {
        define('DEBUG', true);
    }
    if (!defined('LOG_LEVEL')) {
        define('LOG_LEVEL', 'DEBUG');
    }
}

// Configurações de timezone
date_default_timezone_set(TIMEZONE);

// Configurações de sessão (apenas se não estiver em CLI)
if (php_sapi_name() !== 'cli') {
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    ini_set('session.cookie_lifetime', SESSION_LIFETIME);
}

// Configurações de memória
ini_set('memory_limit', MEMORY_LIMIT);
ini_set('max_execution_time', QUERY_TIMEOUT);

// Configurações de upload
ini_set('upload_max_filesize', UPLOAD_MAX_SIZE);
ini_set('post_max_size', UPLOAD_MAX_SIZE * 2);

// Função para obter configuração
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Função para verificar se é ambiente de desenvolvimento
function isDevelopment() {
    return ENVIRONMENT === 'development';
}

// Função para verificar se é ambiente de produção
function isProduction() {
    return ENVIRONMENT === 'production';
}

// Função para obter URL base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $path = dirname($_SERVER['SCRIPT_NAME']);
    return $protocol . '://' . $host . $path;
}

// Função para obter caminho absoluto
function getAbsolutePath($relativePath) {
    return __DIR__ . '/../' . ltrim($relativePath, '/');
}

// Função para obter caminho relativo
function getRelativePath($absolutePath) {
    $basePath = __DIR__ . '/../';
    return str_replace($basePath, '', $absolutePath);
}
