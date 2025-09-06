<?php
/**
 * Script de Migration - Sistema de Controle de Estoque
 * Cria todas as tabelas necessárias
 */

require_once __DIR__ . '/../core/Database.php';

class Migration {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function run() {
        echo "Iniciando migrations...\n";
        
        // Criar diretórios necessários
        $this->createDirectories();
        
        // Criar tabelas
        $this->createUsuariosTable();
        $this->createProdutosTable();
        $this->createMovimentacoesTable();
        $this->createEstoqueTable();
        $this->createLogsTable();
        $this->createSyncTable();
        
        // Inserir dados iniciais
        $this->insertInitialData();
        
        echo "Migrations concluídas com sucesso!\n";
    }
    
    private function createDirectories() {
        $dirs = [
            __DIR__ . '/../../data',
            __DIR__ . '/../../logs',
            __DIR__ . '/../../backups',
            __DIR__ . '/../../cache',
            __DIR__ . '/../../public/uploads',
            __DIR__ . '/../../public/uploads/produtos'
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                echo "Diretório criado: $dir\n";
            }
        }
    }
    
    private function createUsuariosTable() {
        if ($this->db->getDbType() === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS usuarios (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome VARCHAR(100) NOT NULL,
                login VARCHAR(50) UNIQUE NOT NULL,
                senha_hash VARCHAR(255) NOT NULL,
                perfil VARCHAR(20) DEFAULT 'operador' CHECK (perfil IN ('admin', 'operador')),
                ativo INTEGER DEFAULT 1 CHECK (ativo IN (0, 1)),
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS usuarios (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(100) NOT NULL,
                login VARCHAR(50) UNIQUE NOT NULL,
                senha_hash VARCHAR(255) NOT NULL,
                perfil ENUM('admin', 'operador') DEFAULT 'operador',
                ativo BOOLEAN DEFAULT 1,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        $this->db->execute($sql);
        echo "Tabela 'usuarios' criada/verificada\n";
    }
    
    private function createProdutosTable() {
        if ($this->db->getDbType() === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                codigo VARCHAR(20) UNIQUE NOT NULL,
                nome VARCHAR(100) NOT NULL,
                descricao TEXT,
                imagem_url VARCHAR(255),
                unidade VARCHAR(10) DEFAULT 'kg',
                estoque_minimo DECIMAL(10,2) DEFAULT 0,
                preco_unitario DECIMAL(10,2) DEFAULT 0,
                ativo INTEGER DEFAULT 1 CHECK (ativo IN (0, 1)),
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS produtos (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        $this->db->execute($sql);
        echo "Tabela 'produtos' criada/verificada\n";
    }
    
    private function createMovimentacoesTable() {
        if ($this->db->getDbType() === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS movimentacoes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id INTEGER NOT NULL,
                tipo VARCHAR(20) NOT NULL CHECK (tipo IN ('ENTRADA', 'SAIDA', 'AJUSTE')),
                quantidade DECIMAL(10,2) NOT NULL,
                quantidade_anterior DECIMAL(10,2) NOT NULL,
                quantidade_atual DECIMAL(10,2) NOT NULL,
                usuario_id INTEGER,
                origem VARCHAR(10) DEFAULT 'pc' CHECK (origem IN ('pi', 'cel', 'pc')),
                observacao TEXT,
                sincronizado INTEGER DEFAULT 0 CHECK (sincronizado IN (0, 1)),
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (produto_id) REFERENCES produtos(id),
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS movimentacoes (
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
                FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        $this->db->execute($sql);
        echo "Tabela 'movimentacoes' criada/verificada\n";
    }
    
    private function createEstoqueTable() {
        if ($this->db->getDbType() === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS estoque (
                produto_id INTEGER PRIMARY KEY,
                quantidade_atual DECIMAL(10,2) DEFAULT 0,
                quantidade_minima DECIMAL(10,2) DEFAULT 0,
                ultima_movimentacao DATETIME,
                atualizado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (produto_id) REFERENCES produtos(id)
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS estoque (
                produto_id INT PRIMARY KEY,
                quantidade_atual DECIMAL(10,2) DEFAULT 0,
                quantidade_minima DECIMAL(10,2) DEFAULT 0,
                ultima_movimentacao TIMESTAMP NULL,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        $this->db->execute($sql);
        echo "Tabela 'estoque' criada/verificada\n";
    }
    
    private function createLogsTable() {
        if ($this->db->getDbType() === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                usuario_id INTEGER,
                acao VARCHAR(50) NOT NULL,
                tabela VARCHAR(50),
                registro_id INTEGER,
                detalhes TEXT,
                ip VARCHAR(45),
                user_agent TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT,
                acao VARCHAR(50) NOT NULL,
                tabela VARCHAR(50),
                registro_id INT,
                detalhes TEXT,
                ip VARCHAR(45),
                user_agent TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        $this->db->execute($sql);
        echo "Tabela 'logs' criada/verificada\n";
    }
    
    private function createSyncTable() {
        if ($this->db->getDbType() === 'sqlite') {
            $sql = "CREATE TABLE IF NOT EXISTS sync_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                tabela VARCHAR(50) NOT NULL,
                registro_id INTEGER NOT NULL,
                acao VARCHAR(20) NOT NULL CHECK (acao IN ('INSERT', 'UPDATE', 'DELETE')),
                dados TEXT,
                processado INTEGER DEFAULT 0 CHECK (processado IN (0, 1)),
                tentativas INTEGER DEFAULT 0,
                erro TEXT,
                criado_em DATETIME DEFAULT CURRENT_TIMESTAMP,
                processado_em DATETIME
            )";
        } else {
            $sql = "CREATE TABLE IF NOT EXISTS sync_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                tabela VARCHAR(50) NOT NULL,
                registro_id INT NOT NULL,
                acao ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
                dados TEXT,
                processado BOOLEAN DEFAULT 0,
                tentativas INT DEFAULT 0,
                erro TEXT,
                criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                processado_em TIMESTAMP NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        }
        
        $this->db->execute($sql);
        echo "Tabela 'sync_log' criada/verificada\n";
    }
    
    private function insertInitialData() {
        // Verificar se já existe admin
        $admin = $this->db->fetchOne("SELECT id FROM usuarios WHERE login = 'admin'");
        
        if (!$admin) {
            // Inserir usuário admin
            $senhaHash = password_hash('admin123', PASSWORD_DEFAULT);
            $sql = "INSERT INTO usuarios (nome, login, senha_hash, perfil) VALUES (?, ?, ?, ?)";
            $this->db->execute($sql, ['Administrador', 'admin', $senhaHash, 'admin']);
            echo "Usuário admin criado (login: admin, senha: admin123)\n";
        }
        
        // Inserir produtos de exemplo
        $produtos = [
            ['GELO001', 'Gelo em Cubos 1kg', 'Gelo em cubos para bebidas', 'kg', 10.00, 2.50],
            ['GELO002', 'Gelo Triturado 1kg', 'Gelo triturado para sucos', 'kg', 15.00, 2.00],
            ['GELO003', 'Gelo Especial 1kg', 'Gelo especial para eventos', 'kg', 5.00, 5.00],
            ['GELO004', 'Gelo Seco 1kg', 'Gelo seco para transporte', 'kg', 20.00, 8.00]
        ];
        
        foreach ($produtos as $produto) {
            $existe = $this->db->fetchOne("SELECT id FROM produtos WHERE codigo = ?", [$produto[0]]);
            if (!$existe) {
                $sql = "INSERT INTO produtos (codigo, nome, descricao, unidade, estoque_minimo, preco_unitario) VALUES (?, ?, ?, ?, ?, ?)";
                $this->db->execute($sql, $produto);
                
                // Inserir estoque inicial
                $produtoId = $this->db->lastInsertId();
                $sql = "INSERT INTO estoque (produto_id, quantidade_atual, quantidade_minima) VALUES (?, ?, ?)";
                $this->db->execute($sql, [$produtoId, 0, $produto[4]]);
            }
        }
        
        echo "Dados iniciais inseridos\n";
    }
}

// Executar migrations
try {
    $migration = new Migration();
    $migration->run();
} catch (Exception $e) {
    echo "Erro durante migration: " . $e->getMessage() . "\n";
    exit(1);
}
