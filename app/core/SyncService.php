<?php
/**
 * Serviço de Sincronização para Raspberry Pi
 * Envia dados locais para o servidor
 */

require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Movimentacao.php';

class SyncService {
    private $db;
    private $movimentacao;
    private $apiUrl;
    private $token;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->movimentacao = new Movimentacao();
        $this->apiUrl = API_URL;
        $this->token = SYNC_TOKEN;
    }
    
    /**
     * Sincroniza todas as movimentações pendentes
     */
    public function sincronizarMovimentacoes() {
        try {
            // Obter movimentações não sincronizadas
            $pendentes = $this->movimentacao->getMovimentacoesNaoSincronizadas();
            
            if (empty($pendentes)) {
                return ['status' => 'success', 'message' => 'Nenhuma movimentação pendente'];
            }
            
            $enviadas = 0;
            $erros = 0;
            $idsConfirmados = [];
            
            foreach ($pendentes as $mov) {
                try {
                    $dados = [
                        'produto_id' => $mov['produto_id'],
                        'tipo' => $mov['tipo'],
                        'quantidade' => $mov['quantidade'],
                        'usuario_id' => $mov['usuario_id'],
                        'observacao' => $mov['observacao'],
                        'criado_em' => $mov['criado_em']
                    ];
                    
                    $response = $this->enviarParaServidor('movimentacao', $dados);
                    
                    if ($response && $response['success']) {
                        $idsConfirmados[] = $mov['id'];
                        $enviadas++;
                    } else {
                        $erros++;
                        error_log("Erro ao sincronizar movimentação ID {$mov['id']}: " . ($response['error'] ?? 'Erro desconhecido'));
                    }
                    
                } catch (Exception $e) {
                    $erros++;
                    error_log("Exceção ao sincronizar movimentação ID {$mov['id']}: " . $e->getMessage());
                }
            }
            
            // Confirmar sincronização no servidor
            if (!empty($idsConfirmados)) {
                $this->confirmarSincronizacao($idsConfirmados);
            }
            
            return [
                'status' => 'success',
                'enviadas' => $enviadas,
                'erros' => $erros,
                'total' => count($pendentes)
            ];
            
        } catch (Exception $e) {
            error_log("Erro geral na sincronização: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Baixa produtos atualizados do servidor
     */
    public function sincronizarProdutos() {
        try {
            $response = $this->fazerRequisicao('GET', 'produtos');
            
            if (!$response || !$response['success']) {
                throw new Exception('Erro ao obter produtos do servidor');
            }
            
            $produtos = $response['data'];
            $atualizados = 0;
            $criados = 0;
            
            foreach ($produtos as $produto) {
                try {
                    // Verificar se produto existe localmente
                    $existe = $this->db->fetchOne("SELECT id FROM produtos WHERE codigo = ?", [$produto['codigo']]);
                    
                    if ($existe) {
                        // Atualizar produto existente
                        $this->atualizarProdutoLocal($existe['id'], $produto);
                        $atualizados++;
                    } else {
                        // Criar novo produto
                        $this->criarProdutoLocal($produto);
                        $criados++;
                    }
                    
                } catch (Exception $e) {
                    error_log("Erro ao sincronizar produto {$produto['codigo']}: " . $e->getMessage());
                }
            }
            
            return [
                'status' => 'success',
                'atualizados' => $atualizados,
                'criados' => $criados,
                'total' => count($produtos)
            ];
            
        } catch (Exception $e) {
            error_log("Erro ao sincronizar produtos: " . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Verifica status de conectividade com o servidor
     */
    public function verificarConexao() {
        try {
            $response = $this->fazerRequisicao('GET', 'status');
            
            if ($response && $response['success']) {
                return [
                    'status' => 'online',
                    'servidor' => $response['data']['servidor'],
                    'timestamp' => $response['data']['timestamp']
                ];
            } else {
                return ['status' => 'offline', 'message' => 'Servidor não respondeu'];
            }
            
        } catch (Exception $e) {
            return ['status' => 'offline', 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Executa sincronização completa
     */
    public function sincronizarCompleta() {
        $resultado = [
            'timestamp' => date('Y-m-d H:i:s'),
            'conexao' => null,
            'produtos' => null,
            'movimentacoes' => null
        ];
        
        // Verificar conexão
        $resultado['conexao'] = $this->verificarConexao();
        
        if ($resultado['conexao']['status'] === 'online') {
            // Sincronizar produtos
            $resultado['produtos'] = $this->sincronizarProdutos();
            
            // Sincronizar movimentações
            $resultado['movimentacoes'] = $this->sincronizarMovimentacoes();
        }
        
        return $resultado;
    }
    
    /**
     * Envia dados para o servidor
     */
    private function enviarParaServidor($acao, $dados) {
        return $this->fazerRequisicao('POST', $acao, $dados);
    }
    
    /**
     * Faz requisição HTTP para o servidor
     */
    private function fazerRequisicao($metodo, $acao, $dados = null) {
        $url = $this->apiUrl . "/sync.php?acao=" . urlencode($acao);
        
        $opcoes = [
            'http' => [
                'method' => $metodo,
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: ' . $this->token,
                    'User-Agent: Gelo-Canada-Sync/1.0'
                ],
                'timeout' => API_TIMEOUT
            ]
        ];
        
        if ($metodo === 'POST' && $dados) {
            $opcoes['http']['content'] = json_encode($dados);
        }
        
        $contexto = stream_context_create($opcoes);
        
        try {
            $response = file_get_contents($url, false, $contexto);
            
            if ($response === false) {
                throw new Exception('Erro na requisição HTTP');
            }
            
            $dados = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Resposta inválida do servidor');
            }
            
            return $dados;
            
        } catch (Exception $e) {
            error_log("Erro na requisição para {$url}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Confirma sincronização no servidor
     */
    private function confirmarSincronizacao($ids) {
        try {
            $dados = ['ids' => $ids];
            $response = $this->enviarParaServidor('confirmar', $dados);
            
            if ($response && $response['success']) {
                // Marcar como sincronizado localmente
                foreach ($ids as $id) {
                    $this->movimentacao->marcarComoSincronizada($id);
                }
            }
            
            return $response;
            
        } catch (Exception $e) {
            error_log("Erro ao confirmar sincronização: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Atualiza produto local com dados do servidor
     */
    private function atualizarProdutoLocal($id, $dados) {
        $sql = "UPDATE produtos SET 
                    nome = ?, 
                    descricao = ?, 
                    imagem_url = ?, 
                    unidade = ?, 
                    estoque_minimo = ?, 
                    preco_unitario = ?,
                    atualizado_em = NOW()
                WHERE id = ?";
        
        $this->db->execute($sql, [
            $dados['nome'],
            $dados['descricao'],
            $dados['imagem_url'],
            $dados['unidade'],
            $dados['estoque_minimo'],
            $dados['preco_unitario'],
            $id
        ]);
        
        // Atualizar estoque
        $sql = "UPDATE estoque SET 
                    quantidade_atual = ?, 
                    quantidade_minima = ?,
                    atualizado_em = NOW()
                WHERE produto_id = ?";
        
        $this->db->execute($sql, [
            $dados['quantidade_atual'],
            $dados['estoque_minimo'],
            $id
        ]);
    }
    
    /**
     * Cria produto local com dados do servidor
     */
    private function criarProdutoLocal($dados) {
        // Inserir produto
        $sql = "INSERT INTO produtos (codigo, nome, descricao, imagem_url, unidade, estoque_minimo, preco_unitario) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $this->db->execute($sql, [
            $dados['codigo'],
            $dados['nome'],
            $dados['descricao'],
            $dados['imagem_url'],
            $dados['unidade'],
            $dados['estoque_minimo'],
            $dados['preco_unitario']
        ]);
        
        $produtoId = $this->db->lastInsertId();
        
        // Criar estoque
        $sql = "INSERT INTO estoque (produto_id, quantidade_atual, quantidade_minima) VALUES (?, ?, ?)";
        $this->db->execute($sql, [
            $produtoId,
            $dados['quantidade_atual'],
            $dados['estoque_minimo']
        ]);
    }
    
    /**
     * Executa sincronização automática (para cron)
     */
    public static function executarSincronizacaoAutomatica() {
        try {
            $sync = new self();
            $resultado = $sync->sincronizarCompleta();
            
            // Log do resultado
            $log = date('Y-m-d H:i:s') . " - Sincronização automática: " . json_encode($resultado) . "\n";
            file_put_contents(LOG_PATH . 'sync.log', $log, FILE_APPEND | LOCK_EX);
            
            return $resultado;
            
        } catch (Exception $e) {
            $log = date('Y-m-d H:i:s') . " - Erro na sincronização automática: " . $e->getMessage() . "\n";
            file_put_contents(LOG_PATH . 'sync.log', $log, FILE_APPEND | LOCK_EX);
            
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}
