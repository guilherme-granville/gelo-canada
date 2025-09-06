# Documentação Técnica - Sistema de Controle de Estoque
**Desenvolvedor:** Guilherme Granville  
**GitHub:** https://github.com/guilherme-granville/gelo-canada  
**Versão:** 1.0.0

## 🏗️ Arquitetura do Sistema

### Visão Geral
O sistema é baseado em uma arquitetura híbrida que suporta operação offline (Raspberry Pi) e online (servidor central), com sincronização automática entre os ambientes.

### Componentes Principais

#### 1. Backend (PHP)
- **Framework**: PHP 8+ puro (sem framework externo)
- **Padrão**: MVC simplificado
- **Banco de Dados**: MySQL/MariaDB (servidor) + SQLite (Raspberry Pi)
- **API**: REST JSON para sincronização

#### 2. Frontend
- **HTML5 + CSS3 + JavaScript vanilla**
- **Responsivo**: Mobile-first design
- **Touch-friendly**: Interface otimizada para touchscreen
- **PWA-ready**: Preparado para Progressive Web App

#### 3. Banco de Dados
- **Servidor**: MySQL/MariaDB com InnoDB
- **Raspberry Pi**: SQLite com WAL mode
- **Sincronização**: JSON via API REST

## 📁 Estrutura de Diretórios

```
gelo-canada/
├── app/
│   ├── core/           # Classes principais
│   │   ├── Database.php
│   │   ├── Usuario.php
│   │   ├── Produto.php
│   │   ├── Movimentacao.php
│   │   └── SyncService.php
│   ├── db/            # Migrations e seeds
│   │   └── migrate.php
│   └── api/           # API de sincronização
│       └── sync.php
├── config/
│   └── config.php     # Configurações centralizadas
├── public/            # Arquivos públicos
│   ├── admin.php      # Painel administrativo
│   ├── login.php      # Página de login
│   ├── totem.php      # Interface do Totem
│   ├── ui.php         # Interface mobile
│   ├── uploads/       # Uploads de imagens
│   └── .htaccess      # Configurações Apache
├── scripts/
│   └── sync_cron.php  # Script de sincronização
├── logs/              # Logs do sistema
├── backups/           # Backups automáticos
├── data/              # Dados SQLite (Raspberry Pi)
└── docs/              # Documentação
```

## 🗄️ Modelo de Dados

### Tabelas Principais

#### usuarios
```sql
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    login VARCHAR(50) UNIQUE NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    perfil ENUM('admin', 'operador') DEFAULT 'operador',
    ativo BOOLEAN DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### produtos
```sql
CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    codigo VARCHAR(20) UNIQUE NOT NULL,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT,
    imagem_url VARCHAR(255),
    unidade VARCHAR(10) DEFAULT 'kg',
    estoque_minimo DECIMAL(10,2) DEFAULT 0,
    preco_unitario DECIMAL(10,2) DEFAULT 0,
    ativo BOOLEAN DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### movimentacoes
```sql
CREATE TABLE movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produto_id INT NOT NULL,
    tipo ENUM('ENTRADA', 'SAIDA', 'AJUSTE') NOT NULL,
    quantidade DECIMAL(10,2) NOT NULL,
    quantidade_anterior DECIMAL(10,2) NOT NULL,
    quantidade_atual DECIMAL(10,2) NOT NULL,
    usuario_id INT,
    origem ENUM('pi', 'cel', 'pc') DEFAULT 'pc',
    observacao TEXT,
    sincronizado BOOLEAN DEFAULT 0,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);
```

#### estoque
```sql
CREATE TABLE estoque (
    produto_id INT PRIMARY KEY,
    quantidade_atual DECIMAL(10,2) DEFAULT 0,
    quantidade_minima DECIMAL(10,2) DEFAULT 0,
    ultima_movimentacao TIMESTAMP NULL,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
);
```

## 🔄 Sistema de Sincronização

### Fluxo de Sincronização

1. **Raspberry Pi (Offline)**
   - Movimentações são salvas localmente
   - Marcadas como `sincronizado = 0`
   - Script cron executa a cada 5 minutos

2. **Sincronização**
   - Envia movimentações pendentes para servidor
   - Baixa produtos atualizados do servidor
   - Confirma sincronização

3. **Servidor (Online)**
   - Recebe dados via API REST
   - Valida e processa movimentações
   - Retorna confirmação

### API de Sincronização

#### Endpoints

**GET /api/sync.php?acao=status**
```json
{
    "success": true,
    "data": {
        "timestamp": "2024-01-01 12:00:00",
        "servidor": "online",
        "banco": "conectado",
        "produtos_ativos": 10,
        "movimentacoes_hoje": 25
    }
}
```

**POST /api/sync.php?acao=movimentacao**
```json
{
    "produto_id": 1,
    "tipo": "ENTRADA",
    "quantidade": 10.5,
    "usuario_id": 1,
    "observacao": "Entrada manual",
    "criado_em": "2024-01-01 12:00:00"
}
```

## 🔐 Segurança

### Autenticação
- **Sessões PHP** com expiração configurável
- **Senhas** hasheadas com `password_hash()`
- **Tokens** para API de sincronização

### Autorização
- **Perfis**: admin, operador
- **Controle de acesso** baseado em perfil
- **Logs** de todas as ações

### Proteções
- **SQL Injection**: Prepared statements
- **XSS**: Escape de saída
- **CSRF**: Tokens de sessão
- **File Upload**: Validação de tipos e tamanhos

## 📱 Interfaces

### Totem (Raspberry Pi)
- **Acesso**: Sem autenticação
- **Interface**: Touchscreen otimizada
- **Teclado**: Suporte a teclado numérico
- **Auto-logout**: 5 minutos de inatividade

### Mobile (Entregadores)
- **Acesso**: Login obrigatório
- **Interface**: Responsiva para celulares
- **Funcionalidades**: Entrada/saída + histórico

### Admin (PC/Desktop)
- **Acesso**: Login admin obrigatório
- **Interface**: Dashboard completo
- **Funcionalidades**: Gestão total do sistema

## ⚙️ Configurações

### Arquivo config.php
```php
// Ambiente
define('ENVIRONMENT', 'production');
define('DEBUG', false);

// Banco de dados
define('DB_TYPE', 'mysql'); // ou 'sqlite'
define('DB_HOST', 'localhost');
define('DB_NAME', 'gelo_canada');

// API
define('SYNC_TOKEN', 'token_secreto');
define('API_TIMEOUT', 30);

// Sessão
define('SESSION_LIFETIME', 3600);
```

### Variáveis de Ambiente
- **ENVIRONMENT**: development/production
- **DB_TYPE**: mysql/sqlite
- **SYNC_TOKEN**: Token para API
- **BASE_URL**: URL base do sistema

## 🚀 Performance

### Otimizações
- **Cache**: Sistema de cache simples
- **Indexes**: Índices nas colunas principais
- **Compressão**: GZIP para arquivos estáticos
- **CDN**: Font Awesome via CDN

### Monitoramento
- **Logs**: Todas as ações são logadas
- **Erros**: Log de erros PHP
- **Sincronização**: Log de sincronização

## 🔧 Manutenção

### Backups
- **Automático**: Diário via cron
- **Retenção**: 30 dias
- **Localização**: /backups/

### Logs
- **Aplicação**: /logs/app.log
- **Erros**: /logs/php_errors.log
- **Sincronização**: /logs/sync.log

### Limpeza
- **Logs**: Limpeza automática (7 dias)
- **Cache**: Limpeza automática (1 hora)
- **Sessões**: Limpeza automática

## 🐛 Troubleshooting

### Problemas Comuns

#### Sincronização não funciona
1. Verificar conectividade de rede
2. Verificar token de sincronização
3. Verificar logs em /logs/sync.log
4. Verificar cron job

#### Erro de banco de dados
1. Verificar credenciais em config.php
2. Verificar permissões de diretório
3. Verificar logs em /logs/php_errors.log

#### Interface não carrega
1. Verificar permissões de arquivos
2. Verificar configuração do Apache
3. Verificar logs do servidor web

### Comandos Úteis

#### Verificar status da sincronização
```bash
tail -f /var/www/html/gelo-canada/logs/sync.log
```

#### Verificar logs de erro
```bash
tail -f /var/www/html/gelo-canada/logs/php_errors.log
```

#### Testar conexão com banco
```bash
php -r "require 'config/config.php'; require 'app/core/Database.php'; \$db = Database::getInstance(); echo 'Conexão OK';"
```

## 📊 Métricas

### Indicadores de Performance
- **Tempo de resposta**: < 2s para páginas
- **Sincronização**: < 30s para 100 registros
- **Uptime**: 99.9% (com redundância)

### Capacidade
- **Produtos**: Ilimitado
- **Movimentações**: 1M+ registros
- **Usuários**: 100+ simultâneos
- **Upload**: 5MB por arquivo

## 🔄 Atualizações

### Processo de Atualização
1. **Backup** do sistema atual
2. **Download** da nova versão
3. **Migração** do banco de dados
4. **Teste** em ambiente de desenvolvimento
5. **Deploy** em produção

### Versionamento
- **Semântico**: MAJOR.MINOR.PATCH
- **Changelog**: Documentado em cada versão
- **Compatibilidade**: Backward compatible

## 📞 Suporte

### Contatos
- **Desenvolvedor**: [Seu Nome]
- **Email**: [seu-email@exemplo.com]
- **Documentação**: /docs/

### Recursos
- **README.md**: Guia de instalação
- **TECNICO.md**: Esta documentação
- **Logs**: Para diagnóstico de problemas
- **Backups**: Para recuperação de dados
