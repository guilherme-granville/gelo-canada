<?php
/**
 * Painel Administrativo
 * Gestão completa do sistema de controle de estoque
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

// Verificar se é admin
if ($usuarioAtual['perfil'] !== 'admin') {
    header('Location: ui.php');
    exit();
}

// Processar logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Obter dados para dashboard
$estatisticas = $produto->getEstatisticas();
$ultimasMovimentacoes = $movimentacao->getUltimasMovimentacoes(5);
$estoqueBaixo = $produto->getEstoqueBaixo();
$estoqueZero = $produto->getEstoqueZero();

// Estatísticas de movimentações
$hoje = date('Y-m-d');
$estatisticasMov = $movimentacao->getEstatisticas($hoje, $hoje);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - Controle de Estoque</title>
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
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #333;
        }
        
        .card-icon {
            font-size: 2rem;
            opacity: 0.7;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-color: #ffc107;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }
        
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        
        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }
        
        .table tr:hover {
            background: #f8f9fa;
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .menu-item {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            text-decoration: none;
            color: #333;
        }
        
        .menu-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        
        .menu-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        
        .menu-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .menu-description {
            color: #666;
            font-size: 0.9rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .status-normal {
            background: #d4edda;
            color: #155724;
        }
        
        .status-baixo {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-zero {
            background: #f8d7da;
            color: #721c24;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .menu-grid {
                grid-template-columns: 1fr;
            }
            
            .navbar-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .quick-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="navbar-brand">
                <i class="fas fa-warehouse"></i> Controle de Estoque
            </div>
            <div class="navbar-user">
                <span>Bem-vindo, <?php echo htmlspecialchars($usuarioAtual['nome']); ?></span>
                <a href="?logout=1" class="btn btn-sm" style="background: rgba(255,255,255,0.2); color: white;">
                    <i class="fas fa-sign-out-alt"></i> Sair
                </a>
            </div>
        </div>
    </nav>
    
    <div class="container">
        <h1 style="margin-bottom: 2rem; color: #333;">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </h1>
        
        <!-- Estatísticas Gerais -->
        <div class="dashboard-grid">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Total de Produtos</div>
                    <div class="card-icon">📦</div>
                </div>
                <div class="stat-number"><?php echo $estatisticas['total_produtos']; ?></div>
                <div class="stat-label">Produtos cadastrados no sistema</div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Produtos Ativos</div>
                    <div class="card-icon">✅</div>
                </div>
                <div class="stat-number"><?php echo $estatisticas['produtos_ativos']; ?></div>
                <div class="stat-label">Produtos disponíveis para movimentação</div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Movimentações Hoje</div>
                    <div class="card-icon">📊</div>
                </div>
                <div class="stat-number"><?php echo $estatisticasMov['total_movimentacoes']; ?></div>
                <div class="stat-label">Registros de entrada/saída hoje</div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Estoque Baixo</div>
                    <div class="card-icon">⚠️</div>
                </div>
                <div class="stat-number"><?php echo $estatisticas['produtos_estoque_baixo']; ?></div>
                <div class="stat-label">Produtos com estoque mínimo atingido</div>
            </div>
        </div>
        
        <!-- Alertas -->
        <?php if (!empty($estoqueBaixo)): ?>
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Atenção!</strong>
            <?php echo count($estoqueBaixo); ?> produto(s) com estoque baixo.
        </div>
        <?php endif; ?>
        
        <?php if (!empty($estoqueZero)): ?>
        <div class="alert alert-danger">
            <strong><i class="fas fa-times-circle"></i> Crítico!</strong>
            <?php echo count($estoqueZero); ?> produto(s) com estoque zerado.
        </div>
        <?php endif; ?>
        
        <!-- Ações Rápidas -->
        <div class="quick-actions">
            <a href="admin/produtos.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Novo Produto
            </a>
            <a href="admin/movimentacoes.php" class="btn btn-success">
                <i class="fas fa-exchange-alt"></i> Nova Movimentação
            </a>
            <a href="admin/relatorios.php" class="btn btn-warning">
                <i class="fas fa-chart-bar"></i> Relatórios
            </a>
            <a href="admin/usuarios.php" class="btn btn-primary">
                <i class="fas fa-users"></i> Usuários
            </a>
        </div>
        
        <!-- Últimas Movimentações -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Últimas Movimentações</div>
                <a href="admin/movimentacoes.php" class="btn btn-sm btn-primary">Ver Todas</a>
            </div>
            
            <?php if (!empty($ultimasMovimentacoes)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Data/Hora</th>
                        <th>Produto</th>
                        <th>Tipo</th>
                        <th>Quantidade</th>
                        <th>Usuário</th>
                        <th>Origem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimasMovimentacoes as $mov): ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($mov['criado_em'])); ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($mov['produto_nome']); ?></strong><br>
                            <small><?php echo htmlspecialchars($mov['produto_codigo']); ?></small>
                        </td>
                        <td>
                            <?php if ($mov['tipo'] === 'ENTRADA'): ?>
                                <span class="status-badge status-normal">📥 Entrada</span>
                            <?php elseif ($mov['tipo'] === 'SAIDA'): ?>
                                <span class="status-badge status-baixo">📤 Saída</span>
                            <?php else: ?>
                                <span class="status-badge status-zero">⚙️ Ajuste</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $mov['quantidade']; ?></td>
                        <td><?php echo htmlspecialchars($mov['usuario_nome'] ?? 'Sistema'); ?></td>
                        <td>
                            <?php
                            $origemLabels = [
                                'pi' => 'Raspberry Pi',
                                'cel' => 'Celular',
                                'pc' => 'Computador'
                            ];
                            echo $origemLabels[$mov['origem']] ?? $mov['origem'];
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align: center; color: #666; padding: 2rem;">Nenhuma movimentação registrada ainda.</p>
            <?php endif; ?>
        </div>
        
        <!-- Menu Principal -->
        <div class="menu-grid">
            <a href="admin/produtos.php" class="menu-item">
                <div class="menu-icon">📦</div>
                <div class="menu-title">Produtos</div>
                <div class="menu-description">Cadastrar e gerenciar produtos</div>
            </a>
            
            <a href="admin/movimentacoes.php" class="menu-item">
                <div class="menu-icon">📊</div>
                <div class="menu-title">Movimentações</div>
                <div class="menu-description">Histórico de entradas e saídas</div>
            </a>
            
            <a href="admin/estoque.php" class="menu-item">
                <div class="menu-icon">📈</div>
                <div class="menu-title">Estoque</div>
                <div class="menu-description">Visualizar situação do estoque</div>
            </a>
            
            <a href="admin/relatorios.php" class="menu-item">
                <div class="menu-icon">📋</div>
                <div class="menu-title">Relatórios</div>
                <div class="menu-description">Relatórios e exportações</div>
            </a>
            
            <a href="admin/usuarios.php" class="menu-item">
                <div class="menu-icon">👥</div>
                <div class="menu-title">Usuários</div>
                <div class="menu-description">Gerenciar usuários do sistema</div>
            </a>
            
            <a href="admin/configuracoes.php" class="menu-item">
                <div class="menu-icon">⚙️</div>
                <div class="menu-title">Configurações</div>
                <div class="menu-description">Configurações do sistema</div>
            </a>
            
            <a href="admin/backup.php" class="menu-item">
                <div class="menu-icon">💾</div>
                <div class="menu-title">Backup</div>
                <div class="menu-description">Backup e restauração</div>
            </a>
            
            <a href="admin/logs.php" class="menu-item">
                <div class="menu-icon">📝</div>
                <div class="menu-title">Logs</div>
                <div class="menu-description">Logs do sistema</div>
            </a>
        </div>
    </div>
    
    <script>
        // Auto-refresh a cada 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Atualizar estatísticas via AJAX (opcional)
        function atualizarEstatisticas() {
            // Implementar atualização via AJAX se necessário
        }
    </script>
</body>
</html>
