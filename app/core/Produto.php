<?php
/**
 * Classe de serviço para gerenciar produtos
 */

require_once __DIR__ . '/Database.php';

class Produto {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function criar($dados) {
        // Validar dados obrigatórios
        if (empty($dados['codigo']) || empty($dados['nome'])) {
            throw new Exception('Código e nome são obrigatórios');
        }
        
        // Verificar se código já existe
        $existe = $this->db->fetchOne("SELECT id FROM produtos WHERE codigo = ?", [$dados['codigo']]);
        if ($existe) {
            throw new Exception('Código já existe');
        }
        
        // Inserir produto
        $sql = "INSERT INTO produtos (codigo, nome, descricao, imagem_url, unidade, estoque_minimo, preco_unitario) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'] ?? '',
            $dados['imagem_url'] ?? '',
            $dados['unidade'] ?? 'kg',
            $dados['estoque_minimo'] ?? 0,
            $dados['preco_unitario'] ?? 0
        ]);
        
        $produtoId = $this->db->lastInsertId();
        
        // Criar registro de estoque
        $sql = "INSERT INTO estoque (produto_id, quantidade_atual, quantidade_minima) VALUES (?, ?, ?)";
        $this->db->execute($sql, [
            $produtoId,
            0,
            $dados['estoque_minimo'] ?? 0
        ]);
        
        return $produtoId;
    }
    
    public function atualizar($id, $dados) {
        // Verificar se produto existe
        $produto = $this->db->fetchOne("SELECT id FROM produtos WHERE id = ?", [$id]);
        if (!$produto) {
            throw new Exception('Produto não encontrado');
        }
        
        // Verificar se código já existe (se foi alterado)
        if (isset($dados['codigo'])) {
            $existe = $this->db->fetchOne("SELECT id FROM produtos WHERE codigo = ? AND id != ?", [$dados['codigo'], $id]);
            if ($existe) {
                throw new Exception('Código já existe');
            }
        }
        
        $campos = [];
        $valores = [];
        
        if (isset($dados['codigo'])) {
            $campos[] = 'codigo = ?';
            $valores[] = $dados['codigo'];
        }
        
        if (isset($dados['nome'])) {
            $campos[] = 'nome = ?';
            $valores[] = $dados['nome'];
        }
        
        if (isset($dados['descricao'])) {
            $campos[] = 'descricao = ?';
            $valores[] = $dados['descricao'];
        }
        
        if (isset($dados['imagem_url'])) {
            $campos[] = 'imagem_url = ?';
            $valores[] = $dados['imagem_url'];
        }
        
        if (isset($dados['unidade'])) {
            $campos[] = 'unidade = ?';
            $valores[] = $dados['unidade'];
        }
        
        if (isset($dados['estoque_minimo'])) {
            $campos[] = 'estoque_minimo = ?';
            $valores[] = $dados['estoque_minimo'];
        }
        
        if (isset($dados['preco_unitario'])) {
            $campos[] = 'preco_unitario = ?';
            $valores[] = $dados['preco_unitario'];
        }
        
        if (isset($dados['ativo'])) {
            $campos[] = 'ativo = ?';
            $valores[] = $dados['ativo'] ? 1 : 0;
        }
        
        if (empty($campos)) {
            throw new Exception('Nenhum campo para atualizar');
        }
        
        $valores[] = $id;
        $sql = "UPDATE produtos SET " . implode(', ', $campos) . " WHERE id = ?";
        $this->db->execute($sql, $valores);
        
        // Atualizar estoque mínimo se foi alterado
        if (isset($dados['estoque_minimo'])) {
            $sql = "UPDATE estoque SET quantidade_minima = ? WHERE produto_id = ?";
            $this->db->execute($sql, [$dados['estoque_minimo'], $id]);
        }
        
        return true;
    }
    
    public function buscarPorId($id) {
        $sql = "SELECT p.*, e.quantidade_atual, e.quantidade_minima, e.ultima_movimentacao 
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id 
                WHERE p.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function buscarPorCodigo($codigo) {
        $sql = "SELECT p.*, e.quantidade_atual, e.quantidade_minima, e.ultima_movimentacao 
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id 
                WHERE p.codigo = ? AND p.ativo = 1";
        return $this->db->fetchOne($sql, [$codigo]);
    }
    
    public function listar($filtros = []) {
        $sql = "SELECT p.*, e.quantidade_atual, e.quantidade_minima 
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id 
                WHERE 1=1";
        $valores = [];
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND p.ativo = ?";
            $valores[] = $filtros['ativo'] ? 1 : 0;
        }
        
        if (isset($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE ? OR p.codigo LIKE ? OR p.descricao LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $valores[] = $busca;
            $valores[] = $busca;
            $valores[] = $busca;
        }
        
        if (isset($filtros['estoque_baixo'])) {
            $sql .= " AND e.quantidade_atual <= e.quantidade_minima";
        }
        
        if (isset($filtros['estoque_zero'])) {
            $sql .= " AND e.quantidade_atual = 0";
        }
        
        $sql .= " ORDER BY p.nome";
        
        if (isset($filtros['limite'])) {
            $sql .= " LIMIT " . (int)$filtros['limite'];
        }
        
        return $this->db->fetchAll($sql, $valores);
    }
    
    public function listarComEstoque($filtros = []) {
        $sql = "SELECT p.*, e.quantidade_atual, e.quantidade_minima,
                       CASE 
                           WHEN e.quantidade_atual = 0 THEN 'zero'
                           WHEN e.quantidade_atual <= e.quantidade_minima THEN 'baixo'
                           ELSE 'normal'
                       END as status_estoque
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id 
                WHERE p.ativo = 1";
        $valores = [];
        
        if (isset($filtros['status'])) {
            switch ($filtros['status']) {
                case 'zero':
                    $sql .= " AND e.quantidade_atual = 0";
                    break;
                case 'baixo':
                    $sql .= " AND e.quantidade_atual > 0 AND e.quantidade_atual <= e.quantidade_minima";
                    break;
                case 'normal':
                    $sql .= " AND e.quantidade_atual > e.quantidade_minima";
                    break;
            }
        }
        
        if (isset($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE ? OR p.codigo LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $valores[] = $busca;
            $valores[] = $busca;
        }
        
        $sql .= " ORDER BY p.nome";
        
        return $this->db->fetchAll($sql, $valores);
    }
    
    public function desativar($id) {
        $sql = "UPDATE produtos SET ativo = 0 WHERE id = ?";
        $this->db->execute($sql, [$id]);
        return true;
    }
    
    public function ativar($id) {
        $sql = "UPDATE produtos SET ativo = 1 WHERE id = ?";
        $this->db->execute($sql, [$id]);
        return true;
    }
    
    public function excluir($id) {
        // Verificar se há movimentações
        $movimentacoes = $this->db->fetchOne("SELECT COUNT(*) as total FROM movimentacoes WHERE produto_id = ?", [$id]);
        if ($movimentacoes['total'] > 0) {
            throw new Exception('Não é possível excluir produto com movimentações');
        }
        
        // Excluir estoque
        $this->db->execute("DELETE FROM estoque WHERE produto_id = ?", [$id]);
        
        // Excluir produto
        $this->db->execute("DELETE FROM produtos WHERE id = ?", [$id]);
        
        return true;
    }
    
    public function contar($filtros = []) {
        $sql = "SELECT COUNT(*) as total FROM produtos WHERE 1=1";
        $valores = [];
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND ativo = ?";
            $valores[] = $filtros['ativo'] ? 1 : 0;
        }
        
        if (isset($filtros['busca'])) {
            $sql .= " AND (nome LIKE ? OR codigo LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $valores[] = $busca;
            $valores[] = $busca;
        }
        
        $resultado = $this->db->fetchOne($sql, $valores);
        return $resultado['total'] ?? 0;
    }
    
    public function getEstoqueBaixo() {
        $sql = "SELECT p.*, e.quantidade_atual, e.quantidade_minima 
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id 
                WHERE p.ativo = 1 AND e.quantidade_atual <= e.quantidade_minima 
                ORDER BY e.quantidade_atual ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getEstoqueZero() {
        $sql = "SELECT p.*, e.quantidade_atual 
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id 
                WHERE p.ativo = 1 AND e.quantidade_atual = 0 
                ORDER BY p.nome";
        return $this->db->fetchAll($sql);
    }
    
    public function uploadImagem($produtoId, $arquivo) {
        // Validar arquivo
        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            throw new Exception('Arquivo inválido');
        }
        
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        if (!in_array($extensao, ALLOWED_EXTENSIONS)) {
            throw new Exception('Extensão não permitida');
        }
        
        if ($arquivo['size'] > UPLOAD_MAX_SIZE) {
            throw new Exception('Arquivo muito grande');
        }
        
        // Gerar nome único
        $nomeArquivo = 'produto_' . $produtoId . '_' . time() . '.' . $extensao;
        $caminhoCompleto = UPLOAD_PATH . 'produtos/' . $nomeArquivo;
        
        // Mover arquivo
        if (!move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            throw new Exception('Erro ao salvar arquivo');
        }
        
        // Atualizar produto
        $url = 'uploads/produtos/' . $nomeArquivo;
        $this->atualizar($produtoId, ['imagem_url' => $url]);
        
        return $url;
    }
    
    public function getEstatisticas() {
        $sql = "SELECT 
                    COUNT(*) as total_produtos,
                    SUM(CASE WHEN ativo = 1 THEN 1 ELSE 0 END) as produtos_ativos,
                    SUM(CASE WHEN e.quantidade_atual = 0 THEN 1 ELSE 0 END) as produtos_zerados,
                    SUM(CASE WHEN e.quantidade_atual <= e.quantidade_minima AND e.quantidade_atual > 0 THEN 1 ELSE 0 END) as produtos_estoque_baixo
                FROM produtos p 
                LEFT JOIN estoque e ON p.id = e.produto_id";
        
        return $this->db->fetchOne($sql);
    }
}
