# Sistema de Controle de Estoque - Fábrica de Gelo

Sistema completo de controle de estoque para fábrica de gelo com suporte a Totem (Raspberry Pi), painel administrativo e aplicação mobile.

## 🎯 Características

- **Totem (Raspberry Pi)**: Interface touch/teclado para registro rápido
- **Painel Admin**: Gestão completa via PC/celular
- **UI Entregador**: Interface simples para celulares
- **Sincronização**: Offline-first com sync automático
- **Multi-dispositivo**: Responsivo e adaptável

## 🏗️ Arquitetura

```
/app
  /core         # Serviços PHP (movimentacao, produto, usuario, sync)
  /db           # Migrations e seeds
  /public       # Arquivos públicos (index.php, assets)
  /ui           # Interface Totem/UI simples
  /admin        # Painel administrativo
  /api          # API de sincronização
/config         # Configurações
/logs           # Logs do sistema
/backups        # Backups automáticos
/docs           # Documentação
```

## 🚀 Instalação

### Raspberry Pi (Totem)

1. Instalar Apache + PHP + SQLite:
```bash
sudo apt update
sudo apt install apache2 php sqlite3 php-sqlite3
```

2. Configurar projeto:
```bash
cd /var/www/html
git clone https://github.com/guilherme-granville/gelo-canada.git
chmod -R 755 gelo-canada
```

3. Configurar autostart:
```bash
# Editar /etc/xdg/lxsession/LXDE-pi/autostart
@chromium-browser --kiosk http://localhost/gelo-canada/public/totem.php
```

### Servidor (Nuvem)

1. Instalar LAMP:
```bash
sudo apt install apache2 mysql-server php php-mysql
```

2. Configurar banco:
```bash
mysql -u root -p
CREATE DATABASE gelo_canada;
CREATE USER 'gelo_user'@'localhost' IDENTIFIED BY 'senha_segura';
GRANT ALL PRIVILEGES ON gelo_canada.* TO 'gelo_user'@'localhost';
```

3. Executar migrations:
```bash
php /path/to/gelo-canada/app/db/migrate.php
```

## 📱 Uso

### Totem
- Acesso: `http://raspberry-ip/gelo-canada/public/totem.php`
- Teclas: 1=Entrada, 2=Saída, Enter=Confirmar, 0=OK, .=Voltar

### Admin
- Acesso: `http://servidor/gelo-canada/public/admin.php`
- Login: admin / admin123 (alterar após primeiro acesso)

### API Sync
- Endpoint: `http://servidor/gelo-canada/app/api/sync.php`
- Token: Configurado em `/config/config.php`

## 🔧 Configuração

Editar `/config/config.php`:
- Credenciais do banco
- Token de sincronização
- Configurações de ambiente

## 📊 Funcionalidades

- ✅ Registro rápido entrada/saída
- ✅ Sincronização offline/online
- ✅ Relatórios completos
- ✅ Cadastro de produtos/usuários
- ✅ Dashboard com gráficos
- ✅ Exportação CSV/Excel/PDF
- ✅ Backup automático
- ✅ Interface responsiva
- ✅ Segurança com login/sessões

## 🛠️ Tecnologias

- **Backend**: PHP 8
- **Frontend**: HTML5, CSS3, JavaScript
- **Banco Local**: SQLite
- **Banco Nuvem**: MySQL/MariaDB
- **API**: JSON REST
- **Servidor**: Apache2

## 📞 Suporte

Para dúvidas ou problemas, consulte a documentação em `/docs/` ou entre em contato com o administrador do sistema.
