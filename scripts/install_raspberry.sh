#!/bin/bash

# 🍓 INSTALAÇÃO AUTOMÁTICA - RASPBERRY PI
# Sistema de Controle de Estoque - Fábrica de Gelo Canada

echo "=== INSTALAÇÃO AUTOMÁTICA DO SISTEMA GELO CANADA ==="
echo "Raspberry Pi Setup - $(date)"
echo ""

# Verificar se está rodando no Raspberry Pi
if ! grep -q "Raspberry Pi" /proc/cpuinfo; then
    echo "⚠️  AVISO: Este script é destinado ao Raspberry Pi"
    read -p "Continuar mesmo assim? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Configurações
PROJECT_DIR="/var/www/html/gelo-canada"
BACKUP_DIR="$PROJECT_DIR/backups"
LOG_DIR="$PROJECT_DIR/logs"

echo "📦 Atualizando sistema..."
sudo apt update && sudo apt upgrade -y

echo "🔧 Instalando dependências..."
sudo apt install -y apache2 php php-sqlite3 php-mysql php-json php-mbstring php-curl sqlite3 git curl wget htop nano vim

echo "🌐 Configurando Apache..."
sudo a2enmod rewrite headers
sudo systemctl restart apache2

echo "⚙️ Configurando PHP..."
sudo sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 10M/' /etc/php/*/apache2/php.ini
sudo sed -i 's/post_max_size = 8M/post_max_size = 10M/' /etc/php/*/apache2/php.ini
sudo sed -i 's/memory_limit = 128M/memory_limit = 256M/' /etc/php/*/apache2/php.ini

echo "🕐 Configurando timezone..."
sudo timedatectl set-timezone America/Sao_Paulo

echo "📁 Criando estrutura de diretórios..."
sudo mkdir -p $PROJECT_DIR/{data,logs,backups,cache,public/uploads}
sudo chown -R www-data:www-data $PROJECT_DIR
sudo chmod -R 755 $PROJECT_DIR
sudo chmod -R 777 $PROJECT_DIR/{data,logs,backups,cache,public/uploads}

echo "🗄️ Configurando banco SQLite..."
sudo touch $PROJECT_DIR/data/gelo_local.db
sudo chown www-data:www-data $PROJECT_DIR/data/gelo_local.db
sudo chmod 664 $PROJECT_DIR/data/gelo_local.db

echo "⏰ Configurando cron jobs..."
# Sincronização a cada 5 minutos
SYNC_CRON="*/5 * * * * /usr/bin/php $PROJECT_DIR/scripts/sync_to_cloud.php >> $LOG_DIR/sync.log 2>&1"
# Backup diário às 1h
BACKUP_CRON="0 1 * * * $PROJECT_DIR/scripts/backup.sh >> $LOG_DIR/backup.log 2>&1"
# Manutenção diária às 2h
MAINT_CRON="0 2 * * * $PROJECT_DIR/scripts/maintenance.sh >> $LOG_DIR/maintenance.log 2>&1"

# Adicionar ao crontab
(crontab -l 2>/dev/null; echo "$SYNC_CRON"; echo "$BACKUP_CRON"; echo "$MAINT_CRON") | crontab -

echo "🛡️ Configurando firewall..."
sudo ufw allow 80/tcp
sudo ufw allow 22/tcp
sudo ufw --force enable

echo "💾 Configurando swap..."
if [ ! -f /swapfile ]; then
    sudo fallocate -l 1G /swapfile
    sudo chmod 600 /swapfile
    sudo mkswap /swapfile
    sudo swapon /swapfile
    echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab
fi

echo "🏷️ Configurando hostname..."
sudo hostnamectl set-hostname gelo-totem
echo "127.0.1.1 gelo-totem" | sudo tee -a /etc/hosts

echo "📊 Informações do sistema:"
echo "Hostname: $(hostname)"
echo "IP: $(hostname -I | awk '{print $1}')"
echo "Projeto: $PROJECT_DIR"
echo "Cron jobs:"
crontab -l

echo ""
echo "=== INSTALAÇÃO CONCLUÍDA! ==="
echo ""
echo "Próximos passos:"
echo "1. Copie os arquivos do projeto para: $PROJECT_DIR"
echo "   Opção A: scp -r C:\\xampp\\htdocs\\gelo-canada\\* pi@192.168.3.10:$PROJECT_DIR/"
echo "   Opção B: git clone https://github.com/guilherme-granville/gelo-canada.git"
echo "2. Configure o banco de dados na nuvem"
echo "3. Execute: php $PROJECT_DIR/app/db/migrate.php"
echo "4. Configure a sincronização"
echo "5. Teste o sistema em: http://192.168.3.10/gelo-canada"
echo ""
echo "Comandos úteis:"
echo "- Ver logs: tail -f $LOG_DIR/sync.log"
echo "- Verificar cron: crontab -l"
echo "- Reiniciar Apache: sudo systemctl restart apache2"
echo "- Status: sudo systemctl status apache2"
