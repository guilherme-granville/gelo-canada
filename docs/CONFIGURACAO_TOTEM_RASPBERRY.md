# 🍓 CONFIGURAÇÃO TOTEM - RASPBERRY PI
## Sistema de Controle de Estoque - Apenas Totem

### 🎯 **OBJETIVO**
Configurar o Raspberry Pi para funcionar **APENAS** como totem, bloqueando acesso a todas as outras interfaces do sistema.

---

## 🚀 **PASSO 1: CONFIGURAÇÃO BÁSICA**

```bash
# Atualizar sistema
sudo apt update && sudo apt upgrade -y

# Instalar dependências
sudo apt install -y apache2 php php-cli php-sqlite3 php-curl php-mbstring unclutter

# Configurar Apache
sudo a2enmod rewrite
sudo systemctl enable apache2
sudo systemctl start apache2
```

---

## 🚀 **PASSO 2: BAIXAR SISTEMA**

```bash
# Navegar para diretório web
cd /var/www/html

# Remover arquivos padrão
sudo rm -rf index.html

# Baixar sistema
sudo git clone https://github.com/guilherme-granville/gelo-canada.git .

# Configurar permissões
sudo chown -R www-data:www-data /var/www/html/gelo-canada
sudo chmod -R 755 /var/www/html/gelo-canada
sudo chmod -R 777 /var/www/html/gelo-canada/data
sudo chmod -R 777 /var/www/html/gelo-canada/logs
sudo chmod -R 777 /var/www/html/gelo-canada/cache
```

---

## 🚀 **PASSO 3: CONFIGURAR BANCO DE DADOS**

```bash
# Executar migração
cd /var/www/html/gelo-canada
sudo php app/db/migrate.php

# Verificar se funcionou
sudo php -r "
require_once 'config/config.php';
require_once 'app/core/Database.php';
\$db = Database::getInstance();
echo 'Banco configurado: ' . (\$db ? 'OK' : 'ERRO') . \"\n\";
"
```

---

## 🚀 **PASSO 4: CRIAR ARQUIVO DE BLOQUEIO**

```bash
# Criar arquivo .htaccess para bloquear acesso
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
```

---

## 🚀 **PASSO 5: CONFIGURAR REDIRECIONAMENTO AUTOMÁTICO**

```bash
# Criar página de redirecionamento
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
```

---

## 🚀 **PASSO 6: CONFIGURAR FIREWALL RESTRITIVO**

```bash
# Instalar UFW
sudo apt install -y ufw

# Configurar firewall muito restritivo
sudo ufw default deny incoming
sudo ufw default allow outgoing

# Permitir apenas SSH (para manutenção) e HTTP
sudo ufw allow ssh
sudo ufw allow 80/tcp

# Bloquear HTTPS (não necessário para totem)
sudo ufw deny 443/tcp

# Ativar firewall
sudo ufw --force enable

# Verificar status
sudo ufw status
```

---

## 🚀 **PASSO 7: CONFIGURAR AUTO-INICIALIZAÇÃO DO TOTEM**

```bash
# Instalar unclutter para esconder cursor
sudo apt install -y unclutter

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
```

---

## 🚀 **PASSO 8: CONFIGURAR AUTO-LOGIN E INICIALIZAÇÃO**

```bash
# Configurar auto-login
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
```

---

## 🚀 **PASSO 9: CONFIGURAR SINCRONIZAÇÃO AUTOMÁTICA**

```bash
# Configurar cron para sincronização
(crontab -l 2>/dev/null; echo "*/5 * * * * /usr/bin/php /var/www/html/gelo-canada/scripts/sync_cron.php") | crontab -

# Verificar se foi adicionado
crontab -l
```

---

## 🚀 **PASSO 10: CONFIGURAR MONITORAMENTO SIMPLES**

```bash
# Criar script de monitoramento
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

# Tornar executável
sudo chmod +x /var/www/html/gelo-canada/scripts/monitor_totem.sh

# Adicionar ao cron (verificar a cada 5 minutos)
(crontab -l 2>/dev/null; echo "*/5 * * * * /var/www/html/gelo-canada/scripts/monitor_totem.sh") | crontab -
```

---

## 🚀 **PASSO 11: CONFIGURAR BACKUP SIMPLES**

```bash
# Criar script de backup
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

# Tornar executável
sudo chmod +x /var/www/html/gelo-canada/scripts/backup_totem.sh

# Backup diário às 3h da manhã
(crontab -l 2>/dev/null; echo "0 3 * * * /var/www/html/gelo-canada/scripts/backup_totem.sh") | crontab -
```

---

## 🚀 **PASSO 12: TESTE FINAL**

```bash
# Testar totem
curl -s http://localhost/gelo-canada/public/totem.php | head -10

# Verificar se outras páginas estão bloqueadas
curl -s -o /dev/null -w "%{http_code}" http://localhost/gelo-canada/public/login.php
# Deve retornar 302 (redirecionamento)

# Verificar logs
sudo tail -f /var/www/html/gelo-canada/logs/php_errors.log
```

---

## 🎯 **CONFIGURAÇÃO FINAL**

```bash
# Reiniciar sistema para aplicar todas as configurações
sudo reboot
```

---

## 🔒 **SEGURANÇA IMPLEMENTADA**

### ✅ **Bloqueios Ativos:**
- ❌ **Painel Admin**: Bloqueado
- ❌ **Interface Mobile**: Bloqueada  
- ❌ **Login**: Bloqueado
- ❌ **Arquivos de Config**: Bloqueados
- ❌ **Logs**: Bloqueados
- ❌ **Scripts**: Bloqueados
- ❌ **HTTPS**: Bloqueado
- ✅ **Totem**: Acesso liberado
- ✅ **APIs**: Acesso liberado (necessário para funcionamento)

### 🛡️ **Firewall:**
- ✅ **SSH**: Permitido (para manutenção)
- ✅ **HTTP**: Permitido (porta 80)
- ❌ **HTTPS**: Bloqueado (porta 443)
- ❌ **Outras portas**: Bloqueadas

---

## 🌐 **ACESSO APÓS CONFIGURAÇÃO**

### **URLs Funcionais:**
- ✅ **Totem**: `http://SEU_IP/gelo-canada/public/totem.php`
- ✅ **Redirecionamento**: `http://SEU_IP/` → Redireciona para totem

### **URLs Bloqueadas:**
- ❌ **Admin**: `http://SEU_IP/gelo-canada/public/login.php` → Redireciona para totem
- ❌ **Mobile**: `http://SEU_IP/gelo-canada/public/ui.php` → Redireciona para totem
- ❌ **Config**: `http://SEU_IP/gelo-canada/config/` → Erro 403

---

## 🔧 **COMANDOS DE MANUTENÇÃO**

```bash
# Ver status do totem
sudo systemctl status totem-gelo

# Reiniciar totem
sudo systemctl restart totem-gelo

# Ver logs do totem
sudo journalctl -u totem-gelo -f

# Ver logs de monitoramento
tail -f /var/www/html/gelo-canada/logs/totem_monitor.log

# Verificar cron jobs
crontab -l

# Verificar firewall
sudo ufw status
```

---

## 🚨 **SOLUÇÃO DE PROBLEMAS**

### **Totem não inicia:**
```bash
# Verificar se Apache está rodando
sudo systemctl status apache2

# Verificar logs
sudo tail -f /var/www/html/gelo-canada/logs/php_errors.log

# Reiniciar Apache
sudo systemctl restart apache2
```

### **Totem não abre automaticamente:**
```bash
# Verificar serviço
sudo systemctl status totem-gelo

# Reiniciar serviço
sudo systemctl restart totem-gelo

# Verificar se navegador está instalado
which chromium-browser
which firefox
```

### **Acesso bloqueado incorretamente:**
```bash
# Verificar .htaccess
cat /var/www/html/gelo-canada/.htaccess

# Testar regras
sudo apache2ctl configtest
```

---

## 🎉 **SISTEMA TOTEM CONFIGURADO!**

O Raspberry Pi agora funciona **APENAS** como totem:
- ✅ **Totem acessível** via navegador
- ✅ **Auto-inicialização** em tela cheia
- ✅ **Sincronização automática** com servidor
- ✅ **Monitoramento** e **backup** automáticos
- ❌ **Todas as outras interfaces bloqueadas**
- ❌ **Acesso administrativo bloqueado**

**🔒 Sistema seguro e restrito apenas ao totem!**
