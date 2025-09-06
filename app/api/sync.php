<?php
/**
 * API de Sincronização
 * Sincroniza dados entre Raspberry Pi e servidor
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Permitir requisições OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Movimentacao.php';
require_once __DIR__ . '/../core/Produto.php';
require_once __DIR__ . '/../core/Usuario.php';

class SyncAPI {
    private $db;
    private $movimentacao;
    private $produto;
    private $usuario;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->movimentacao = new Movimentacao();
        $this->produto = new Produto();
        $this->usuario = new Usuario();
    }
    
    public function handleRequest() {
        try {
            // Verificar autenticação
            if (!$this->verificarAutenticacao()) {
                return $this->responderErro('Token de autenticação inválido', 401);
            }
            
            $metodo = $_SERVER['REQUEST_METHOD'];
            $acao = $_GET['acao'] ?? '';
            
            switch ($metodo) {
                case 'GET':
                    return $this->handleGet($acao);
                case 'POST':
                    return $this->handlePost($acao);
                default:
                    return $this->responderErro('Método não permitido', 405);
            }
            
        } catch (Exception $e) {
            error_log("Erro na API Sync: " . $e->getMessage());
            return $this->responderErro('Erro interno do servidor', 500);
        }
    }
    
    private function verificarAutenticacao() {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        // Remover "Bearer " se presente
        $token = str_replace('Bearer ', '', $token);
        
        return $token === SYNC_TOKEN;
    }
    
    private function handleGet($acao) {
        switch ($acao) {
            case 'status':
                return $this->getStatus();
            case 'produtos':
                return $this->getProdutos();
            case 'usuarios':
                return $this->getUsuarios();
            case 'pendentes':
                return $this->getPendentes();
            default:
                return $this->responderErro('Ação não encontrada', 404);
        }
    }
    
    private function handlePost($acao) {
        $dados = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->responderErro('JSON inválido', 400);
        }
        
        switch ($acao) {
            case 'movimentacao':
                return $this->receberMovimentacao($dados);
            case 'produto':
                return $this->receberProduto($dados);
            case 'usuario':
                return $this->receberUsuario($dados);
            case 'confirmar':
                return $this->confirmarSincronizacao($dados);
            default:
                return $this->responderErro('Ação não encontrada', 404);
        }
    }
    
    private function getStatus() {
        $status = [
            'timestamp' => date('Y-m-d H:i:s'),
            'servidor' => 'online',
            'banco' => 'conectado',
            'produtos_ativos' => $this->produto->contar(['ativo' => true]),
            'movimentacoes_hoje' => $this->movimentacao->contar([
                'data_inicio' => date('Y-m-d'),
                'data_fim' => date('Y-m-d')
            ])
        ];
        
        return $this->responderSucesso($status);
    }
    
    private function getProdutos() {
        $produtos = $this->produto->listar(['ativo' => true]);
        
        // Formatar dados para sincronização
        $dados = [];
        foreach ($produtos as $produto) {
            $dados[] = [
                'id' => $produto['id'],
                'codigo' => $produto['codigo'],
                'nome' => $produto['nome'],
                'descricao' => $produto['descricao'],
                'imagem_url' => $produto['imagem_url'],
                'unidade' => $produto['unidade'],
                'estoque_minimo' => $produto['estoque_minimo'],
                'preco_unitario' => $produto['preco_unitario'],
                'quantidade_atual' => $produto['quantidade_atual'] ?? 0,
                'atualizado_em' => $produto['atualizado_em']
            ];
        }
        
        return $this->responderSucesso($dados);
    }
    
    private function getUsuarios() {
        $usuarios = $this->usuario->listar(['ativo' => true]);
        
        // Remover dados sensíveis
        $dados = [];
        foreach ($usuarios as $usuario) {
            $dados[] = [
                'id' => $usuario['id'],
                'nome' => $usuario['nome'],
                'login' => $usuario['login'],
                'perfil' => $usuario['perfil']
            ];
        }
        
        return $this->responderSucesso($dados);
    }
    
    private function getPendentes() {
        $pendentes = $this->movimentacao->getMovimentacoesNaoSincronizadas();
        
        $dados = [];
        foreach ($pendentes as $mov) {
            $dados[] = [
                'id' => $mov['id'],
                'produto_id' => $mov['produto_id'],
                'tipo' => $mov['tipo'],
                'quantidade' => $mov['quantidade'],
                'quantidade_anterior' => $mov['quantidade_anterior'],
                'quantidade_atual' => $mov['quantidade_atual'],
                'usuario_id' => $mov['usuario_id'],
                'origem' => $mov['origem'],
                'observacao' => $mov['observacao'],
                'criado_em' => $mov['criado_em']
            ];
        }
        
        return $this->responderSucesso($dados);
    }
    
    private function receberMovimentacao($dados) {
        // Validar dados obrigatórios
        if (empty($dados['produto_id']) || empty($dados['tipo']) || !isset($dados['quantidade'])) {
            return $this->responderErro('Dados obrigatórios não fornecidos', 400);
        }
        
        try {
            // Verificar se já existe (evitar duplicação)
            $existe = $this->db->fetchOne(
                "SELECT id FROM movimentacoes WHERE produto_id = ? AND tipo = ? AND quantidade = ? AND criado_em = ?",
                [$dados['produto_id'], $dados['tipo'], $dados['quantidade'], $dados['criado_em']]
            );
            
            if ($existe) {
                return $this->responderSucesso(['id' => $existe['id'], 'status' => 'já_existe']);
            }
            
            // Registrar movimentação
            $resultado = $this->movimentacao->registrar([
                'produto_id' => $dados['produto_id'],
                'tipo' => $dados['tipo'],
                'quantidade' => $dados['quantidade'],
                'usuario_id' => $dados['usuario_id'] ?? null,
                'origem' => 'pi',
                'observacao' => $dados['observacao'] ?? ''
            ]);
            
            return $this->responderSucesso([
                'id' => $resultado['id'],
                'status' => 'registrado',
                'quantidade_atual' => $resultado['quantidade_atual']
            ]);
            
        } catch (Exception $e) {
            return $this->responderErro($e->getMessage(), 400);
        }
    }
    
    private function receberProduto($dados) {
        // Validar dados obrigatórios
        if (empty($dados['codigo']) || empty($dados['nome'])) {
            return $this->responderErro('Código e nome são obrigatórios', 400);
        }
        
        try {
            // Verificar se produto já existe
            $existe = $this->produto->buscarPorCodigo($dados['codigo']);
            
            if ($existe) {
                // Atualizar produto existente
                $this->produto->atualizar($existe['id'], $dados);
                return $this->responderSucesso(['id' => $existe['id'], 'status' => 'atualizado']);
            } else {
                // Criar novo produto
                $id = $this->produto->criar($dados);
                return $this->responderSucesso(['id' => $id, 'status' => 'criado']);
            }
            
        } catch (Exception $e) {
            return $this->responderErro($e->getMessage(), 400);
        }
    }
    
    private function receberUsuario($dados) {
        // Validar dados obrigatórios
        if (empty($dados['login']) || empty($dados['nome'])) {
            return $this->responderErro('Login e nome são obrigatórios', 400);
        }
        
        try {
            // Verificar se usuário já existe
            $existe = $this->db->fetchOne("SELECT id FROM usuarios WHERE login = ?", [$dados['login']]);
            
            if ($existe) {
                // Atualizar usuário existente
                $this->usuario->atualizar($existe['id'], $dados);
                return $this->responderSucesso(['id' => $existe['id'], 'status' => 'atualizado']);
            } else {
                // Criar novo usuário
                $id = $this->usuario->criar($dados);
                return $this->responderSucesso(['id' => $id, 'status' => 'criado']);
            }
            
        } catch (Exception $e) {
            return $this->responderErro($e->getMessage(), 400);
        }
    }
    
    private function confirmarSincronizacao($dados) {
        if (empty($dados['ids']) || !is_array($dados['ids'])) {
            return $this->responderErro('IDs não fornecidos', 400);
        }
        
        $confirmados = 0;
        foreach ($dados['ids'] as $id) {
            try {
                $this->movimentacao->marcarComoSincronizada($id);
                $confirmados++;
            } catch (Exception $e) {
                error_log("Erro ao confirmar sincronização ID {$id}: " . $e->getMessage());
            }
        }
        
        return $this->responderSucesso([
            'confirmados' => $confirmados,
            'total' => count($dados['ids'])
        ]);
    }
    
    private function responderSucesso($dados) {
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'data' => $dados,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        return true;
    }
    
    private function responderErro($mensagem, $codigo = 400) {
        http_response_code($codigo);
        echo json_encode([
            'success' => false,
            'error' => $mensagem,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
        return false;
    }
}

// Executar API
$api = new SyncAPI();
$api->handleRequest();
