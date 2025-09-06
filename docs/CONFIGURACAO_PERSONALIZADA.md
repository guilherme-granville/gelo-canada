# 🍓 CONFIGURAÇÃO PERSONALIZADA - GUILHERME GRANVILLE

## 📋 INFORMAÇÕES DO PROJETO

- **Nome:** Sistema de Controle de Estoque - Fábrica de Gelo Canada
- **Desenvolvedor:** Guilherme Granville
- **GitHub:** https://github.com/guilherme-granville/gelo-canada
- **Versão:** 1.0.0

## 🚀 COMANDOS DE INSTALAÇÃO RÁPIDA

### **No Raspberry Pi:**

```bash
# 1. Baixar e executar script de instalação:
cd /var/www/html
sudo wget https://raw.githubusercontent.com/guilherme-granville/gelo-canada/main/scripts/install_raspberry.sh
sudo chmod +x install_raspberry.sh
sudo ./install_raspberry.sh

# 2. Clonar o projeto:
sudo git clone https://github.com/guilherme-granville/gelo-canada.git
sudo chown -R www-data:www-data gelo-canada
sudo chmod -R 755 gelo-canada

# 3. Configurar banco:
cd gelo-canada
sudo php app/db/migrate.php

# 4. Configurar sincronização:
sudo nano config/config.php
```

### **Do seu computador (Windows):**

```bash
# Copiar arquivos via SCP:
scp -r C:\xampp\htdocs\gelo-canada\* pi@192.168.3.10:/var/www/html/gelo-canada/
```

## 🔧 CONFIGURAÇÕES ESPECÍFICAS

### **Banco de Dados na Nuvem:**
```php
// config/config.php
define('DB_CLOUD_HOST', 'seu-servidor-mysql.com');
define('DB_CLOUD_NAME', 'gelo_canada');
define('DB_CLOUD_USER', 'gelo_user');
define('DB_CLOUD_PASS', 'sua_senha_segura');
define('DB_CLOUD_PORT', 3306);
```

### **Sincronização:**
```php
define('SYNC_TOKEN', 'token-secreto-guilherme-granville');
define('SYNC_URL', 'https://seu-servidor.com/api/sync');
define('SYNC_INTERVAL', 300); // 5 minutos
```

## 📱 ACESSOS

- **Totem:** http://192.168.3.10/gelo-canada/public/totem.php
- **Admin:** http://192.168.3.10/gelo-canada/public/admin.php
- **UI Mobile:** http://192.168.3.10/gelo-canada/public/ui.php

## 📞 CONTATO

- **GitHub:** @guilherme-granville
- **Projeto:** https://github.com/guilherme-granville/gelo-canada

## 📚 DOCUMENTAÇÃO

- **Guia Completo:** docs/GUIA_RASPBERRY_PI.md
- **Configuração Nuvem:** docs/CONFIGURACAO_NUVEM.md
- **Documentação Técnica:** docs/TECNICO.md
