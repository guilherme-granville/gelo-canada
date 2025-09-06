# 🍓 GUIA COMPLETO - RASPBERRY PI PARA SISTEMA GELO CANADA

## 📋 ÍNDICE
1. [Preparação do Hardware](#preparação-do-hardware)
2. [Instalação do Sistema Operacional](#instalação-do-sistema-operacional)
3. [Configuração Inicial](#configuração-inicial)
4. [Instalação do Sistema](#instalação-do-sistema)
5. [Configuração do Banco de Dados](#configuração-do-banco-de-dados)
6. [Sincronização com Nuvem](#sincronização-com-nuvem)
7. [Configuração do Totem](#configuração-do-totem)
8. [Monitoramento e Manutenção](#monitoramento-e-manutenção)
9. [Solução de Problemas](#solução-de-problemas)
10. [Comandos Úteis](#comandos-úteis)

---

## 🔧 PREPARAÇÃO DO HARDWARE

### **Materiais Necessários:**
- ✅ Raspberry Pi 4 (4GB RAM recomendado)
- ✅ Cartão microSD (32GB classe 10 ou superior)
- ✅ Fonte de alimentação oficial (5V/3A)
- ✅ Cabo HDMI
- ✅ Teclado e mouse USB
- ✅ Monitor/TV com entrada HDMI
- ✅ Cabo de rede Ethernet (opcional)

### **Especificações Mínimas:**
- **RAM:** 2GB (4GB recomendado)
- **Armazenamento:** 16GB mínimo (32GB recomendado)
- **Rede:** WiFi ou Ethernet
- **Sistema:** Raspberry Pi OS Lite (64-bit)

---

## 💿 INSTALAÇÃO DO SISTEMA OPERACIONAL

### **1. Baixar o Raspberry Pi Imager:**
```bash
# No Windows/Mac/Linux, baixe em:
https://www.raspberrypi.org/downloads/
```

### **2. Configurar o Cartão SD:**
1. Abra o Raspberry Pi Imager
2. Escolha "Raspberry Pi OS Lite (64-bit)"
3. Clique em "Advanced Options" (ícone da engrenagem)
4. Configure:
   - **Enable SSH:** ✅ Sim
   - **Username:** `pi`
   - **Password:** `gelocanada123` (ou sua senha)
   - **WiFi:** Configure sua rede
   - **Locale:** `pt_BR.UTF-8`
   - **Timezone:** `America/Sao_Paulo`

### **3. Gravar a Imagem:**
1. Clique em "WRITE"
2. Aguarde a gravação (5-10 minutos)
3. Remova o cartão SD com segurança

---

## ⚙️ CONFIGURAÇÃO INICIAL

### **1. Primeira Inicialização:**
```bash
# Conecte o Raspberry Pi e acesse via SSH:
ssh pi@[IP_DO_RASPBERRY]

# Ou conecte teclado/mouse/monitor diretamente
```

### **2. Atualizar o Sistema:**
```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y git curl wget htop nano vim
```

### **3. Configurar Hostname:**
```bash
sudo hostnamectl set-hostname gelo-totem
echo "127.0.1.1 gelo-totem" | sudo tee -a /etc/hosts
```

### **4. Configurar Rede (se necessário):**
```bash
# Para IP estático, edite:
sudo nano /etc/dhcpcd.conf

# Adicione no final:
interface eth0
static ip_address=192.168.1.100/24
static routers=192.168.1.1
static domain_name_servers=8.8.8.8 8.8.4.4
```

---

## 🚀 INSTALAÇÃO DO SISTEMA

### **1. Baixar o Script de Configuração:**
```bash
# Criar diretório do projeto
sudo mkdir -p /var/www/html/gelo-canada
cd /var/www/html/gelo-canada

# Baixar o script de setup
sudo wget https://raw.githubusercontent.com/seu-usuario/gelo-canada/main/scripts/raspberry_setup.sh
sudo chmod +x raspberry_setup.sh
```

### **2. Executar o Script de Configuração:**
```bash
sudo ./raspberry_setup.sh
```

### **3. Copiar Arquivos do Projeto:**
```bash
# Via SCP (do seu computador):
scp -r C:\xampp\htdocs\gelo-canada\* pi@[IP_RASPBERRY]:/var/www/html/gelo-canada/

# Ou via Git:
cd /var/www/html/gelo-canada
sudo git clone https://github.com/seu-usuario/gelo-canada.git .
sudo chown -R www-data:www-data /var/www/html/gelo-canada
```

### **4. Configurar Permissões:**
```bash
sudo chmod -R 755 /var/www/html/gelo-canada
sudo chmod -R 777 /var/www/html/gelo-canada/data
sudo chmod -R 777 /var/www/html/gelo-canada/logs
sudo chmod -R 777 /var/www/html/gelo-canada/backups
sudo chmod -R 777 /var/www/html/gelo-canada/cache
sudo chmod -R 777 /var/www/html/gelo-canada/public/uploads
```

---

## 🗄️ CONFIGURAÇÃO DO BANCO DE DADOS

### **1. Criar Banco SQLite Local:**
```bash
cd /var/www/html/gelo-canada
sudo php app/db/migrate.php
```

### **2. Configurar Banco na Nuvem:**

#### **Opção A: MySQL/MariaDB na Nuvem**
```bash
# Editar configuração:
sudo nano config/config.php

# Configurar:
define('DB_CLOUD_HOST', 'seu-servidor-mysql.com');
define('DB_CLOUD_NAME', 'gelo_canada');
define('DB_CLOUD_USER', 'usuario_cloud');
define('DB_CLOUD_PASS', 'senha_cloud');
define('DB_CLOUD_PORT', 3306);
```

#### **Opção B: PostgreSQL na Nuvem**
```bash
# Instalar driver PostgreSQL:
sudo apt install -y php-pgsql

# Configurar:
define('DB_CLOUD_TYPE', 'pgsql');
define('DB_CLOUD_HOST', 'seu-servidor-postgres.com');
define('DB_CLOUD_NAME', 'gelo_canada');
define('DB_CLOUD_USER', 'usuario_cloud');
define('DB_CLOUD_PASS', 'senha_cloud');
define('DB_CLOUD_PORT', 5432);
```

### **3. Testar Conexão:**
```bash
# Criar script de teste:
sudo nano /var/www/html/gelo-canada/test_connection.php
```

```php
<?php
require_once 'config/config.php';
require_once 'app/core/Database.php';

try {
    $db = Database::getInstance();
    echo "✅ Conexão local OK\n";
    
    // Testar conexão com nuvem
    $cloudDb = Database::getCloudInstance();
    echo "✅ Conexão nuvem OK\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
```

```bash
sudo php test_connection.php
sudo rm test_connection.php
```

---

## ☁️ SINCRONIZAÇÃO COM NUVEM

### **1. Configurar Token de Sincronização:**
```bash
sudo nano config/config.php

# Adicionar:
define('SYNC_TOKEN', 'seu-token-secreto-aqui');
define('SYNC_URL', 'https://seu-servidor.com/api/sync');
define('SYNC_INTERVAL', 300); // 5 minutos
```

### **2. Criar Script de Sincronização:**
```bash
sudo nano /var/www/html/gelo-canada/scripts/sync_to_cloud.php
```

```php
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/SyncService.php';

$sync = new SyncService();
$result = $sync->syncToCloud();

if ($result['success']) {
    echo "✅ Sincronização OK: " . $result['message'] . "\n";
} else {
    echo "❌ Erro: " . $result['message'] . "\n";
}
?>
```

### **3. Configurar Cron para Sincronização:**
```bash
# Editar crontab:
crontab -e

# Adicionar linha:
*/5 * * * * /usr/bin/php /var/www/html/gelo-canada/scripts/sync_to_cloud.php >> /var/www/html/gelo-canada/logs/sync.log 2>&1
```

### **4. Testar Sincronização:**
```bash
sudo php /var/www/html/gelo-canada/scripts/sync_to_cloud.php
tail -f /var/www/html/gelo-canada/logs/sync.log
```

---

## 🖥️ CONFIGURAÇÃO DO TOTEM

### **1. Instalar Navegador:**
```bash
sudo apt install -y chromium-browser
```

### **2. Configurar Autostart:**
```bash
sudo nano /etc/xdg/lxsession/LXDE-pi/autostart

# Adicionar linha:
@chromium-browser --kiosk --disable-web-security --user-data-dir=/tmp/chrome-totem http://localhost/gelo-canada/public/totem.php
```

### **3. Configurar Resolução:**
```bash
sudo nano /boot/config.txt

# Adicionar/modificar:
hdmi_group=2
hdmi_mode=82
hdmi_force_hotplug=1
```

### **4. Desabilitar Screensaver:**
```bash
sudo apt install -y x11-xserver-utils
sudo nano /etc/X11/xinit/xinitrc

# Adicionar no final:
xset s off
xset -dpms
xset s noblank
```

---

## 📊 MONITORAMENTO E MANUTENÇÃO

### **1. Scripts de Monitoramento:**
```bash
# Verificar status:
sudo /var/www/html/gelo-canada/scripts/monitor.sh

# Ver logs:
tail -f /var/www/html/gelo-canada/logs/monitor.log
```

### **2. Backup Automático:**
```bash
# Executar backup manual:
sudo /var/www/html/gelo-canada/scripts/backup.sh

# Ver backups:
ls -la /var/www/html/gelo-canada/backups/
```

### **3. Manutenção Automática:**
```bash
# Executar manutenção:
sudo /var/www/html/gelo-canada/scripts/maintenance.sh

# Ver logs de manutenção:
tail -f /var/www/html/gelo-canada/logs/maintenance.log
```

### **4. Monitoramento de Recursos:**
```bash
# Instalar htop:
sudo apt install -y htop

# Monitorar em tempo real:
htop

# Ver uso de disco:
df -h

# Ver temperatura:
vcgencmd measure_temp
```

---

## 🔧 SOLUÇÃO DE PROBLEMAS

### **Problemas Comuns:**

#### **1. Apache não inicia:**
```bash
sudo systemctl status apache2
sudo journalctl -u apache2
sudo systemctl restart apache2
```

#### **2. Erro de permissões:**
```bash
sudo chown -R www-data:www-data /var/www/html/gelo-canada
sudo chmod -R 755 /var/www/html/gelo-canada
```

#### **3. Banco de dados não conecta:**
```bash
sudo sqlite3 /var/www/html/gelo-canada/data/gelo_local.db ".tables"
sudo php -r "require 'config/config.php'; echo 'Config OK';"
```

#### **4. Sincronização falha:**
```bash
# Verificar conectividade:
ping google.com
curl -I https://seu-servidor.com

# Ver logs de sincronização:
tail -f /var/www/html/gelo-canada/logs/sync.log
```

#### **5. Totem não abre:**
```bash
# Testar manualmente:
chromium-browser --kiosk http://localhost/gelo-canada/public/totem.php

# Verificar logs do Apache:
sudo tail -f /var/log/apache2/error.log
```

---

## 📱 COMANDOS ÚTEIS

### **Sistema:**
```bash
# Reiniciar sistema:
sudo reboot

# Desligar sistema:
sudo shutdown -h now

# Ver informações do sistema:
uname -a
cat /proc/cpuinfo
free -h
df -h
```

### **Serviços:**
```bash
# Status do Apache:
sudo systemctl status apache2

# Reiniciar Apache:
sudo systemctl restart apache2

# Ver logs do Apache:
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log
```

### **Projeto:**
```bash
# Ver logs do projeto:
tail -f /var/www/html/gelo-canada/logs/sync.log
tail -f /var/www/html/gelo-canada/logs/monitor.log

# Executar sincronização manual:
sudo php /var/www/html/gelo-canada/scripts/sync_to_cloud.php

# Backup manual:
sudo /var/www/html/gelo-canada/scripts/backup.sh
```

### **Rede:**
```bash
# Ver IP:
hostname -I

# Testar conectividade:
ping google.com
curl -I https://seu-servidor.com

# Ver configuração de rede:
ip addr show
```

---

## 🌐 CONFIGURAÇÃO DE REDE AVANÇADA

### **1. Configurar Firewall:**
```bash
sudo ufw enable
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 443/tcp   # HTTPS (se usar SSL)
sudo ufw status
```

### **2. Configurar SSL (Opcional):**
```bash
# Instalar Certbot:
sudo apt install -y certbot python3-certbot-apache

# Obter certificado:
sudo certbot --apache -d seu-dominio.com
```

### **3. Configurar Proxy Reverso (se necessário):**
```bash
sudo apt install -y nginx
sudo nano /etc/nginx/sites-available/gelo-canada
```

---

## 📋 CHECKLIST FINAL

### **Antes de Colocar em Produção:**

- [ ] ✅ Sistema operacional instalado e atualizado
- [ ] ✅ Apache configurado e funcionando
- [ ] ✅ PHP instalado com todas as extensões
- [ ] ✅ Banco SQLite local criado e testado
- [ ] ✅ Conexão com banco na nuvem funcionando
- [ ] ✅ Sincronização automática configurada
- [ ] ✅ Totem configurado para autostart
- [ ] ✅ Scripts de monitoramento funcionando
- [ ] ✅ Backup automático configurado
- [ ] ✅ Firewall configurado
- [ ] ✅ Hostname configurado
- [ ] ✅ Timezone configurado
- [ ] ✅ Permissões corretas
- [ ] ✅ Logs funcionando
- [ ] ✅ Teste completo do sistema

### **Testes Finais:**
```bash
# 1. Testar acesso web:
curl -I http://localhost/gelo-canada/public/totem.php

# 2. Testar sincronização:
sudo php /var/www/html/gelo-canada/scripts/sync_to_cloud.php

# 3. Testar backup:
sudo /var/www/html/gelo-canada/scripts/backup.sh

# 4. Testar monitoramento:
sudo /var/www/html/gelo-canada/scripts/monitor.sh

# 5. Reiniciar e verificar autostart:
sudo reboot
# Aguardar 2 minutos e verificar se o totem abriu automaticamente
```

---

## 📞 SUPORTE

### **Logs Importantes:**
- `/var/www/html/gelo-canada/logs/sync.log` - Sincronização
- `/var/www/html/gelo-canada/logs/monitor.log` - Monitoramento
- `/var/www/html/gelo-canada/logs/maintenance.log` - Manutenção
- `/var/log/apache2/error.log` - Erros do Apache
- `/var/log/syslog` - Logs do sistema

### **Contatos:**
- **Email:** suporte@gelocanada.com
- **Telefone:** (11) 99999-9999
- **Documentação:** `/var/www/html/gelo-canada/docs/`

---

**🎉 PARABÉNS! Seu Raspberry Pi está configurado e pronto para uso!**

*Este guia foi criado especificamente para o Sistema de Controle de Estoque da Fábrica de Gelo Canada. Mantenha-o atualizado conforme novas versões do sistema.*
