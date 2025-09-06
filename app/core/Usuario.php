<?php
/**
 * Classe de serviço para gerenciar usuários
 */

require_once __DIR__ . '/Database.php';

class Usuario {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function autenticar($login, $senha) {
        $sql = "SELECT id, nome, login, senha_hash, perfil, ativo FROM usuarios WHERE login = ? AND ativo = 1";
        $usuario = $this->db->fetchOne($sql, [$login]);
        
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {
            // Registrar log de login
            $this->logAcao($usuario['id'], 'LOGIN', 'usuarios', $usuario['id'], 'Login realizado com sucesso');
            
            // Remover senha_hash do retorno
            unset($usuario['senha_hash']);
            return $usuario;
        }
        
        return false;
    }
    
    public function criar($dados) {
        // Validar dados
        if (empty($dados['nome']) || empty($dados['login']) || empty($dados['senha'])) {
            throw new Exception('Nome, login e senha são obrigatórios');
        }
        
        if (strlen($dados['senha']) < PASSWORD_MIN_LENGTH) {
            throw new Exception('Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
        }
        
        // Verificar se login já existe
        $existe = $this->db->fetchOne("SELECT id FROM usuarios WHERE login = ?", [$dados['login']]);
        if ($existe) {
            throw new Exception('Login já existe');
        }
        
        // Hash da senha
        $senhaHash = password_hash($dados['senha'], PASSWORD_DEFAULT);
        
        // Inserir usuário
        $sql = "INSERT INTO usuarios (nome, login, senha_hash, perfil) VALUES (?, ?, ?, ?)";
        $this->db->execute($sql, [
            $dados['nome'],
            $dados['login'],
            $senhaHash,
            $dados['perfil'] ?? 'operador'
        ]);
        
        $usuarioId = $this->db->lastInsertId();
        
        // Log da ação
        $this->logAcao($usuarioId, 'CRIAR', 'usuarios', $usuarioId, 'Usuário criado');
        
        return $usuarioId;
    }
    
    public function atualizar($id, $dados) {
        // Verificar se usuário existe
        $usuario = $this->db->fetchOne("SELECT id FROM usuarios WHERE id = ?", [$id]);
        if (!$usuario) {
            throw new Exception('Usuário não encontrado');
        }
        
        $campos = [];
        $valores = [];
        
        if (isset($dados['nome'])) {
            $campos[] = 'nome = ?';
            $valores[] = $dados['nome'];
        }
        
        if (isset($dados['perfil'])) {
            $campos[] = 'perfil = ?';
            $valores[] = $dados['perfil'];
        }
        
        if (isset($dados['ativo'])) {
            $campos[] = 'ativo = ?';
            $valores[] = $dados['ativo'] ? 1 : 0;
        }
        
        if (isset($dados['senha']) && !empty($dados['senha'])) {
            if (strlen($dados['senha']) < PASSWORD_MIN_LENGTH) {
                throw new Exception('Senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
            }
            $campos[] = 'senha_hash = ?';
            $valores[] = password_hash($dados['senha'], PASSWORD_DEFAULT);
        }
        
        if (empty($campos)) {
            throw new Exception('Nenhum campo para atualizar');
        }
        
        $valores[] = $id;
        $sql = "UPDATE usuarios SET " . implode(', ', $campos) . " WHERE id = ?";
        $this->db->execute($sql, $valores);
        
        // Log da ação
        $this->logAcao($id, 'ATUALIZAR', 'usuarios', $id, 'Usuário atualizado');
        
        return true;
    }
    
    public function buscarPorId($id) {
        $sql = "SELECT id, nome, login, perfil, ativo, criado_em FROM usuarios WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function listar($filtros = []) {
        $sql = "SELECT id, nome, login, perfil, ativo, criado_em FROM usuarios WHERE 1=1";
        $valores = [];
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND ativo = ?";
            $valores[] = $filtros['ativo'] ? 1 : 0;
        }
        
        if (isset($filtros['perfil'])) {
            $sql .= " AND perfil = ?";
            $valores[] = $filtros['perfil'];
        }
        
        if (isset($filtros['busca'])) {
            $sql .= " AND (nome LIKE ? OR login LIKE ?)";
            $busca = '%' . $filtros['busca'] . '%';
            $valores[] = $busca;
            $valores[] = $busca;
        }
        
        $sql .= " ORDER BY nome";
        
        if (isset($filtros['limite'])) {
            $sql .= " LIMIT " . (int)$filtros['limite'];
        }
        
        return $this->db->fetchAll($sql, $valores);
    }
    
    public function alterarSenha($id, $senhaAtual, $novaSenha) {
        // Verificar senha atual
        $usuario = $this->db->fetchOne("SELECT senha_hash FROM usuarios WHERE id = ?", [$id]);
        if (!$usuario || !password_verify($senhaAtual, $usuario['senha_hash'])) {
            throw new Exception('Senha atual incorreta');
        }
        
        // Validar nova senha
        if (strlen($novaSenha) < PASSWORD_MIN_LENGTH) {
            throw new Exception('Nova senha deve ter pelo menos ' . PASSWORD_MIN_LENGTH . ' caracteres');
        }
        
        // Atualizar senha
        $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        $sql = "UPDATE usuarios SET senha_hash = ? WHERE id = ?";
        $this->db->execute($sql, [$novaSenhaHash, $id]);
        
        // Log da ação
        $this->logAcao($id, 'ALTERAR_SENHA', 'usuarios', $id, 'Senha alterada');
        
        return true;
    }
    
    public function desativar($id) {
        $sql = "UPDATE usuarios SET ativo = 0 WHERE id = ?";
        $this->db->execute($sql, [$id]);
        
        // Log da ação
        $this->logAcao($id, 'DESATIVAR', 'usuarios', $id, 'Usuário desativado');
        
        return true;
    }
    
    public function ativar($id) {
        $sql = "UPDATE usuarios SET ativo = 1 WHERE id = ?";
        $this->db->execute($sql, [$id]);
        
        // Log da ação
        $this->logAcao($id, 'ATIVAR', 'usuarios', $id, 'Usuário ativado');
        
        return true;
    }
    
    public function contar($filtros = []) {
        $sql = "SELECT COUNT(*) as total FROM usuarios WHERE 1=1";
        $valores = [];
        
        if (isset($filtros['ativo'])) {
            $sql .= " AND ativo = ?";
            $valores[] = $filtros['ativo'] ? 1 : 0;
        }
        
        if (isset($filtros['perfil'])) {
            $sql .= " AND perfil = ?";
            $valores[] = $filtros['perfil'];
        }
        
        $resultado = $this->db->fetchOne($sql, $valores);
        return $resultado['total'] ?? 0;
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
