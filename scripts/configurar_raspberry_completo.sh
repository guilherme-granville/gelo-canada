#!/bin/bash
# Script de configuração completa do Raspberry Pi
# Sistema de Controle de Estoque - Gelo Canada

set -e  # Parar em caso de erro

echo "🍓 ==============================================="
echo "🍓 CONFIGURAÇÃO COMPLETA DO RASPBERRY PI"
echo "🍓 Sistema de Controle de Estoque - Gelo Canada"
echo "🍓 ==============================================="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Função para log
log() {
    echo -e "${GREEN}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

error() {
    echo -e "${RED}[ERRO]${NC} $1"
    exit 1
}

warning() {
    echo -e "${YELLOW}[AVISO]${NC} $1"
}

info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

# Verificar se está rodando como root
if [ "$EUID" -eq 0 ]; then
    error "Não execute este script como root. Use: bash configurar_raspberry_completo.sh"
fi

# Verificar se é Raspberry Pi
if ! grep -q "Raspberry Pi" /proc/cpuinfo; then
    warning "Este script é específico para Raspberry Pi"
    read -p "Deseja continuar mesmo assim? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

log "Iniciando configuração completa do sistema..."

# PASSO 1: Atualização do sistema
log "PASSO 1: Atualizando sistema..."
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip bc

# PASSO 2: Instalação do Apache
log "PASSO 2: Instalando Apache..."
sudo apt install -y apache2
sudo systemctl enable apache2
sudo systemctl start apache2

# Verificar se Apache está rodando
if ! systemctl is-active --quiet apache2; then
    error "Falha ao iniciar Apache"
fi

# PASSO 3: Instalação do PHP
log "PASSO 3: Instalando PHP e extensões..."
sudo apt install -y php php-cli php-fpm php-mysql php-sqlite3 php-curl php-mbstring php-xml php-zip php-gd

# Verificar extensões PHP
required_extensions=("pdo" "sqlite3" "json" "curl" "mbstring")
for ext in "${required_extensions[@]}"; do
    if ! php -m | grep -q "$ext"; then
        error "Extensão PHP $ext não encontrada"
    fi
done

# PASSO 4: Configuração do Apache
log "PASSO 4: Configurando Apache..."
sudo a2enmod rewrite
sudo usermod -a -G www-data $USER
sudo chown -R www-data:www-data /var/www/html
sudo chmod -R 755 /var/www/html
sudo systemctl restart apache2

# PASSO 5: Baixar sistema
log "PASSO 5: Baixando sistema..."
cd /var/www/html
sudo rm -rf index.html

# Verificar se já existe o sistema
if [ -d "gelo-canada" ]; then
    warning "Sistema já existe. Fazendo backup..."
    sudo mv gelo-canada gelo-canada.backup.$(date +%Y%m%d_%H%M%S)
fi

# Baixar via Git
sudo git clone https://github.com/guilherme-granville/gelo-canada.git .

# PASSO 6: Configurar permissões
log "PASSO 6: Configurando permissões..."
sudo chown -R www-data:www-data /var/www/html/gelo-canada
sudo chmod -R 755 /var/www/html/gelo-canada
sudo chmod -R 777 /var/www/html/gelo-canada/data
sudo chmod -R 777 /var/www/html/gelo-canada/logs
sudo chmod -R 777 /var/www/html/gelo-canada/backups
sudo chmod -R 777 /var/www/html/gelo-canada/cache
sudo chmod -R 777 /var/www/html/gelo-canada/public/uploads
sudo chmod +x /var/www/html/gelo-canada/scripts/*.sh

# PASSO 7: Configurar banco de dados
log "PASSO 7: Configurando banco de dados..."
cd /var/www/html/gelo-canada
sudo php app/db/migrate.php

# Verificar tabelas
log "Verificando tabelas do banco..."
tables=("usuarios" "produtos" "movimentacoes" "estoque" "logs" "sync_log")
for table in "${tables[@]}"; do
    if sudo php -r "
    require_once 'config/config.php';
    require_once 'app/core/Database.php';
    \$db = Database::getInstance();
    if(\$db->tableExists('$table')) {
        echo 'OK';
    } else {
        echo 'ERRO';
    }
    " | grep -q "OK"; then
        log "✅ Tabela $table: OK"
    else
        error "❌ Tabela $table: ERRO"
    fi
done

# PASSO 8: Configurar firewall
log "PASSO 8: Configurando firewall..."
sudo apt install -y ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

# PASSO 9: Configurar cron
log "PASSO 9: Configurando sincronização automática..."
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/bin/php /var/www/html/gelo-canada/scripts/sync_cron.php") | crontab -

# PASSO 10: Configurar auto-inicialização
log "PASSO 10: Configurando auto-inicialização..."
sudo tee /etc/systemd/system/gelo-canada.service > /dev/null <<EOF
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
EOF

sudo systemctl daemon-reload
sudo systemctl enable gelo-canada.service
sudo systemctl start gelo-canada.service

# PASSO 11: Configurar logrotate
log "PASSO 11: Configurando rotação de logs..."
sudo tee /etc/logrotate.d/gelo-canada > /dev/null <<EOF
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
EOF

# PASSO 12: Configurar monitoramento
log "PASSO 12: Configurando monitoramento..."
sudo apt install -y htop

# Criar script de monitoramento
sudo tee /var/www/html/gelo-canada/scripts/monitor.sh > /dev/null <<'EOF'
#!/bin/bash
LOG_FILE="/var/www/html/gelo-canada/logs/system_monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
MEMORY_USAGE=$(free | grep Mem | awk '{printf("%.1f", $3/$2 * 100.0)}')
DISK_USAGE=$(df -h / | awk 'NR==2{print $5}' | cut -d'%' -f1)
APACHE_STATUS=$(systemctl is-active apache2)

DB_STATUS="OK"
if [ ! -f "/var/www/html/gelo-canada/data/gelo_local.db" ]; then
    DB_STATUS="ERRO"
fi

echo "[$DATE] CPU: ${CPU_USAGE}% | RAM: ${MEMORY_USAGE}% | DISK: ${DISK_USAGE}% | Apache: $APACHE_STATUS | DB: $DB_STATUS" >> $LOG_FILE

if (( $(echo "$CPU_USAGE > 80" | bc -l) )); then
    echo "[$DATE] ALERTA: CPU alta ($CPU_USAGE%)" >> $LOG_FILE
fi

if (( $(echo "$MEMORY_USAGE > 80" | bc -l) )); then
    echo "[$DATE] ALERTA: Memória alta ($MEMORY_USAGE%)" >> $LOG_FILE
fi

if [ "$DISK_USAGE" -gt 80 ]; then
    echo "[$DATE] ALERTA: Disco cheio ($DISK_USAGE%)" >> $LOG_FILE
fi
EOF

sudo chmod +x /var/www/html/gelo-canada/scripts/monitor.sh
(crontab -l 2>/dev/null; echo "*/10 * * * * /var/www/html/gelo-canada/scripts/monitor.sh") | crontab -

# PASSO 13: Configurar backup automático
log "PASSO 13: Configurando backup automático..."
sudo tee /var/www/html/gelo-canada/scripts/backup_auto.sh > /dev/null <<'EOF'
#!/bin/bash
BACKUP_DIR="/var/www/html/gelo-canada/backups"
DATE=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="backup_$DATE.tar.gz"

cd /var/www/html/gelo-canada
sudo tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    --exclude='backups' \
    --exclude='cache' \
    --exclude='logs' \
    .

cd $BACKUP_DIR
ls -t backup_*.tar.gz | tail -n +8 | xargs -r rm

echo "Backup criado: $BACKUP_FILE"
EOF

sudo chmod +x /var/www/html/gelo-canada/scripts/backup_auto.sh
(crontab -l 2>/dev/null; echo "0 2 * * * /var/www/html/gelo-canada/scripts/backup_auto.sh") | crontab -

# PASSO 14: Teste final
log "PASSO 14: Executando teste final..."
cd /var/www/html/gelo-canada

# Teste de autenticação
if sudo php -r "
require_once 'config/config.php';
require_once 'app/core/Database.php';
require_once 'app/core/Usuario.php';

\$db = Database::getInstance();
\$usuario = new Usuario();
\$admin = \$usuario->autenticar('admin', 'admin123');

if (\$admin) {
    echo 'SUCCESS';
} else {
    echo 'FAILED';
}
" | grep -q "SUCCESS"; then
    log "✅ Teste de autenticação: OK"
else
    error "❌ Teste de autenticação: FALHOU"
fi

# Obter IP do sistema
IP=$(hostname -I | awk '{print $1}')

echo ""
echo "🎉 ==============================================="
echo "🎉 CONFIGURAÇÃO CONCLUÍDA COM SUCESSO!"
echo "🎉 ==============================================="
echo ""
echo "📱 ACESSO AO SISTEMA:"
echo "   🌐 Painel Admin: http://$IP/gelo-canada/public/login.php"
echo "   🍓 Totem:        http://$IP/gelo-canada/public/totem.php"
echo "   📱 Mobile:       http://$IP/gelo-canada/public/ui.php"
echo ""
echo "🔐 CREDENCIAIS PADRÃO:"
echo "   👤 Login: admin"
echo "   🔑 Senha: admin123"
echo ""
echo "🔧 COMANDOS ÚTEIS:"
echo "   📊 Monitor: htop"
echo "   📝 Logs: sudo tail -f /var/www/html/gelo-canada/logs/php_errors.log"
echo "   🔄 Reiniciar: sudo systemctl restart apache2"
echo "   📋 Cron: crontab -l"
echo ""
echo "📊 SERVIÇOS ATIVOS:"
echo "   ✅ Apache: $(systemctl is-active apache2)"
echo "   ✅ Gelo-Canada: $(systemctl is-active gelo-canada)"
echo "   ✅ Firewall: $(sudo ufw status | head -1)"
echo ""
echo "🚀 Sistema pronto para uso em produção!"
echo ""

# Executar monitoramento inicial
log "Executando monitoramento inicial..."
/var/www/html/gelo-canada/scripts/monitor.sh

log "Configuração completa finalizada!"
