# 🍓 INSTRUÇÕES RÁPIDAS - TOTEM RASPBERRY PI

## ⚡ **CONFIGURAÇÃO EM 1 COMANDO**

```bash
# Baixar e executar configuração automática do totem
curl -sSL https://raw.githubusercontent.com/guilherme-granville/gelo-canada/main/scripts/configurar_totem_raspberry.sh | bash
```

**OU se preferir baixar primeiro:**

```bash
# Baixar o script
wget https://raw.githubusercontent.com/guilherme-granville/gelo-canada/main/scripts/configurar_totem_raspberry.sh

# Tornar executável
chmod +x configurar_totem_raspberry.sh

# Executar
./configurar_totem_raspberry.sh
```

---

## 🎯 **O QUE O SCRIPT FAZ AUTOMATICAMENTE:**

✅ **Instala Apache + PHP**  
✅ **Baixa o sistema completo**  
✅ **Configura banco de dados**  
✅ **BLOQUEIA acesso ao painel admin**  
✅ **BLOQUEIA acesso à interface mobile**  
✅ **BLOQUEIA acesso ao login**  
✅ **Permite APENAS acesso ao totem**  
✅ **Configura auto-inicialização em tela cheia**  
✅ **Configura sincronização automática**  
✅ **Configura monitoramento e backup**  
✅ **Configura firewall restritivo**  

---

## 🔒 **SEGURANÇA IMPLEMENTADA:**

### ❌ **BLOQUEADO:**
- Painel Administrativo
- Interface Mobile
- Sistema de Login
- Arquivos de Configuração
- Logs do Sistema
- Scripts
- Documentação
- Acesso HTTPS

### ✅ **LIBERADO:**
- **Totem** (única interface acessível)
- APIs necessárias para funcionamento
- Uploads de imagens

---

## 🌐 **ACESSO APÓS CONFIGURAÇÃO:**

### **URLs Funcionais:**
- ✅ **Totem**: `http://SEU_IP/gelo-canada/public/totem.php`
- ✅ **Redirecionamento**: `http://SEU_IP/` → Redireciona para totem

### **URLs Bloqueadas:**
- ❌ **Admin**: `http://SEU_IP/gelo-canada/public/login.php` → Redireciona para totem
- ❌ **Mobile**: `http://SEU_IP/gelo-canada/public/ui.php` → Redireciona para totem
- ❌ **Config**: `http://SEU_IP/gelo-canada/config/` → Erro 403

---

## 🖥️ **FUNCIONAMENTO DO TOTEM:**

### **Auto-Inicialização:**
- ✅ **Inicia automaticamente** ao ligar o Raspberry Pi
- ✅ **Abre em tela cheia** (modo kiosk)
- ✅ **Esconde cursor** automaticamente
- ✅ **Tela não desliga** automaticamente
- ✅ **Reinicia automaticamente** se houver erro

### **Navegador:**
- ✅ **Chromium** (preferencial)
- ✅ **Firefox** (fallback se Chromium não estiver disponível)

---

## 🔧 **COMANDOS DE MANUTENÇÃO:**

```bash
# Ver status do totem
sudo systemctl status totem-gelo

# Reiniciar totem
sudo systemctl restart totem-gelo

# Ver logs do totem
sudo journalctl -u totem-gelo -f

# Ver logs de monitoramento
tail -f /var/www/html/gelo-canada/logs/totem_monitor.log

# Verificar se totem está acessível
curl -s http://localhost/gelo-canada/public/totem.php | head -5

# Verificar firewall
sudo ufw status

# Verificar cron jobs
crontab -l
```

---

## 🚨 **SOLUÇÃO DE PROBLEMAS:**

### **Totem não inicia automaticamente:**
```bash
# Verificar serviço
sudo systemctl status totem-gelo

# Reiniciar serviço
sudo systemctl restart totem-gelo

# Verificar se navegador está instalado
which chromium-browser
which firefox

# Instalar navegador se necessário
sudo apt install -y chromium-browser
```

### **Totem não abre em tela cheia:**
```bash
# Verificar script de inicialização
cat /home/pi/start_totem.sh

# Testar manualmente
/home/pi/start_totem.sh
```

### **Acesso bloqueado incorretamente:**
```bash
# Verificar .htaccess
cat /var/www/html/gelo-canada/.htaccess

# Testar regras do Apache
sudo apache2ctl configtest

# Reiniciar Apache
sudo systemctl restart apache2
```

### **Sincronização não funciona:**
```bash
# Verificar logs de sincronização
tail -f /var/www/html/gelo-canada/logs/sync.log

# Executar sincronização manual
php /var/www/html/gelo-canada/scripts/sync_cron.php

# Verificar cron
crontab -l
```

---

## 📱 **PARA USAR NO CELULAR (BLOQUEADO):**

❌ **Acesso via celular está BLOQUEADO por segurança**

Se precisar acessar o sistema via celular, use o **servidor principal** (não o Raspberry Pi).

---

## 🍓 **PARA USAR NO TOTEM:**

1. **Configure o Raspberry Pi** para iniciar automaticamente
2. **Conecte monitor/touchscreen**
3. **O totem abrirá automaticamente** em tela cheia
4. **Funcionamento totalmente autônomo**

---

## ⏰ **CRON JOBS CONFIGURADOS:**

- **Sincronização**: A cada 5 minutos
- **Monitoramento**: A cada 5 minutos  
- **Backup**: Diário às 3h da manhã

---

## 🔐 **SEGURANÇA ADICIONAL:**

### **Firewall Configurado:**
- ✅ **SSH**: Permitido (para manutenção)
- ✅ **HTTP**: Permitido (porta 80)
- ❌ **HTTPS**: Bloqueado (porta 443)
- ❌ **Outras portas**: Bloqueadas

### **Bloqueios de Arquivo:**
- ❌ **config.php**: Bloqueado
- ❌ ***.log**: Bloqueados
- ❌ **Diretórios sensíveis**: Bloqueados

---

## 🎉 **PRONTO!**

O Raspberry Pi agora funciona **APENAS** como totem:
- ✅ **Totem acessível** via navegador
- ✅ **Auto-inicialização** em tela cheia
- ✅ **Sincronização automática** com servidor
- ✅ **Monitoramento** e **backup** automáticos
- ❌ **Todas as outras interfaces bloqueadas**
- ❌ **Acesso administrativo bloqueado**

**🔒 Sistema seguro e restrito apenas ao totem!**

**Tempo estimado de configuração: 8-12 minutos**
