# ☁️ CONFIGURAÇÃO DE SINCRONIZAÇÃO COM NUVEM

## 📋 CONFIGURAÇÕES NECESSÁRIAS

### **1. Arquivo de Configuração Principal**
Edite: `config/config.php`

```php
<?php
// Configurações do Banco Local (SQLite)
define('DB_TYPE', 'sqlite');
define('DB_PATH', __DIR__ . '/../data/gelo_local.db');

// Configurações do Banco na Nuvem
define('DB_CLOUD_TYPE', 'mysql'); // ou 'pgsql'
define('DB_CLOUD_HOST', 'seu-servidor-mysql.com');
define('DB_CLOUD_NAME', 'gelo_canada');
define('DB_CLOUD_USER', 'usuario_cloud');
define('DB_CLOUD_PASS', 'senha_super_secreta');
define('DB_CLOUD_PORT', 3306);

// Configurações de Sincronização
define('SYNC_TOKEN', 'token-secreto-para-autenticacao');
define('SYNC_URL', 'https://seu-servidor.com/api/sync');
define('SYNC_INTERVAL', 300); // 5 minutos
define('SYNC_RETRY_ATTEMPTS', 3);
define('SYNC_TIMEOUT', 30);

// Configurações do Sistema
define('SYSTEM_NAME', 'Gelo Canada');
define('SYSTEM_VERSION', '1.0.0');
define('TIMEZONE', 'America/Sao_Paulo');
?>
```

### **2. Script de Sincronização**
Criar: `scripts/sync_to_cloud.php`

```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/SyncService.php';

$sync = new SyncService();
$result = $sync->syncToCloud();

if ($result['success']) {
    echo "✅ Sincronização OK: " . $result['message'] . "\n";
    exit(0);
} else {
    echo "❌ Erro: " . $result['message'] . "\n";
    exit(1);
}
?>
```

### **3. Configuração do Cron**
```bash
# Editar crontab:
crontab -e

# Adicionar linha para sincronização a cada 5 minutos:
*/5 * * * * /usr/bin/php /var/www/html/gelo-canada/scripts/sync_to_cloud.php >> /var/www/html/gelo-canada/logs/sync.log 2>&1
```

## 🔧 IMPLEMENTAÇÃO NO RASPBERRY PI

### **Passo 1: Configurar Banco na Nuvem**
```bash
# No servidor da nuvem, criar banco:
mysql -u root -p
CREATE DATABASE gelo_canada;
CREATE USER 'gelo_user'@'%' IDENTIFIED BY 'senha_super_secreta';
GRANT ALL PRIVILEGES ON gelo_canada.* TO 'gelo_user'@'%';
FLUSH PRIVILEGES;
```

### **Passo 2: Configurar no Raspberry Pi**
```bash
# Editar configuração:
sudo nano /var/www/html/gelo-canada/config/config.php

# Testar conexão:
sudo php -r "
require 'config/config.php';
try {
    \$db = Database::getCloudInstance();
    echo 'Conexão OK\n';
} catch (Exception \$e) {
    echo 'Erro: ' . \$e->getMessage() . '\n';
}
"
```

### **Passo 3: Executar Migração**
```bash
# Criar tabelas na nuvem:
sudo php app/db/migrate_cloud.php
```

### **Passo 4: Testar Sincronização**
```bash
# Executar sincronização manual:
sudo php scripts/sync_to_cloud.php

# Ver logs:
tail -f logs/sync.log
```

## 📊 MONITORAMENTO

### **Verificar Status:**
```bash
# Ver última sincronização:
tail -n 20 logs/sync.log

# Ver erros:
grep "ERROR" logs/sync.log

# Ver estatísticas:
grep "Sincronização OK" logs/sync.log | wc -l
```

### **Comandos Úteis:**
```bash
# Forçar sincronização:
sudo php scripts/sync_to_cloud.php

# Ver configuração:
cat config/config.php | grep DB_CLOUD

# Testar conectividade:
ping seu-servidor-mysql.com
telnet seu-servidor-mysql.com 3306
```

## 🚨 SOLUÇÃO DE PROBLEMAS

### **Erro de Conexão:**
1. Verificar se o servidor está online
2. Verificar credenciais
3. Verificar firewall
4. Testar conectividade de rede

### **Erro de Sincronização:**
1. Verificar logs detalhados
2. Verificar formato dos dados
3. Verificar conflitos de dados
4. Executar sincronização manual

### **Logs Importantes:**
- `logs/sync.log` - Sincronização
- `logs/error.log` - Erros gerais
- `/var/log/apache2/error.log` - Erros do Apache
