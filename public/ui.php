<?php
/**
 * Interface Simples - Entregadores
 * Versão mobile para registro de movimentações
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Usuario.php';
require_once __DIR__ . '/../app/core/Produto.php';
require_once __DIR__ . '/../app/core/Movimentacao.php';

session_start();

// Verificar se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit();
}

$db = Database::getInstance();
$usuario = new Usuario();
$produto = new Produto();
$movimentacao = new Movimentacao();

$usuarioAtual = $usuario->buscarPorId($_SESSION['usuario_id']);

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Verificar se é requisição AJAX
if (isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        switch ($_POST['acao']) {
            case 'buscar_produto':
                $codigo = $_POST['codigo'] ?? '';
                $produtoEncontrado = $produto->buscarPorCodigo($codigo);
                
                if ($produtoEncontrado) {
                    echo json_encode([
                        'success' => true,
                        'produto' => $produtoEncontrado
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Produto não encontrado'
                    ]);
                }
                break;
                
            case 'registrar_movimentacao':
                $dados = [
                    'produto_id' => $_POST['produto_id'],
                    'tipo' => $_POST['tipo'],
                    'quantidade' => $_POST['quantidade'],
                    'usuario_id' => $_SESSION['usuario_id'],
                    'origem' => 'cel'
                ];
                
                $resultado = $movimentacao->registrar($dados);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Movimentação registrada com sucesso',
                    'quantidade_atual' => $resultado['quantidade_atual']
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Ação não reconhecida']);
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Estoque - Entregador</title>
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
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .user-info {
            background: white;
            padding: 1rem;
            margin: 1rem;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .user-name {
            font-weight: bold;
            color: #333;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .main-content {
            padding: 1rem;
        }
        
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-btn {
            background: white;
            border: none;
            padding: 2rem 1rem;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            text-decoration: none;
            color: #333;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        
        .action-btn.entrada {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
        
        .action-btn.saida {
            background: linear-gradient(135deg, #dc3545, #fd7e14);
            color: white;
        }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .action-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.25rem;
        }
        
        .action-subtitle {
            font-size: 0.9rem;
            opacity: 0.8;
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
            padding: 1rem;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            width: 100%;
            max-width: 400px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            text-align: center;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            font-size: 1rem;
            margin-bottom: 0.5rem;
            color: #555;
            font-weight: bold;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .form-input:focus {
            border-color: #667eea;
        }
        
        .product-info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin: 1rem 0;
            display: none;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 0.5rem;
        }
        
        .product-name {
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .product-stock {
            font-size: 0.9rem;
            color: #666;
        }
        
        .button-group {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .message {
            padding: 1rem;
            border-radius: 10px;
            margin: 1rem 0;
            font-size: 1rem;
            display: none;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .recent-movements {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .recent-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .movement-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid #eee;
        }
        
        .movement-item:last-child {
            border-bottom: none;
        }
        
        .movement-info {
            flex: 1;
        }
        
        .movement-product {
            font-weight: bold;
            color: #333;
        }
        
        .movement-details {
            font-size: 0.9rem;
            color: #666;
        }
        
        .movement-type {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .type-entrada {
            background: #d4edda;
            color: #155724;
        }
        
        .type-saida {
            background: #f8d7da;
            color: #721c24;
        }
        
        @media (max-width: 480px) {
            .action-buttons {
                grid-template-columns: 1fr;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .modal-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-warehouse"></i> Controle de Estoque</h1>
        <p>Interface para Entregadores</p>
    </div>
    
    <div class="user-info">
        <div>
            <div class="user-name"><?php echo htmlspecialchars($usuarioAtual['nome']); ?></div>
            <small>Entregador</small>
        </div>
        <a href="?logout=1" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Sair
        </a>
    </div>
    
    <div class="main-content">
        <div class="action-buttons">
            <button class="action-btn entrada" onclick="iniciarMovimentacao('ENTRADA')">
                <i class="fas fa-plus action-icon"></i>
                <div class="action-title">ENTRADA</div>
                <div class="action-subtitle">Registrar entrada</div>
            </button>
            
            <button class="action-btn saida" onclick="iniciarMovimentacao('SAIDA')">
                <i class="fas fa-minus action-icon"></i>
                <div class="action-title">SAÍDA</div>
                <div class="action-subtitle">Registrar saída</div>
            </button>
        </div>
        
        <!-- Movimentações Recentes -->
        <div class="recent-movements">
            <div class="recent-title">
                <i class="fas fa-history"></i> Movimentações Recentes
            </div>
            <div id="recentMovements">
                <p style="text-align: center; color: #666;">Carregando...</p>
            </div>
        </div>
    </div>
    
    <!-- Modal de Movimentação -->
    <div class="modal" id="modalMovimentacao">
        <div class="modal-content">
            <div class="modal-title" id="modalTitle">Registrar Movimentação</div>
            
            <div class="message" id="message"></div>
            
            <div class="form-group">
                <label class="form-label" for="codigoProduto">Código do Produto:</label>
                <input type="text" id="codigoProduto" class="form-input" placeholder="Digite o código..." autocomplete="off">
            </div>
            
            <div class="product-info" id="productInfo">
                <img class="product-image" id="productImage" src="" alt="Produto">
                <div class="product-name" id="productName"></div>
                <div class="product-stock" id="productStock"></div>
            </div>
            
            <div class="form-group" id="quantidadeGroup" style="display: none;">
                <label class="form-label" for="quantidade">Quantidade:</label>
                <input type="number" id="quantidade" class="form-input" placeholder="Digite a quantidade..." min="0.01" step="0.01">
            </div>
            
            <div class="button-group">
                <button class="btn btn-secondary" onclick="fecharModal()">Cancelar</button>
                <button class="btn btn-primary" id="btnConfirmar" onclick="confirmarMovimentacao()" style="display: none;">Confirmar</button>
            </div>
        </div>
    </div>
    
    <script>
        let tipoMovimentacao = '';
        let produtoAtual = null;
        let codigoDigitado = '';
        let searchTimeout = null;
        let processandoAcao = false; // Proteção contra duplo clique
        
        // Carregar movimentações recentes
        function carregarMovimentacoesRecentes() {
            fetch('api/movimentacoes.php?limite=5')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.movimentacoes.length > 0) {
                        const html = data.movimentacoes.map(mov => `
                            <div class="movement-item">
                                <div class="movement-info">
                                    <div class="movement-product">${mov.produto_nome}</div>
                                    <div class="movement-details">
                                        ${mov.quantidade} - ${new Date(mov.criado_em).toLocaleString('pt-BR')}
                                    </div>
                                </div>
                                <span class="movement-type type-${mov.tipo.toLowerCase()}">
                                    ${mov.tipo === 'ENTRADA' ? '📥' : '📤'} ${mov.tipo}
                                </span>
                            </div>
                        `).join('');
                        document.getElementById('recentMovements').innerHTML = html;
                    } else {
                        document.getElementById('recentMovements').innerHTML = 
                            '<p style="text-align: center; color: #666;">Nenhuma movimentação recente</p>';
                    }
                })
                .catch(error => {
                    document.getElementById('recentMovements').innerHTML = 
                        '<p style="text-align: center; color: #666;">Erro ao carregar movimentações</p>';
                });
        }
        
        function iniciarMovimentacao(tipo) {
            tipoMovimentacao = tipo;
            document.getElementById('modalTitle').textContent = `Registrar ${tipo}`;
            document.getElementById('codigoProduto').value = '';
            document.getElementById('productInfo').style.display = 'none';
            document.getElementById('quantidadeGroup').style.display = 'none';
            document.getElementById('btnConfirmar').style.display = 'none';
            document.getElementById('message').style.display = 'none';
            document.getElementById('modalMovimentacao').style.display = 'flex';
            document.getElementById('codigoProduto').focus();
        }
        
        function buscarProdutoAutomatico() {
            if (!codigoDigitado) {
                // Se não tem código, limpar tudo
                produtoAtual = null;
                document.getElementById('productInfo').style.display = 'none';
                document.getElementById('message').style.display = 'none';
                mostrarMensagem('Digite o código do produto', 'info');
                return;
            }
            
            // Mostrar que está buscando
            mostrarMensagem('Buscando produto...', 'info');
            
            // Buscar produto via API
            fetch('api/buscar_produto.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ codigo: codigoDigitado })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    produtoAtual = data.produto;
                    mostrarProduto(data.produto);
                    document.getElementById('quantidadeGroup').style.display = 'block';
                    document.getElementById('btnConfirmar').style.display = 'block';
                    mostrarMensagem('Produto encontrado! Digite a quantidade', 'success');
                } else {
                    produtoAtual = null;
                    document.getElementById('productInfo').style.display = 'none';
                    mostrarMensagem('Produto não encontrado', 'error');
                }
            })
            .catch(error => {
                produtoAtual = null;
                document.getElementById('productInfo').style.display = 'none';
                mostrarMensagem('Erro ao buscar produto', 'error');
            });
        }
        
        function buscarProduto() {
            // Função mantida para compatibilidade
            buscarProdutoAutomatico();
        }
        
        function mostrarProduto(produto) {
            document.getElementById('productInfo').style.display = 'block';
            document.getElementById('productName').textContent = produto.nome;
            document.getElementById('productStock').textContent = `Estoque atual: ${produto.quantidade_atual || 0} ${produto.unidade}`;
            
            if (produto.imagem_url) {
                document.getElementById('productImage').src = produto.imagem_url;
                document.getElementById('productImage').style.display = 'block';
            } else {
                document.getElementById('productImage').style.display = 'none';
            }
        }
        
        function confirmarMovimentacao() {
            // Proteção contra duplo clique
            if (processandoAcao) {
                return;
            }
            
            if (!produtoAtual) {
                mostrarMensagem('Produto não selecionado', 'error');
                return;
            }
            
            const quantidade = parseFloat(document.getElementById('quantidade').value);
            
            if (!quantidade || quantidade <= 0) {
                mostrarMensagem('Digite uma quantidade válida', 'error');
                return;
            }
            
            // Marcar que uma ação está sendo processada
            processandoAcao = true;
            mostrarMensagem('Processando...', 'info');
            
            const formData = new FormData();
            formData.append('acao', 'registrar_movimentacao');
            formData.append('produto_id', produtoAtual.id);
            formData.append('tipo', tipoMovimentacao);
            formData.append('quantidade', quantidade);
            
            fetch('ui.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarMensagem(data.message, 'success');
                    setTimeout(() => {
                        fecharModal();
                        carregarMovimentacoesRecentes();
                    }, 2000);
                } else {
                    mostrarMensagem(data.message, 'error');
                }
            })
            .catch(error => {
                mostrarMensagem('Erro ao registrar movimentação', 'error');
            })
            .finally(() => {
                // Liberar a proteção após 2 segundos
                setTimeout(() => {
                    processandoAcao = false;
                }, 2000);
            });
        }
        
        function fecharModal() {
            document.getElementById('modalMovimentacao').style.display = 'none';
            produtoAtual = null;
            codigoDigitado = '';
            processandoAcao = false;
            document.getElementById('codigoProduto').value = '';
            document.getElementById('quantidade').value = '';
            document.getElementById('productInfo').style.display = 'none';
            document.getElementById('quantidadeGroup').style.display = 'none';
            document.getElementById('btnConfirmar').style.display = 'none';
            document.getElementById('message').style.display = 'none';
        }
        
        function mostrarMensagem(texto, tipo) {
            const message = document.getElementById('message');
            message.textContent = texto;
            message.className = `message ${tipo}`;
            message.style.display = 'block';
        }
        
        // Event listeners
        // Event listener para busca em tempo real
        document.getElementById('codigoProduto').addEventListener('input', function(e) {
            codigoDigitado = e.target.value;
            
            // Limpar produto atual se mudou o código
            produtoAtual = null;
            document.getElementById('productInfo').style.display = 'none';
            document.getElementById('quantidadeGroup').style.display = 'none';
            document.getElementById('btnConfirmar').style.display = 'none';
            document.getElementById('message').style.display = 'none';
            
            // Buscar produto automaticamente
            clearTimeout(searchTimeout);
            buscarProdutoAutomatico();
        });
        
        document.getElementById('codigoProduto').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && produtoAtual) {
                document.getElementById('quantidade').focus();
            }
        });
        
        document.getElementById('quantidade').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                confirmarMovimentacao();
            }
        });
        
        // Carregar movimentações ao iniciar
        document.addEventListener('DOMContentLoaded', function() {
            carregarMovimentacoesRecentes();
        });
        
        // Auto-refresh das movimentações a cada 30 segundos
        setInterval(carregarMovimentacoesRecentes, 30000);
    </script>
</body>
</html>
