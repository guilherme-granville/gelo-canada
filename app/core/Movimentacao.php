<?php
/**
 * Classe de serviço para gerenciar movimentações de estoque
 */

require_once __DIR__ . '/Database.php';

class Movimentacao {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function registrar($dados) {
        // Validar dados obrigatórios
        if (empty($dados['produto_id']) || empty($dados['tipo']) || !isset($dados['quantidade'])) {
            throw new Exception('Produto, tipo e quantidade são obrigatórios');
        }
        
        if ($dados['quantidade'] <= 0) {
            throw new Exception('Quantidade deve ser maior que zero');
        }
        
        // Verificar se produto existe e está ativo
        $produto = $this->db->fetchOne("SELECT id, nome FROM produtos WHERE id = ? AND ativo = 1", [$dados['produto_id']]);
        if (!$produto) {
            throw new Exception('Produto não encontrado ou inativo');
        }
        
        // Obter estoque atual
        $estoque = $this->db->fetchOne("SELECT quantidade_atual FROM estoque WHERE produto_id = ?", [$dados['produto_id']]);
        if (!$estoque) {
            throw new Exception('Estoque não encontrado para o produto');
        }
        
        $quantidadeAnterior = $estoque['quantidade_atual'];
        $quantidade = $dados['quantidade'];
        
        // Calcular nova quantidade
        switch ($dados['tipo']) {
            case 'ENTRADA':
                $quantidadeAtual = $quantidadeAnterior + $quantidade;
                break;
            case 'SAIDA':
                if ($quantidadeAnterior < $quantidade) {
                    throw new Exception('Estoque insuficiente. Disponível: ' . $quantidadeAnterior);
                }
                $quantidadeAtual = $quantidadeAnterior - $quantidade;
                break;
            case 'AJUSTE':
                $quantidadeAtual = $quantidade;
                break;
            default:
                throw new Exception('Tipo de movimentação inválido');
        }
        
        // Iniciar transação
        $this->db->beginTransaction();
        
        try {
            // Inserir movimentação
            $sql = "INSERT INTO movimentacoes (produto_id, tipo, quantidade, quantidade_anterior, quantidade_atual, usuario_id, origem, observacao) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->db->execute($sql, [
                $dados['produto_id'],
                $dados['tipo'],
                $quantidade,
                $quantidadeAnterior,
                $quantidadeAtual,
                $dados['usuario_id'] ?? null,
                $dados['origem'] ?? 'pc',
                $dados['observacao'] ?? ''
            ]);
            
            $movimentacaoId = $this->db->lastInsertId();
            
            // Atualizar estoque
            $sql = "UPDATE estoque SET quantidade_atual = ?, ultima_movimentacao = NOW() WHERE produto_id = ?";
            $this->db->execute($sql, [$quantidadeAtual, $dados['produto_id']]);
            
            // Registrar log
            $this->logAcao($dados['usuario_id'] ?? null, 'MOVIMENTACAO', 'movimentacoes', $movimentacaoId, 
                "Movimentação: {$dados['tipo']} - {$quantidade} - Produto: {$produto['nome']}");
            
            // Se for Raspberry Pi, marcar para sincronização
            if (($dados['origem'] ?? 'pc') === 'pi') {
                $this->marcarParaSincronizacao('movimentacoes', $movimentacaoId, 'INSERT');
            }
            
            $this->db->commit();
            
            return [
                'id' => $movimentacaoId,
                'quantidade_anterior' => $quantidadeAnterior,
                'quantidade_atual' => $quantidadeAtual,
                'produto' => $produto
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    public function buscarPorId($id) {
        $sql = "SELECT m.*, p.nome as produto_nome, p.codigo as produto_codigo, u.nome as usuario_nome 
                FROM movimentacoes m 
                LEFT JOIN produtos p ON m.produto_id = p.id 
                LEFT JOIN usuarios u ON m.usuario_id = u.id 
                WHERE m.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function listar($filtros = []) {
        $sql = "SELECT m.*, p.nome as produto_nome, p.codigo as produto_codigo, u.nome as usuario_nome 
                FROM movimentacoes m 
                LEFT JOIN produtos p ON m.produto_id = p.id 
                LEFT JOIN usuarios u ON m.usuario_id = u.id 
                WHERE 1=1";
        $valores = [];
        
        if (isset($filtros['produto_id'])) {
            $sql .= " AND m.produto_id = ?";
            $valores[] = $filtros['produto_id'];
        }
        
        if (isset($filtros['tipo'])) {
            $sql .= " AND m.tipo = ?";
            $valores[] = $filtros['tipo'];
        }
        
        if (isset($filtros['usuario_id'])) {
            $sql .= " AND m.usuario_id = ?";
            $valores[] = $filtros['usuario_id'];
        }
        
        if (isset($filtros['origem'])) {
            $sql .= " AND m.origem = ?";
            $valores[] = $filtros['origem'];
        }
        
        if (isset($filtros['data_inicio'])) {
            $sql .= " AND DATE(m.criado_em) >= ?";
            $valores[] = $filtros['data_inicio'];
        }
        
        if (isset($filtros['data_fim'])) {
            $sql .= " AND DATE(m.criado_em) <= ?";
            $valores[] = $filtros['data_fim'];
        }
        
        if (isset($filtros['busca'])) {
            $sql .= " AND (p.nome LIKE ? OR p.codigo LIKE ? OR u.nome LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $valores[] = $busca;
            $valores[] = $busca;
            $valores[] = $busca;
        }
        
        // Excluir ajustes dos relatórios de vendas se solicitado
        if (isset($filtros['excluir_ajustes']) && $filtros['excluir_ajustes']) {
            $sql .= " AND m.tipo != 'AJUSTE'";
        }
        
        $sql .= " ORDER BY m.criado_em DESC";
        
        if (isset($filtros['limite'])) {
            $sql .= " LIMIT " . (int)$filtros['limite'];
        }
        
        return $this->db->fetchAll($sql, $valores);
    }
    
    public function getUltimasMovimentacoes($limite = 10) {
        $sql = "SELECT m.*, p.nome as produto_nome, p.codigo as produto_codigo, u.nome as usuario_nome 
                FROM movimentacoes m 
                LEFT JOIN produtos p ON m.produto_id = p.id 
                LEFT JOIN usuarios u ON m.usuario_id = u.id 
                ORDER BY m.criado_em DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limite]);
    }
    
    public function getMovimentacoesPorPeriodo($dataInicio, $dataFim, $filtros = []) {
        $sql = "SELECT m.*, p.nome as produto_nome, p.codigo as produto_codigo, u.nome as usuario_nome 
                FROM movimentacoes m 
                LEFT JOIN produtos p ON m.produto_id = p.id 
                LEFT JOIN usuarios u ON m.usuario_id = u.id 
                WHERE DATE(m.criado_em) BETWEEN ? AND ?";
        $valores = [$dataInicio, $dataFim];
        
        if (isset($filtros['tipo'])) {
            $sql .= " AND m.tipo = ?";
            $valores[] = $filtros['tipo'];
        }
        
        if (isset($filtros['produto_id'])) {
            $sql .= " AND m.produto_id = ?";
            $valores[] = $filtros['produto_id'];
        }
        
        if (isset($filtros['excluir_ajustes']) && $filtros['excluir_ajustes']) {
            $sql .= " AND m.tipo != 'AJUSTE'";
        }
        
        $sql .= " ORDER BY m.criado_em DESC";
        
        return $this->db->fetchAll($sql, $valores);
    }
    
    public function getResumoPorPeriodo($dataInicio, $dataFim) {
        $sql = "SELECT 
                    p.codigo,
                    p.nome as produto_nome,
                    SUM(CASE WHEN m.tipo = 'ENTRADA' THEN m.quantidade ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN m.tipo = 'SAIDA' THEN m.quantidade ELSE 0 END) as total_saidas,
                    SUM(CASE WHEN m.tipo = 'AJUSTE' THEN m.quantidade ELSE 0 END) as total_ajustes,
                    e.quantidade_atual as estoque_atual
                FROM produtos p 
                LEFT JOIN movimentacoes m ON p.id = m.produto_id 
                    AND DATE(m.criado_em) BETWEEN ? AND ?
                LEFT JOIN estoque e ON p.id = e.produto_id 
                WHERE p.ativo = 1 
                GROUP BY p.id, p.codigo, p.nome, e.quantidade_atual 
                ORDER BY p.nome";
        
        return $this->db->fetchAll($sql, [$dataInicio, $dataFim]);
    }
    
    public function getEstatisticas($dataInicio = null, $dataFim = null) {
        $sql = "SELECT 
                    COUNT(*) as total_movimentacoes,
                    SUM(CASE WHEN tipo = 'ENTRADA' THEN 1 ELSE 0 END) as total_entradas,
                    SUM(CASE WHEN tipo = 'SAIDA' THEN 1 ELSE 0 END) as total_saidas,
                    SUM(CASE WHEN tipo = 'AJUSTE' THEN 1 ELSE 0 END) as total_ajustes,
                    SUM(CASE WHEN tipo = 'ENTRADA' THEN quantidade ELSE 0 END) as quantidade_entradas,
                    SUM(CASE WHEN tipo = 'SAIDA' THEN quantidade ELSE 0 END) as quantidade_saidas
                FROM movimentacoes 
                WHERE 1=1";
        $valores = [];
        
        if ($dataInicio) {
            $sql .= " AND DATE(criado_em) >= ?";
            $valores[] = $dataInicio;
        }
        
        if ($dataFim) {
            $sql .= " AND DATE(criado_em) <= ?";
            $valores[] = $dataFim;
        }
        
        return $this->db->fetchOne($sql, $valores);
    }
    
    public function getMovimentacoesNaoSincronizadas() {
        $sql = "SELECT m.*, p.nome as produto_nome, p.codigo as produto_codigo 
                FROM movimentacoes m 
                LEFT JOIN produtos p ON m.produto_id = p.id 
                WHERE m.sincronizado = 0 
                ORDER BY m.criado_em ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function marcarComoSincronizada($id) {
        $sql = "UPDATE movimentacoes SET sincronizado = 1 WHERE id = ?";
        return $this->db->execute($sql, [$id]);
    }
    
    public function marcarParaSincronizacao($tabela, $registroId, $acao) {
        $sql = "INSERT INTO sync_log (tabela, registro_id, acao) VALUES (?, ?, ?)";
        return $this->db->execute($sql, [$tabela, $registroId, $acao]);
    }
    
    public function contar($filtros = []) {
        $sql = "SELECT COUNT(*) as total FROM movimentacoes m 
                LEFT JOIN produtos p ON m.produto_id = p.id 
                WHERE 1=1";
        $valores = [];
        
        if (isset($filtros['produto_id'])) {
            $sql .= " AND m.produto_id = ?";
            $valores[] = $filtros['produto_id'];
        }
        
        if (isset($filtros['tipo'])) {
            $sql .= " AND m.tipo = ?";
            $valores[] = $filtros['tipo'];
        }
        
        if (isset($filtros['data_inicio'])) {
            $sql .= " AND DATE(m.criado_em) >= ?";
            $valores[] = $filtros['data_inicio'];
        }
        
        if (isset($filtros['data_fim'])) {
            $sql .= " AND DATE(m.criado_em) <= ?";
            $valores[] = $filtros['data_fim'];
        }
        
        if (isset($filtros['excluir_ajustes']) && $filtros['excluir_ajustes']) {
            $sql .= " AND m.tipo != 'AJUSTE'";
        }
        
        $resultado = $this->db->fetchOne($sql, $valores);
        return $resultado['total'] ?? 0;
    }
    
    public function getMovimentacoesPorProduto($produtoId, $limite = 50) {
        $sql = "SELECT m.*, u.nome as usuario_nome 
                FROM movimentacoes m 
                LEFT JOIN usuarios u ON m.usuario_id = u.id 
                WHERE m.produto_id = ? 
                ORDER BY m.criado_em DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$produtoId, $limite]);
    }
    
    private function logAcao($usuarioId, $acao, $tabela, $registroId, $detalhes) {
        $sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, detalhes, ip, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $usuarioId,
            $acao,
            $tabela,
            $registroId,
            $detalhes,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    }
}
