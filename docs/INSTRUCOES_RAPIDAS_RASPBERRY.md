# 🚀 INSTRUÇÕES RÁPIDAS - RASPBERRY PI

## ⚡ **CONFIGURAÇÃO EM 1 COMANDO**

```bash
# Baixar e executar configuração automática
curl -sSL https://raw.githubusercontent.com/guilherme-granville/gelo-canada/main/scripts/configurar_raspberry_completo.sh | bash
```

**OU se preferir baixar primeiro:**

```bash
# Baixar o script
wget https://raw.githubusercontent.com/guilherme-granville/gelo-canada/main/scripts/configurar_raspberry_completo.sh

# Tornar executável
chmod +x configurar_raspberry_completo.sh

# Executar
./configurar_raspberry_completo.sh
```

---

## 🎯 **O QUE O SCRIPT FAZ AUTOMATICAMENTE:**

✅ **Atualiza o sistema**  
✅ **Instala Apache + PHP**  
✅ **Baixa o sistema completo**  
✅ **Configura banco de dados**  
✅ **Configura permissões**  
✅ **Configura firewall**  
✅ **Configura sincronização automática**  
✅ **Configura backup automático**  
✅ **Configura monitoramento**  
✅ **Configura auto-inicialização**  

---

## 🌐 **ACESSO APÓS CONFIGURAÇÃO:**

### **URLs:**
- **Admin**: `http://SEU_IP/gelo-canada/public/login.php`
- **Totem**: `http://SEU_IP/gelo-canada/public/totem.php`
- **Mobile**: `http://SEU_IP/gelo-canada/public/ui.php`

### **Login:**
- **Usuário**: `admin`
- **Senha**: `admin123`

---

## 🔧 **COMANDOS ÚTEIS:**

```bash
# Ver status dos serviços
sudo systemctl status apache2 gelo-canada

# Ver logs em tempo real
sudo tail -f /var/www/html/gelo-canada/logs/php_errors.log

# Reiniciar sistema
sudo systemctl restart apache2

# Ver uso de recursos
htop

# Ver backups
ls -la /var/www/html/gelo-canada/backups/
```

---

## 🚨 **SE ALGO DER ERRADO:**

```bash
# Verificar logs de erro
sudo journalctl -u apache2
sudo tail -f /var/www/html/gelo-canada/logs/php_errors.log

# Reconfigurar permissões
sudo chown -R www-data:www-data /var/www/html/gelo-canada
sudo chmod -R 755 /var/www/html/gelo-canada
sudo chmod -R 777 /var/www/html/gelo-canada/data
sudo chmod -R 777 /var/www/html/gelo-canada/logs

# Reexecutar migração
cd /var/www/html/gelo-canada
sudo php app/db/migrate.php
```

---

## 📱 **PARA USAR NO CELULAR:**

1. **Conecte o celular na mesma rede WiFi**
2. **Descubra o IP do Raspberry Pi:**
   ```bash
   hostname -I
   ```
3. **Acesse no celular:**
   ```
   http://IP_DO_RASPBERRY/gelo-canada/public/ui.php
   ```

---

## 🍓 **PARA USAR NO TOTEM:**

1. **Configure o Raspberry Pi para iniciar automaticamente no navegador**
2. **Acesse:**
   ```
   http://localhost/gelo-canada/public/totem.php
   ```

---

## ⏰ **CRON JOBS CONFIGURADOS:**

- **Sincronização**: A cada 5 minutos
- **Monitoramento**: A cada 10 minutos  
- **Backup**: Diário às 2h da manhã

---

## 🎉 **PRONTO!**

O sistema estará funcionando completamente após executar o script. Não é necessário fazer mais nada!

**Tempo estimado de configuração: 10-15 minutos**
