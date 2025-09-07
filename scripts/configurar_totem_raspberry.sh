#!/bin/bash
# Script de configuração do Totem - Raspberry Pi
# Sistema de Controle de Estoque - Apenas Totem

set -e  # Parar em caso de erro

echo "🍓 ==============================================="
echo "🍓 CONFIGURAÇÃO TOTEM - RASPBERRY PI"
echo "🍓 Sistema de Controle de Estoque - Apenas Totem"
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
    error "Não execute este script como root. Use: bash configurar_totem_raspberry.sh"
fi

log "Iniciando configuração do totem..."

# PASSO 1: Atualização do sistema
log "PASSO 1: Atualizando sistema..."
sudo apt update && sudo apt upgrade -y
sudo apt install -y apache2 php php-cli php-sqlite3 php-curl php-mbstring unclutter

# PASSO 2: Configuração do Apache
log "PASSO 2: Configurando Apache..."
sudo a2enmod rewrite
sudo systemctl enable apache2
sudo systemctl start apache2

# Verificar se Apache está rodando
if ! systemctl is-active --quiet apache2; then
    error "Falha ao iniciar Apache"
fi

# PASSO 3: Baixar sistema
log "PASSO 3: Baixando sistema..."
cd /var/www/html
sudo rm -rf index.html

# Verificar se já existe o sistema
if [ -d "gelo-canada" ]; then
    warning "Sistema já existe. Fazendo backup..."
    sudo mv gelo-canada gelo-canada.backup.$(date +%Y%m%d_%H%M%S)
fi

# Baixar via Git
sudo git clone https://github.com/guilherme-granville/gelo-canada.git .

# PASSO 4: Configurar permissões
log "PASSO 4: Configurando permissões..."
sudo chown -R www-data:www-data /var/www/html/gelo-canada
sudo chmod -R 755 /var/www/html/gelo-canada
sudo chmod -R 777 /var/www/html/gelo-canada/data
sudo chmod -R 777 /var/www/html/gelo-canada/logs
sudo chmod -R 777 /var/www/html/gelo-canada/cache

# PASSO 5: Configurar banco de dados
log "PASSO 5: Configurando banco de dados..."
cd /var/www/html/gelo-canada
sudo php app/db/migrate.php

# Verificar se funcionou
if ! sudo php -r "
require_once 'config/config.php';
require_once 'app/core/Database.php';
\$db = Database::getInstance();
echo (\$db ? 'OK' : 'ERRO');
" | grep -q "OK"; then
    error "Falha ao configurar banco de dados"
fi

# PASSO 6: Criar arquivo de bloqueio
log "PASSO 6: Configurando bloqueio de acesso..."
sudo tee /var/www/html/gelo-canada/.htaccess > /dev/null <<'EOF'
# Permitir apenas acesso ao totem
RewriteEngine On

# Bloquear acesso a todas as páginas exceto totem
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/totem\.php$
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/totem$
RewriteCond %{REQUEST_URI} !^/gelo-canada/app/api/
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/uploads/
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/assets/
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/css/
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/js/
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/images/
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/fonts/
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/favicon.ico$
RewriteCond %{REQUEST_URI} !^/gelo-canada/public/robots.txt$
RewriteRule ^(.*)$ /gelo-canada/public/totem.php [R=302,L]

# Bloquear acesso direto a arquivos sensíveis
<Files "*.php">
    Order Allow,Deny
    Allow from all
</Files>

<Files "config.php">
    Order Deny,Allow
    Deny from all
</Files>

<Files "*.log">
    Order Deny,Allow
    Deny from all
</Files>

# Bloquear acesso a diretórios sensíveis
RedirectMatch 403 ^/gelo-canada/config/
RedirectMatch 403 ^/gelo-canada/app/core/
RedirectMatch 403 ^/gelo-canada/data/
RedirectMatch 403 ^/gelo-canada/logs/
RedirectMatch 403 ^/gelo-canada/backups/
RedirectMatch 403 ^/gelo-canada/scripts/
RedirectMatch 403 ^/gelo-canada/docs/
EOF

# PASSO 7: Configurar redirecionamento automático
log "PASSO 7: Configurando redirecionamento automático..."
sudo tee /var/www/html/index.html > /dev/null <<'EOF'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=/gelo-canada/public/totem.php">
    <title>Redirecionando...</title>
</head>
<body>
    <script>
        window.location.href = '/gelo-canada/public/totem.php';
    </script>
    <p>Redirecionando para o totem...</p>
</body>
</html>
EOF

# PASSO 8: Configurar firewall restritivo
log "PASSO 8: Configurando firewall restritivo..."
sudo apt install -y ufw
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 80/tcp
sudo ufw deny 443/tcp
sudo ufw --force enable

# PASSO 9: Configurar auto-inicialização do totem
log "PASSO 9: Configurando auto-inicialização do totem..."

# Criar script de inicialização do totem
sudo tee /home/pi/start_totem.sh > /dev/null <<'EOF'
#!/bin/bash

# Aguardar rede estar pronta
sleep 10

# Esconder cursor
unclutter -idle 1 &

# Configurar tela para não desligar
xset s off
xset -dpms
xset s noblank

# Abrir totem em tela cheia
chromium-browser \
    --kiosk \
    --no-sandbox \
    --disable-infobars \
    --disable-session-crashed-bubble \
    --disable-dev-shm-usage \
    --disable-gpu \
    --start-fullscreen \
    --app=http://localhost/gelo-canada/public/totem.php &

# Se Chromium não estiver disponível, usar Firefox
if ! command -v chromium-browser &> /dev/null; then
    firefox --kiosk http://localhost/gelo-canada/public/totem.php &
fi
EOF

# Tornar executável
sudo chmod +x /home/pi/start_totem.sh

# PASSO 10: Configurar auto-login e inicialização
log "PASSO 10: Configurando auto-login e inicialização..."
sudo raspi-config nonint do_boot_behaviour B4

# Criar serviço systemd para o totem
sudo tee /etc/systemd/system/totem-gelo.service > /dev/null <<'EOF'
[Unit]
Description=Totem Gelo Canada
After=graphical-session.target

[Service]
Type=simple
User=pi
Environment=DISPLAY=:0
ExecStart=/home/pi/start_totem.sh
Restart=always
RestartSec=10

[Install]
WantedBy=graphical-session.target
EOF

# Habilitar serviço
sudo systemctl daemon-reload
sudo systemctl enable totem-gelo.service

# PASSO 11: Configurar sincronização automática
log "PASSO 11: Configurando sincronização automática..."
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/bin/php /var/www/html/gelo-canada/scripts/sync_cron.php") | crontab -

# PASSO 12: Configurar monitoramento simples
log "PASSO 12: Configurando monitoramento..."
sudo tee /var/www/html/gelo-canada/scripts/monitor_totem.sh > /dev/null <<'EOF'
#!/bin/bash
LOG_FILE="/var/www/html/gelo-canada/logs/totem_monitor.log"
DATE=$(date '+%Y-%m-%d %H:%M:%S')

# Verificar se Apache está rodando
APACHE_STATUS=$(systemctl is-active apache2)

# Verificar se banco existe
DB_STATUS="OK"
if [ ! -f "/var/www/html/gelo-canada/data/gelo_local.db" ]; then
    DB_STATUS="ERRO"
fi

# Verificar se totem está acessível
TOTEM_STATUS="OK"
if ! curl -s http://localhost/gelo-canada/public/totem.php > /dev/null; then
    TOTEM_STATUS="ERRO"
fi

# Log das informações
echo "[$DATE] Apache: $APACHE_STATUS | DB: $DB_STATUS | Totem: $TOTEM_STATUS" >> $LOG_FILE

# Se houver erro, reiniciar Apache
if [ "$APACHE_STATUS" != "active" ] || [ "$TOTEM_STATUS" != "OK" ]; then
    echo "[$DATE] Reiniciando Apache..." >> $LOG_FILE
    sudo systemctl restart apache2
fi
EOF

sudo chmod +x /var/www/html/gelo-canada/scripts/monitor_totem.sh
(crontab -l 2>/dev/null; echo "*/5 * * * * /var/www/html/gelo-canada/scripts/monitor_totem.sh") | crontab -

# PASSO 13: Configurar backup simples
log "PASSO 13: Configurando backup automático..."
sudo tee /var/www/html/gelo-canada/scripts/backup_totem.sh > /dev/null <<'EOF'
#!/bin/bash
BACKUP_DIR="/var/www/html/gelo-canada/backups"
DATE=$(date '+%Y%m%d_%H%M%S')
BACKUP_FILE="totem_backup_$DATE.tar.gz"

# Criar backup apenas dos dados essenciais
cd /var/www/html/gelo-canada
sudo tar -czf "$BACKUP_DIR/$BACKUP_FILE" \
    data/ \
    config/config.php \
    public/totem.php \
    app/core/ \
    app/api/

# Manter apenas últimos 3 backups
cd $BACKUP_DIR
ls -t totem_backup_*.tar.gz | tail -n +4 | xargs -r rm

echo "Backup do totem criado: $BACKUP_FILE"
EOF

sudo chmod +x /var/www/html/gelo-canada/scripts/backup_totem.sh
(crontab -l 2>/dev/null; echo "0 3 * * * /var/www/html/gelo-canada/scripts/backup_totem.sh") | crontab -

# PASSO 14: Teste final
log "PASSO 14: Executando teste final..."

# Testar totem
if curl -s http://localhost/gelo-canada/public/totem.php | head -10 | grep -q "html"; then
    log "✅ Totem acessível: OK"
else
    error "❌ Totem não acessível"
fi

# Verificar se outras páginas estão bloqueadas
if curl -s -o /dev/null -w "%{http_code}" http://localhost/gelo-canada/public/login.php | grep -q "302"; then
    log "✅ Bloqueio de acesso: OK"
else
    warning "⚠️ Bloqueio de acesso pode não estar funcionando"
fi

# Obter IP do sistema
IP=$(hostname -I | awk '{print $1}')

echo ""
echo "🎉 ==============================================="
echo "🎉 TOTEM CONFIGURADO COM SUCESSO!"
echo "🎉 ==============================================="
echo ""
echo "🔒 SEGURANÇA IMPLEMENTADA:"
echo "   ❌ Painel Admin: BLOQUEADO"
echo "   ❌ Interface Mobile: BLOQUEADA"
echo "   ❌ Login: BLOQUEADO"
echo "   ❌ Arquivos de Config: BLOQUEADOS"
echo "   ✅ Totem: ACESSÍVEL"
echo ""
echo "🌐 ACESSO:"
echo "   🍓 Totem: http://$IP/gelo-canada/public/totem.php"
echo "   🔄 Redirecionamento: http://$IP/ → Totem"
echo ""
echo "🔧 COMANDOS ÚTEIS:"
echo "   📊 Status: sudo systemctl status totem-gelo"
echo "   🔄 Reiniciar: sudo systemctl restart totem-gelo"
echo "   📝 Logs: sudo journalctl -u totem-gelo -f"
echo "   🛡️ Firewall: sudo ufw status"
echo ""
echo "⏰ CRON JOBS CONFIGURADOS:"
echo "   🔄 Sincronização: A cada 5 minutos"
echo "   📊 Monitoramento: A cada 5 minutos"
echo "   💾 Backup: Diário às 3h da manhã"
echo ""
echo "🚀 Sistema pronto para uso como totem!"
echo "   Reinicie o sistema para aplicar todas as configurações."
echo ""

# Executar monitoramento inicial
log "Executando monitoramento inicial..."
/var/www/html/gelo-canada/scripts/monitor_totem.sh

log "Configuração do totem finalizada!"
