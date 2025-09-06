<?php
/**
 * Controle de Estoque - Painel Administrativo
 * Gestão completa do estoque de produtos
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Usuario.php';
require_once __DIR__ . '/../../app/core/Produto.php';
require_once __DIR__ . '/../../app/core/Movimentacao.php';

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

$db = Database::getInstance();
$usuario = new Usuario();
$produto = new Produto();
$movimentacao = new Movimentacao();

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

// Obter dados para dashboard
$estatisticas = $produto->getEstatisticas();
$produtos = $produto->listar();
$estoqueBaixo = $produto->getEstoqueBaixo();
$estoqueZero = $produto->getEstoqueZero();
$ultimasMovimentacoes = $movimentacao->getUltimasMovimentacoes(10);

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['acao'])) {
        switch ($_POST['acao']) {
            case 'adicionar_produto':
                // Validar código numérico
                $codigo = $_POST['codigo'];
                if (!is_numeric($codigo) || $codigo <= 0) {
                    header('Location: estoque.php?msg=erro_codigo_invalido');
                    exit();
                }
                
                $dados = [
                    'codigo' => $codigo,
                    'nome' => $_POST['nome'],
                    'descricao' => $_POST['descricao'],
                    'unidade' => $_POST['unidade'],
                    'estoque_minimo' => $_POST['estoque_minimo'],
                    'preco_unitario' => $_POST['preco_unitario']
                ];
                $produto->criar($dados);
                header('Location: estoque.php?msg=produto_adicionado');
                exit();
                break;
                
            case 'editar_produto':
                // Validar código numérico
                $codigo = $_POST['codigo'];
                if (!is_numeric($codigo) || $codigo <= 0) {
                    header('Location: estoque.php?msg=erro_codigo_invalido');
                    exit();
                }
                
                $dados = [
                    'id' => $_POST['id'],
                    'codigo' => $codigo,
                    'nome' => $_POST['nome'],
                    'descricao' => $_POST['descricao'],
                    'unidade' => $_POST['unidade'],
                    'estoque_minimo' => $_POST['estoque_minimo'],
                    'preco_unitario' => $_POST['preco_unitario']
                ];
                $produto->atualizar($_POST['id'], $dados);
                header('Location: estoque.php?msg=produto_atualizado');
                exit();
                break;
                
            case 'registrar_movimentacao':
                $dados = [
                    'produto_id' => $_POST['produto_id'],
                    'tipo' => $_POST['tipo'],
                    'quantidade' => $_POST['quantidade'],
                    'origem' => 'pc'
                ];
                $movimentacao->registrar($dados);
                header('Location: estoque.php?msg=movimentacao_registrada');
                exit();
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque - Administração</title>
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
        
        .btn-success:hover {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
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
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #7f8c8d;
            font-size: 1rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: bold;
        }
        
        .status-ok {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .status-low {
            background: #fef9e7;
            color: #f39c12;
        }
        
        .status-zero {
            background: #fadbd8;
            color: #e74c3c;
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
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'estoque'; include __DIR__ . '/_header.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <?php if ($_GET['msg'] === 'erro_codigo_invalido'): ?>
                <div class="alert alert-error">
                    Erro: O código do produto deve conter apenas números e ser maior que zero!
                </div>
            <?php else: ?>
                <div class="alert alert-success">
                    <?php
                    switch ($_GET['msg']) {
                        case 'produto_adicionado':
                            echo 'Produto adicionado com sucesso!';
                            break;
                        case 'produto_atualizado':
                            echo 'Produto atualizado com sucesso!';
                            break;
                        case 'movimentacao_registrada':
                            echo 'Movimentação registrada com sucesso!';
                            break;
                    }
                    ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="header">
            <h1>Gestão de Estoque</h1>
            <button class="btn btn-primary" onclick="abrirModal('adicionarProduto')">
                <i class="fas fa-plus"></i> Adicionar Produto
            </button>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $estatisticas['total_produtos']; ?></div>
                <div class="stat-label">Total de Produtos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estatisticas['produtos_ativos']; ?></div>
                <div class="stat-label">Produtos Ativos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($estoqueBaixo); ?></div>
                <div class="stat-label">Estoque Baixo</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($estoqueZero); ?></div>
                <div class="stat-label">Estoque Zero</div>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-list"></i> Produtos em Estoque
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Produto</th>
                                <th>Estoque</th>
                                <th>Mínimo</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtos as $p): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($p['codigo']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['nome']); ?></td>
                                    <td><?php echo $p['quantidade_atual'] ?? 0; ?> <?php echo $p['unidade']; ?></td>
                                    <td><?php echo $p['estoque_minimo']; ?> <?php echo $p['unidade']; ?></td>
                                    <td>
                                        <?php
                                        $quantidade = $p['quantidade_atual'] ?? 0;
                                        $minimo = $p['estoque_minimo'];
                                        
                                        if ($quantidade == 0) {
                                            echo '<span class="status-badge status-zero">ZERO</span>';
                                        } elseif ($quantidade <= $minimo) {
                                            echo '<span class="status-badge status-low">BAIXO</span>';
                                        } else {
                                            echo '<span class="status-badge status-ok">OK</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary" onclick="abrirModal('movimentacao', <?php echo $p['id']; ?>)">
                                            <i class="fas fa-exchange-alt"></i>
                                        </button>
                                        <button class="btn btn-success" onclick="abrirModal('editarProduto', <?php echo $p['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-history"></i> Últimas Movimentações
                </div>
                <div class="card-body">
                    <?php if (empty($ultimasMovimentacoes)): ?>
                        <p>Nenhuma movimentação registrada.</p>
                    <?php else: ?>
                        <?php foreach ($ultimasMovimentacoes as $mov): ?>
                            <div style="padding: 0.5rem 0; border-bottom: 1px solid #ecf0f1;">
                                <div style="font-weight: bold; color: #2c3e50;">
                                    <?php echo htmlspecialchars($mov['produto_nome']); ?>
                                </div>
                                <div style="font-size: 0.875rem; color: #7f8c8d;">
                                    <?php echo $mov['tipo']; ?>: <?php echo $mov['quantidade']; ?> <?php echo $mov['unidade']; ?>
                                    <br>
                                    <small><?php echo date('d/m/Y H:i', strtotime($mov['criado_em'])); ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Adicionar Produto -->
    <div id="modalAdicionarProduto" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Adicionar Produto</h3>
                <button class="close" onclick="fecharModal('adicionarProduto')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="adicionar_produto">
                
                <div class="form-group">
                    <label class="form-label">Código (apenas números):</label>
                    <input type="number" name="codigo" class="form-input" required min="1" step="1" placeholder="Ex: 12345">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrição:</label>
                    <textarea name="descricao" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Unidade:</label>
                    <select name="unidade" class="form-input" required>
                        <option value="kg">Quilogramas (kg)</option>
                        <option value="g">Gramas (g)</option>
                        <option value="l">Litros (l)</option>
                        <option value="ml">Mililitros (ml)</option>
                        <option value="un">Unidades (un)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estoque Mínimo:</label>
                    <input type="number" name="estoque_minimo" class="form-input" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Preço Unitário:</label>
                    <input type="number" name="preco_unitario" class="form-input" step="0.01" min="0" required>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-danger" onclick="fecharModal('adicionarProduto')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Adicionar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Editar Produto -->
    <div id="modalEditarProduto" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Editar Produto</h3>
                <button class="close" onclick="fecharModal('editarProduto')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="editar_produto">
                <input type="hidden" name="id" id="editar_produto_id">
                
                <div class="form-group">
                    <label class="form-label">Código (apenas números):</label>
                    <input type="number" name="codigo" id="editar_codigo" class="form-input" required min="1" step="1" placeholder="Ex: 12345">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Nome:</label>
                    <input type="text" name="nome" id="editar_nome" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Descrição:</label>
                    <textarea name="descricao" id="editar_descricao" class="form-input" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Unidade:</label>
                    <select name="unidade" id="editar_unidade" class="form-input" required>
                        <option value="kg">Quilogramas (kg)</option>
                        <option value="g">Gramas (g)</option>
                        <option value="l">Litros (l)</option>
                        <option value="ml">Mililitros (ml)</option>
                        <option value="un">Unidades (un)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Estoque Mínimo:</label>
                    <input type="number" name="estoque_minimo" id="editar_estoque_minimo" class="form-input" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Preço Unitário:</label>
                    <input type="number" name="preco_unitario" id="editar_preco_unitario" class="form-input" step="0.01" min="0" required>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-danger" onclick="fecharModal('editarProduto')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Atualizar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Movimentação -->
    <div id="modalMovimentacao" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Registrar Movimentação</h3>
                <button class="close" onclick="fecharModal('movimentacao')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="acao" value="registrar_movimentacao">
                <input type="hidden" name="produto_id" id="movimentacao_produto_id">
                
                <div class="form-group">
                    <label class="form-label">Tipo:</label>
                    <select name="tipo" class="form-input" required>
                        <option value="ENTRADA">Entrada</option>
                        <option value="SAIDA">Saída</option>
                        <option value="AJUSTE">Ajuste</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Quantidade:</label>
                    <input type="number" name="quantidade" class="form-input" step="0.01" min="0.01" required>
                </div>
                
                <div style="text-align: right; margin-top: 1.5rem;">
                    <button type="button" class="btn btn-danger" onclick="fecharModal('movimentacao')">Cancelar</button>
                    <button type="submit" class="btn btn-success">Registrar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function abrirModal(tipo, produtoId = null) {
            if (tipo === 'movimentacao' && produtoId) {
                document.getElementById('movimentacao_produto_id').value = produtoId;
            } else if (tipo === 'editarProduto' && produtoId) {
                // Carregar dados do produto para edição
                carregarDadosProduto(produtoId);
            }
            document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).style.display = 'flex';
        }
        
        function carregarDadosProduto(produtoId) {
            // Buscar dados do produto via AJAX
            fetch('api/buscar_produto_por_id.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: produtoId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const produto = data.produto;
                    document.getElementById('editar_produto_id').value = produto.id;
                    document.getElementById('editar_codigo').value = produto.codigo;
                    document.getElementById('editar_nome').value = produto.nome;
                    document.getElementById('editar_descricao').value = produto.descricao;
                    document.getElementById('editar_unidade').value = produto.unidade;
                    document.getElementById('editar_estoque_minimo').value = produto.estoque_minimo;
                    document.getElementById('editar_preco_unitario').value = produto.preco_unitario;
                } else {
                    alert('Erro ao carregar dados do produto');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar dados do produto');
            });
        }
        
        function fecharModal(tipo) {
            document.getElementById('modal' + tipo.charAt(0).toUpperCase() + tipo.slice(1)).style.display = 'none';
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
