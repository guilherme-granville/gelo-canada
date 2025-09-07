<?php
/**
 * Script para corrigir permissões de arquivos e diretórios
 * Execute como: php scripts/fix_permissions.php
 */

echo "=== CORRIGINDO PERMISSÕES DO SISTEMA ===\n\n";

// Função para executar comandos
function executarComando($comando, $descricao) {
    echo "📁 $descricao...\n";
    $resultado = shell_exec($comando . ' 2>&1');
    if ($resultado) {
        echo "   ✅ $resultado\n";
    } else {
        echo "   ⚠️  Comando executado (sem saída)\n";
    }
    echo "\n";
}

// 1. Criar diretórios necessários
echo "1. CRIANDO DIRETÓRIOS NECESSÁRIOS:\n";
$diretorios = [
    'data' => 'Diretório de dados (banco SQLite)',
    'logs' => 'Diretório de logs',
    'backups' => 'Diretório de backups',
    'cache' => 'Diretório de cache',
    'public/uploads' => 'Diretório de uploads',
    'public/uploads/produtos' => 'Diretório de imagens de produtos'
];

foreach ($diretorios as $dir => $desc) {
    $caminho = __DIR__ . '/../' . $dir;
    if (!is_dir($caminho)) {
        mkdir($caminho, 0755, true);
        echo "   ✅ Criado: $dir - $desc\n";
    } else {
        echo "   ℹ️  Já existe: $dir\n";
    }
}
echo "\n";

// 2. Corrigir permissões de diretórios
echo "2. CORRIGINDO PERMISSÕES DE DIRETÓRIOS:\n";
$comandos = [
    'chmod 755 data' => 'Permissões do diretório data',
    'chmod 755 logs' => 'Permissões do diretório logs',
    'chmod 755 backups' => 'Permissões do diretório backups',
    'chmod 755 cache' => 'Permissões do diretório cache',
    'chmod 755 public/uploads' => 'Permissões do diretório uploads',
    'chmod 755 public/uploads/produtos' => 'Permissões do diretório produtos'
];

foreach ($comandos as $comando => $desc) {
    executarComando($comando, $desc);
}

// 3. Corrigir permissões de arquivos
echo "3. CORRIGINDO PERMISSÕES DE ARQUIVOS:\n";
$comandos = [
    'chmod 644 config/config.php' => 'Arquivo de configuração',
    'chmod 644 app/core/*.php' => 'Classes core',
    'chmod 644 public/*.php' => 'Páginas públicas',
    'chmod 644 public/admin/*.php' => 'Páginas admin',
    'chmod 644 public/api/*.php' => 'APIs',
    'chmod 644 scripts/*.php' => 'Scripts',
    'chmod 755 scripts/install_raspberry.sh' => 'Script de instalação'
];

foreach ($comandos as $comando => $desc) {
    executarComando($comando, $desc);
}

// 4. Criar arquivo de banco se não existir
echo "4. VERIFICANDO BANCO DE DADOS:\n";
$dbPath = __DIR__ . '/../data/gelo_local.db';
if (!file_exists($dbPath)) {
    touch($dbPath);
    chmod($dbPath, 664);
    echo "   ✅ Criado arquivo de banco: data/gelo_local.db\n";
} else {
    echo "   ℹ️  Arquivo de banco já existe: data/gelo_local.db\n";
}
echo "\n";

// 5. Verificar extensões PHP necessárias
echo "5. VERIFICANDO EXTENSÕES PHP:\n";
$extensoes = ['pdo', 'pdo_sqlite', 'json', 'curl', 'mbstring'];
foreach ($extensoes as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext: Disponível\n";
    } else {
        echo "   ❌ $ext: NÃO DISPONÍVEL\n";
    }
}
echo "\n";

// 6. Testar conexão com banco
echo "6. TESTANDO CONEXÃO COM BANCO:\n";
try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../app/core/Database.php';
    
    $db = Database::getInstance();
    echo "   ✅ Conexão com banco: OK\n";
    
    // Testar se as tabelas existem
    $tabelas = ['usuarios', 'produtos', 'movimentacoes', 'estoque', 'logs', 'sync_log'];
    foreach ($tabelas as $tabela) {
        if ($db->tableExists($tabela)) {
            echo "   ✅ Tabela $tabela: Existe\n";
        } else {
            echo "   ⚠️  Tabela $tabela: NÃO EXISTE (execute migrate.php)\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ❌ Erro na conexão: " . $e->getMessage() . "\n";
}
echo "\n";

// 7. Verificar arquivos de log
echo "7. VERIFICANDO ARQUIVOS DE LOG:\n";
$logFiles = [
    'logs/php_errors.log' => 'Log de erros PHP',
    'logs/sync.log' => 'Log de sincronização',
    'logs/sync_cron.log' => 'Log do cron de sincronização'
];

foreach ($logFiles as $file => $desc) {
    $caminho = __DIR__ . '/../' . $file;
    if (file_exists($caminho)) {
        $tamanho = filesize($caminho);
        echo "   ℹ️  $file: Existe (" . number_format($tamanho) . " bytes)\n";
    } else {
        touch($caminho);
        chmod($caminho, 664);
        echo "   ✅ Criado: $file - $desc\n";
    }
}
echo "\n";

// 8. Resumo final
echo "=== RESUMO FINAL ===\n";
echo "✅ Permissões corrigidas com sucesso!\n";
echo "✅ Diretórios criados/verificados\n";
echo "✅ Arquivos de banco e logs verificados\n";
echo "\n";
echo "PRÓXIMOS PASSOS:\n";
echo "1. Execute: php app/db/migrate.php (se as tabelas não existirem)\n";
echo "2. Acesse: http://localhost/gelo-canada/public/login.php\n";
echo "3. Login padrão: admin / admin123\n";
echo "\n";
echo "Para Raspberry Pi:\n";
echo "1. Execute: bash scripts/install_raspberry.sh\n";
echo "2. Configure o cron: */5 * * * * php /var/www/html/gelo-canada/scripts/sync_cron.php\n";
echo "\n";
echo "=== CONCLUÍDO ===\n";
?>
