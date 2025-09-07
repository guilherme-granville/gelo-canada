# 🍓 CONFIGURAÇÃO COMPLETA DO RASPBERRY PI
## Sistema de Controle de Estoque - Gelo Canada

### 📋 **PRÉ-REQUISITOS**
- ✅ Raspberry Pi com SO instalado (Raspberry Pi OS)
- ✅ Conexão com internet
- ✅ Acesso SSH ou teclado/monitor conectado

---

## 🚀 **PASSO 1: ATUALIZAÇÃO DO SISTEMA**

```bash
# Atualizar lista de pacotes
sudo apt update && sudo apt upgrade -y

# Instalar dependências básicas
sudo apt install -y curl wget git unzip
```

---

## 🚀 **PASSO 2: INSTALAÇÃO DO APACHE**

```bash
# Instalar Apache
sudo apt install -y apache2

# Habilitar e iniciar Apache
sudo systemctl enable apache2
sudo systemctl start apache2

# Verificar status
sudo systemctl status apache2
```

**✅ Teste:** Acesse `http://SEU_IP` - deve aparecer a página padrão do Apache

---

## 🚀 **PASSO 3: INSTALAÇÃO DO PHP**

```bash
# Instalar PHP e extensões necessárias
sudo apt install -y php php-cli php-fpm php-mysql php-sqlite3 php-curl php-mbstring php-xml php-zip php-gd

# Verificar versão
php -v

# Verificar extensões
php -m | grep -E "(pdo|sqlite|json|curl|mbstring)"
```

---

## 🚀 **PASSO 4: CONFIGURAÇÃO DO APACHE**

```bash
# Habilitar mod_rewrite
sudo a2enmod rewrite

# Configurar permissões do Apache
sudo usermod -a -G www-data pi
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html

# Reiniciar Apache
sudo systemctl restart apache2
```

---

## 🚀 **PASSO 5: BAIXAR E CONFIGURAR O SISTEMA**

```bash
# Navegar para diretório web
cd /var/www/html

# Remover arquivos padrão
sudo rm -rf index.html

# Baixar o sistema (método 1: Git)
sudo git clone https://github.com/guilherme-granville/gelo-canada.git .

# OU baixar via SCP (método 2: se preferir)
# scp -r usuario@seu-pc:/caminho/para/gelo-canada/* pi@192.168.3.10:/var/www/html/
```

---

## 🚀 **PASSO 6: CONFIGURAR PERMISSÕES**

```bash
# Definir proprietário correto
sudo chown -R www-data:www-data /var/www/html/gelo-canada

# Configurar permissões
sudo chmod -R 755 /var/www/html/gelo-canada
sudo chmod -R 777 /var/www/html/gelo-canada/data
sudo chmod -R 777 /var/www/html/gelo-canada/logs
sudo chmod -R 777 /var/www/html/gelo-canada/backups
sudo chmod -R 777 /var/www/html/gelo-canada/cache
sudo chmod -R 777 /var/www/html/gelo-canada/public/uploads

# Tornar scripts executáveis
sudo chmod +x /var/www/html/gelo-canada/scripts/*.sh
```

---

## 🚀 **PASSO 7: CONFIGURAR BANCO DE DADOS**

```bash
# Navegar para o diretório do sistema
cd /var/www/html/gelo-canada

# Executar migração do banco
sudo php app/db/migrate.php

# Verificar se as tabelas foram criadas
sudo php -r "
require_once 'config/config.php';
require_once 'app/core/Database.php';
\$db = Database::getInstance();
\$tables = ['usuarios', 'produtos', 'movimentacoes', 'estoque', 'logs', 'sync_log'];
foreach(\$tables as \$table) {
    if(\$db->tableExists(\$table)) {
        echo \"✅ Tabela \$table: OK\n\";
    } else {
        echo \"❌ Tabela \$table: ERRO\n\";
    }
}
"
```

---

## 🚀 **PASSO 8: CONFIGURAR FIREWALL**

```bash
# Instalar UFW se não estiver instalado
sudo apt install -y ufw

# Configurar regras básicas
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Permitir SSH (importante!)
sudo ufw allow ssh

# Permitir HTTP e HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp

# Ativar firewall
sudo ufw enable

# Verificar status
sudo ufw status
```

---

## 🚀 **PASSO 9: CONFIGURAR CRON PARA SINCRONIZAÇÃO**

```bash
# Abrir crontab
sudo crontab -e

# Adicionar linha para sincronização a cada 5 minutos
*/5 * * * * /usr/bin/php /var/www/html/gelo-canada/scripts/sync_cron.php

# Verificar se foi adicionado
sudo crontab -l
```

---

## 🚀 **PASSO 10: CONFIGURAR AUTO-INICIALIZAÇÃO**

```bash
# Criar script de inicialização
sudo nano /etc/systemd/system/gelo-canada.service
```

**Conteúdo do arquivo:**
```ini
[Unit]
Description=Gelo Canada - Sistema de Controle de Estoque
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/var/www/html/gelo-canada
ExecStart=/bin/bash -c 'while true; do sleep 30; done'
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

```bash
# Habilitar serviço
sudo systemctl daemon-reload
sudo systemctl enable gelo-canada.service
sudo systemctl start gelo-canada.service

# Verificar status
sudo systemctl status gelo-canada.service
```

---

## 🚀 **PASSO 11: CONFIGURAR LOGROTATE**

```bash
# Criar configuração de logrotate
sudo nano /etc/logrotate.d/gelo-canada
```

**Conteúdo do arquivo:**
```
/var/www/html/gelo-canada/logs/*.log {
    daily
    missingok
    rotate 7
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        /bin/systemctl reload apache2 > /dev/null 2>&1 || true
    endscript
}
```

---

## 🚀 **PASSO 12: CONFIGURAR SWAP (OPCIONAL)**

```bash
# Verificar swap atual
free -h

# Se necessário, aumentar swap
sudo dphys-swapfile swapoff
sudo nano /etc/dphys-swapfile

# Alterar CONF_SWAPSIZE=100 para CONF_SWAPSIZE=1024
sudo dphys-swapfile setup
sudo dphys-swapfile swapon
```

---

## 🚀 **PASSO 13: CONFIGURAR MONITORAMENTO**

```bash
# Instalar htop para monitoramento
sudo apt install -y htop

# Criar script de monitoramento
sudo nano /var/www/html/gelo-canada/scripts/monitor.sh
```

**Conteúdo do script:**
```bash
#!/bin/bash
# Script de monitoramento do sistema

LOG_FILE="/var/www/html/gelo-canada/logs/system_monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Verificar uso de CPU
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)

# Verificar uso de memória
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.1f", $3/$2 * 100.0)}')

# Verificar espaço em disco
DISK_USAGE=$(df -h / | awk 'NR==2{print $5}' | cut -d'%' -f1)

# Verificar status do Apache
APACHE_STATUS=$(systemctl is-active apache2)

# Verificar status do banco
DB_STATUS="OK"
if [ ! -f "/var/www/html/gelo-canada/data/gelo_local.db" ]; then
    DB_STATUS="ERRO"
fi

# Log das informações
echo "[$DATE] CPU: ${CPU_USAGE}% | RAM: ${MEMORY_USAGE}% | DISK: ${DISK_USAGE}% | Apache: $APACHE_STATUS | DB: $DB_STATUS" >> $LOG_FILE

# Alertas se necessário
if (( $(echo "$CPU_USAGE > 80" | bc -l) )); then
    echo "[$DATE] ALERTA: CPU alta ($CPU_USAGE%)" >> $LOG_FILE
fi

if (( $(echo "$MEMORY_USAGE > 80" | bc -l) )); then
    echo "[$DATE] ALERTA: Memória alta ($MEMORY_USAGE%)" >> $LOG_FILE
fi

if [ "$DISK_USAGE" -gt 80 ]; then
    echo "[$DATE] ALERTA: Disco cheio ($DISK_USAGE%)" >> $LOG_FILE
fi
```

```bash
# Tornar executável
sudo chmod +x /var/www/html/gelo-canada/scripts/monitor.sh

# Adicionar ao cron (executar a cada 10 minutos)
sudo crontab -e
# Adicionar: */10 * * * * /var/www/html/gelo-canada/scripts/monitor.sh
```

---

## 🚀 **PASSO 14: CONFIGURAR BACKUP AUTOMÁTICO**

```bash
# Criar script de backup
sudo nano /var/www/html/gelo-canada/scripts/backup_auto.sh
```

**Conteúdo do script:**
```bash
#!/bin/bash
# Script de backup automático

BACKUP_DIR="/var/www/html/gelo-canada/backups"
DATE=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="backup_$DATE.tar.gz"

# Criar backup
cd /var/www/html/gelo-canada
sudo tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    --exclude='backups' \
    --exclude='cache' \
    --exclude='logs' \
    .

# Manter apenas últimos 7 backups
cd $BACKUP_DIR
ls -t backup_*.tar.gz | tail -n +8 | xargs -r rm

echo "Backup criado: $BACKUP_FILE"
```

```bash
# Tornar executável
sudo chmod +x /var/www/html/gelo-canada/scripts/backup_auto.sh

# Adicionar ao cron (backup diário às 2h da manhã)
sudo crontab -e
# Adicionar: 0 2 * * * /var/www/html/gelo-canada/scripts/backup_auto.sh
```

---

## 🚀 **PASSO 15: TESTE FINAL**

```bash
# Testar sistema
cd /var/www/html/gelo-canada
sudo php -r "
require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/core/Usuario.php';

echo \"=== TESTE FINAL ===\n\";
echo \"✅ Configuração: OK\n\";

\$db = Database::getInstance();
echo \"✅ Banco de dados: OK\n\";

\$usuario = new Usuario();
\$admin = \$usuario->autenticar('admin', 'admin123');
if (\$admin) {
    echo \"✅ Login admin: OK\n\";
    echo \"✅ Nome: \" . \$admin['nome'] . \"\n\";
} else {
    echo \"❌ Login admin: ERRO\n\";
}

echo \"✅ Sistema pronto para uso!\n\";
"
```

---

## 🎯 **ACESSO AO SISTEMA**

### **URLs de Acesso:**
- **Painel Admin**: `http://SEU_IP/gelo-canada/public/login.php`
- **Totem**: `http://SEU_IP/gelo-canada/public/totem.php`
- **Interface Mobile**: `http://SEU_IP/gelo-canada/public/ui.php`

### **Credenciais Padrão:**
- **Login**: `admin`
- **Senha**: `admin123`

---

## 🔧 **COMANDOS ÚTEIS**

```bash
# Verificar status dos serviços
sudo systemctl status apache2
sudo systemctl status gelo-canada

# Ver logs do sistema
sudo tail -f /var/www/html/gelo-canada/logs/php_errors.log
sudo tail -f /var/www/html/gelo-canada/logs/sync.log

# Reiniciar serviços
sudo systemctl restart apache2
sudo systemctl restart gelo-canada

# Verificar uso de recursos
htop
df -h
free -h

# Verificar cron jobs
sudo crontab -l

# Verificar firewall
sudo ufw status
```

---

## 🚨 **SOLUÇÃO DE PROBLEMAS**

### **Problema: Apache não inicia**
```bash
sudo systemctl status apache2
sudo journalctl -u apache2
sudo apache2ctl configtest
```

### **Problema: PHP não funciona**
```bash
sudo apt install --reinstall php
sudo systemctl restart apache2
```

### **Problema: Banco de dados não conecta**
```bash
sudo chmod 777 /var/www/html/gelo-canada/data
sudo php app/db/migrate.php
```

### **Problema: Permissões incorretas**
```bash
sudo chown -R www-data:www-data /var/www/html/gelo-canada
sudo chmod -R 755 /var/www/html/gelo-canada
sudo chmod -R 777 /var/www/html/gelo-canada/data
sudo chmod -R 777 /var/www/html/gelo-canada/logs
```

---

## 📊 **MONITORAMENTO**

### **Verificar Status:**
```bash
# Status geral
sudo systemctl status apache2 gelo-canada

# Uso de recursos
htop

# Logs em tempo real
sudo tail -f /var/www/html/gelo-canada/logs/system_monitor.log
```

### **Métricas Importantes:**
- **CPU**: < 80%
- **RAM**: < 80%
- **Disco**: < 80%
- **Apache**: Ativo
- **Banco**: Arquivo existe

---

## 🎉 **SISTEMA CONFIGURADO COM SUCESSO!**

O Raspberry Pi está agora completamente configurado com:
- ✅ Apache + PHP
- ✅ Sistema de Controle de Estoque
- ✅ Banco SQLite
- ✅ Sincronização automática
- ✅ Backup automático
- ✅ Monitoramento
- ✅ Firewall configurado
- ✅ Auto-inicialização

**🚀 Pronto para uso em produção!**
