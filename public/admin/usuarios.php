<?php
/**
 * Gestão de Usuários - Painel Administrativo
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Usuario.php';

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$usuario = new Usuario();

$usuarioAtual = $usuario->buscarPorId($_SESSION['usuario_id']);

// Verificar se é admin
if ($usuarioAtual['perfil'] !== 'admin') {
    header('Location: ../ui.php');
    exit();
}

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../login.php');
    exit();
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'adicionar_usuario':
                $dados = [
                    'nome' => $_POST['nome'],
                    'login' => $_POST['login'],
                    'senha' => $_POST['senha'],
                    'perfil' => $_POST['perfil'],
                    'ativo' => isset($_POST['ativo']) ? 1 : 0
                ];
                $usuario->criar($dados);
                header('Location: usuarios.php?msg=usuario_adicionado');
                exit();
                break;
                
            case 'editar_usuario':
                $dados = [
                    'nome' => $_POST['nome'],
                    'login' => $_POST['login'],
                    'perfil' => $_POST['perfil'],
                    'ativo' => isset($_POST['ativo']) ? 1 : 0
                ];
                
                // Se senha foi fornecida, incluir na atualização
                if (!empty($_POST['senha'])) {
                    $dados['senha'] = $_POST['senha'];
                }
                
                $usuario->atualizar($_POST['id'], $dados);
                header('Location: usuarios.php?msg=usuario_atualizado');
                exit();
                break;
                
            case 'excluir_usuario':
                // Não permitir excluir o próprio usuário
                if ($_POST['id'] != $usuarioAtual['id']) {
                    $usuario->excluir($_POST['id']);
                    header('Location: usuarios.php?msg=usuario_excluido');
                } else {
                    header('Location: usuarios.php?msg=erro_excluir_proprio');
                }
                exit();
                break;
        }
    }
}

// Obter dados
$usuarios = $usuario->listar();
$totalUsuarios = count($usuarios);
$usuariosAtivos = count(array_filter($usuarios, function($u) { return $u['ativo']; }));
$usuariosInativos = $totalUsuarios - $usuariosAtivos;
$admins = count(array_filter($usuarios, function($u) { return $u['perfil'] === 'admin'; }));
$operadores = count(array_filter($usuarios, function($u) { return $u['perfil'] === 'operador'; }));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Usuários - Administração</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .navbar-nav {
            display: flex;
            gap: 2rem;
            list-style: none;
        }
        
        .navbar-nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .navbar-nav a:hover,
        .navbar-nav a.active {
            background: rgba(255,255,255,0.2);
        }
        
        .navbar-user {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 2rem;
            color: #2c3e50;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .card-header {
            background: #34495e;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .table th,
        .table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .modal-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-checkbox input {
            width: auto;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d5f4e6;
            color: #27ae60;
            border: 1px solid #27ae60;
        }
        
        .alert-error {
            background: #fadbd8;
            color: #e74c3c;
            border: 1px solid #e74c3c;
        }
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .badge-danger {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .badge-primary {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .badge-warning {
            background: #fef9e7;
            color: #856404;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .table {
                font-size: 0.875rem;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'usuarios'; include __DIR__ . '/_header.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert <?php echo strpos($_GET['msg'], 'erro') === 0 ? 'alert-error' : 'alert-success'; ?>">
                <?php
                switch ($_GET['msg']) {
                    case 'usuario_adicionado':
                        echo 'Usuário adicionado com sucesso!';
                        break;
                    case 'usuario_atualizado':
                        echo 'Usuário atualizado com sucesso!';
                        break;
                    case 'usuario_excluido':
                        echo 'Usuário excluído com sucesso!';
                        break;
                    case 'erro_excluir_proprio':
                        echo 'Erro: Não é possível excluir seu próprio usuário!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>Gestão de Usuários</h1>
            <button class="btn btn-primary" onclick="abrirModal('adicionarUsuario')">
                <i class="fas fa-plus"></i> Adicionar Usuário
            </button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalUsuarios; ?></div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $usuariosAtivos; ?></div>
                <div class="stat-label">Usuários Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $admins; ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $operadores; ?></div>
                <div class="stat-label">Operadores</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-users"></i> Lista de Usuários</span>
                <span><?php echo $totalUsuarios; ?> usuários</span>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Login</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($u['nome']); ?></strong>
                                    <?php if ($u['id'] == $usuarioAtual['id']): ?>
                                        <small>(Você)</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($u['login']); ?></td>
                                <td>
                                    <span class="badge <?php echo $u['perfil'] === 'admin' ? 'badge-primary' : 'badge-warning'; ?>">
                                        <?php echo ucfirst($u['perfil']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $u['ativo'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $u['ativo'] ? 'Ativo' : 'Inativo'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($u['criado_em'])); ?></td>
                                <td class="actions">
                                    <button class="btn btn-warning btn-sm" onclick="editarUsuario(<?php echo $u['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($u['id'] != $usuarioAtual['id']): ?>
                                        <button class="btn btn-danger btn-sm" onclick="excluirUsuario(<?php echo $u['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Adicionar Usuário -->
    <div id="modalAdicionarUsuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Adicionar Usuário</h3>
                <button class="close" onclick="fecharModal('adicionarUsuario')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="adicionar_usuario">
                
                <div class="form-group">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Login:</label>
                    <input type="text" name="login" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Senha:</label>
                    <input type="password" name="senha" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Perfil:</label>
                    <select name="perfil" class="form-input" required>
                        <option value="operador">Operador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" name="ativo" id="ativo" checked>
                        <label for="ativo">Usuário ativo</label>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-danger" onclick="fecharModal('adicionarUsuario')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Editar Usuário -->
    <div id="modalEditarUsuario" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Usuário</h3>
                <button class="close" onclick="fecharModal('editarUsuario')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="editar_usuario">
                <input type="hidden" name="id" id="editar_usuario_id">
                
                <div class="form-group">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" id="editar_nome" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Login:</label>
                    <input type="text" name="login" id="editar_login" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nova Senha (deixe em branco para manter a atual):</label>
                    <input type="password" name="senha" id="editar_senha" class="form-input" placeholder="Digite nova senha ou deixe em branco">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Perfil:</label>
                    <select name="perfil" id="editar_perfil" class="form-input" required>
                        <option value="operador">Operador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <div class="form-checkbox">
                        <input type="checkbox" name="ativo" id="editar_ativo">
                        <label for="editar_ativo">Usuário ativo</label>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-danger" onclick="fecharModal('editarUsuario')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModal(tipo) {
            document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).style.display = 'flex';
        }
        
        function fecharModal(tipo) {
            document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).style.display = 'none';
        }
        
        function editarUsuario(id) {
            // Carregar dados do usuário para edição
            carregarDadosUsuario(id);
            abrirModal('editarUsuario');
        }
        
        function carregarDadosUsuario(usuarioId) {
            // Buscar dados do usuário via AJAX
            fetch('api/buscar_usuario_por_id.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: usuarioId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const usuario = data.usuario;
                    document.getElementById('editar_usuario_id').value = usuario.id;
                    document.getElementById('editar_nome').value = usuario.nome;
                    document.getElementById('editar_login').value = usuario.login;
                    document.getElementById('editar_perfil').value = usuario.perfil;
                    document.getElementById('editar_ativo').checked = usuario.ativo == 1;
                    // Limpar campo de senha
                    document.getElementById('editar_senha').value = '';
                } else {
                    alert('Erro ao carregar dados do usuário');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar dados do usuário');
            });
        }
        
        function excluirUsuario(id) {
            if (confirm('Tem certeza que deseja excluir este usuário?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="excluir_usuario">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Fechar modal ao clicar fora
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
