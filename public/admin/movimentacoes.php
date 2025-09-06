<?php
/**
 * Gestão de Movimentações - Painel Administrativo
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

// Filtros
$filtroTipo = $_GET['tipo'] ?? '';
$filtroDataInicio = $_GET['data_inicio'] ?? '';
$filtroDataFim = $_GET['data_fim'] ?? '';
$filtroProduto = $_GET['produto_id'] ?? '';

// Obter dados
$movimentacoes = $movimentacao->listar([
    'tipo' => $filtroTipo,
    'data_inicio' => $filtroDataInicio,
    'data_fim' => $filtroDataFim,
    'produto_id' => $filtroProduto
]);

$produtos = $produto->listar();
$estatisticas = $movimentacao->getEstatisticas($filtroDataInicio, $filtroDataFim);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Movimentações - Administração</title>
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
        
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        
        .filters h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }
        
        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
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
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: bold;
        }
        
        .badge-entrada {
            background: #d5f4e6;
            color: #27ae60;
        }
        
        .badge-saida {
            background: #fadbd8;
            color: #e74c3c;
        }
        
        .badge-ajuste {
            background: #fef9e7;
            color: #f39c12;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .table {
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'movimentacoes'; include __DIR__ . '/_header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>Movimentações</h1>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-primary" onclick="window.location.href='estoque.php'">
                    <i class="fas fa-plus"></i> Nova Movimentação
                </button>
                <button class="btn btn-success" onclick="exportarMovimentacoes()">
                    <i class="fas fa-download"></i> Exportar
                </button>
                <button class="btn btn-info" onclick="abrirModal('filtrosAvancados')">
                    <i class="fas fa-filter"></i> Filtros Avançados
                </button>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $estatisticas['total_movimentacoes']; ?></div>
                <div class="stat-label">Total de Movimentações</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estatisticas['entradas']; ?></div>
                <div class="stat-label">Entradas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estatisticas['saidas']; ?></div>
                <div class="stat-label">Saídas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $estatisticas['ajustes']; ?></div>
                <div class="stat-label">Ajustes</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($movimentacoes); ?></div>
                <div class="stat-label">Movimentações Filtradas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $valorTotal = 0;
                    foreach ($movimentacoes as $m) {
                        $valorTotal += $m['quantidade'] * ($m['preco_unitario'] ?? 0);
                    }
                    echo 'R$ ' . number_format($valorTotal, 2, ',', '.');
                    ?>
                </div>
                <div class="stat-label">Valor Total</div>
            </div>
        </div>
        
        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label class="form-label">Tipo:</label>
                        <select name="tipo" class="form-input">
                            <option value="">Todos os tipos</option>
                            <option value="ENTRADA" <?php echo $filtroTipo === 'ENTRADA' ? 'selected' : ''; ?>>Entrada</option>
                            <option value="SAIDA" <?php echo $filtroTipo === 'SAIDA' ? 'selected' : ''; ?>>Saída</option>
                            <option value="AJUSTE" <?php echo $filtroTipo === 'AJUSTE' ? 'selected' : ''; ?>>Ajuste</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Produto:</label>
                        <select name="produto_id" class="form-input">
                            <option value="">Todos os produtos</option>
                            <?php foreach ($produtos as $p): ?>
                                <option value="<?php echo $p['id']; ?>" <?php echo $filtroProduto == $p['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($p['codigo'] . ' - ' . $p['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Data Início:</label>
                        <input type="date" name="data_inicio" class="form-input" value="<?php echo $filtroDataInicio; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Data Fim:</label>
                        <input type="date" name="data_fim" class="form-input" value="<?php echo $filtroDataFim; ?>">
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="movimentacoes.php" class="btn btn-warning">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list"></i> Lista de Movimentações</span>
                <span><?php echo count($movimentacoes); ?> movimentações</span>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Valor Unit.</th>
                            <th>Valor Total</th>
                            <th>Origem</th>
                            <th>Usuário</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimentacoes as $m): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($m['criado_em'])); ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($m['produto_codigo']); ?></strong><br>
                                    <small><?php echo htmlspecialchars($m['produto_nome']); ?></small>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($m['tipo']); ?>">
                                        <?php echo $m['tipo']; ?>
                                    </span>
                                </td>
                                <td><?php echo $m['quantidade']; ?> <?php echo $m['produto_unidade']; ?></td>
                                <td>R$ <?php echo number_format($m['preco_unitario'] ?? 0, 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format(($m['quantidade'] * ($m['preco_unitario'] ?? 0)), 2, ',', '.'); ?></td>
                                <td>
                                    <?php
                                    $origemLabels = [
                                        'pi' => '<i class="fas fa-microchip"></i> Raspberry Pi',
                                        'cel' => '<i class="fas fa-mobile-alt"></i> Celular',
                                        'pc' => '<i class="fas fa-desktop"></i> Computador'
                                    ];
                                    echo $origemLabels[$m['origem']] ?? $m['origem'];
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($m['usuario_nome']); ?></td>
                                <td>
                                    <button class="btn btn-info btn-sm" onclick="verDetalhes(<?php echo $m['id']; ?>)" title="Ver Detalhes">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function exportarMovimentacoes() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'csv');
            window.open('?' + params.toString(), '_blank');
        }

        function abrirModal(tipo) {
            // Implementar modal de filtros avançados
            alert('Modal de filtros avançados será implementado');
        }

        function verDetalhes(id) {
            // Implementar visualização de detalhes
            alert('Detalhes da movimentação ID: ' + id);
        }

        // Auto-refresh a cada 30 segundos
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>

