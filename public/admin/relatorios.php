<?php
/**
 * Relatórios - Painel Administrativo
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

// Filtros padrão
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01'); // Primeiro dia do mês
$dataFim = $_GET['data_fim'] ?? date('Y-m-d'); // Hoje
$tipoRelatorio = $_GET['tipo'] ?? 'movimentacoes';

// Obter dados
$estatisticas = $movimentacao->getEstatisticas($dataInicio, $dataFim);
$produtos = $produto->listar();
$movimentacoes = $movimentacao->listar([
    'data_inicio' => $dataInicio,
    'data_fim' => $dataFim
]);

// Calcular dados para gráficos
$dadosGrafico = [];
$labels = [];
$entradas = [];
$saidas = [];

for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-$i days"));
    $labels[] = date('d/m', strtotime($data));
    
    $movDia = $movimentacao->listar([
        'data_inicio' => $data,
        'data_fim' => $data
    ]);
    
    $entradasDia = 0;
    $saidasDia = 0;
    
    foreach ($movDia as $mov) {
        if ($mov['tipo'] === 'ENTRADA') {
            $entradasDia += $mov['quantidade'];
        } elseif ($mov['tipo'] === 'SAIDA') {
            $saidasDia += $mov['quantidade'];
        }
    }
    
    $entradas[] = $entradasDia;
    $saidas[] = $saidasDia;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Administração</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .table-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .table-header {
            background: #34495e;
            color: white;
            padding: 1rem 1.5rem;
            font-weight: bold;
        }
        
        .table-body {
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
        
        @media (max-width: 768px) {
            .charts-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'relatorios'; include __DIR__ . '/_header.php'; ?>
    
    <div class="container">
        <div class="header">
            <h1>Relatórios</h1>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-success" onclick="exportarPDF()">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
                <button class="btn btn-primary" onclick="exportarExcel()">
                    <i class="fas fa-file-excel"></i> Exportar Excel
                </button>
                <button class="btn btn-info" onclick="imprimirRelatorio()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
                <button class="btn btn-warning" onclick="agendarRelatorio()">
                    <i class="fas fa-clock"></i> Agendar
                </button>
            </div>
        </div>
        
        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label class="form-label">Tipo de Relatório:</label>
                        <select name="tipo" class="form-input">
                            <option value="movimentacoes" <?php echo $tipoRelatorio === 'movimentacoes' ? 'selected' : ''; ?>>Movimentações</option>
                            <option value="estoque" <?php echo $tipoRelatorio === 'estoque' ? 'selected' : ''; ?>>Estoque Atual</option>
                            <option value="produtos" <?php echo $tipoRelatorio === 'produtos' ? 'selected' : ''; ?>>Produtos</option>
                            <option value="financeiro" <?php echo $tipoRelatorio === 'financeiro' ? 'selected' : ''; ?>>Relatório Financeiro</option>
                            <option value="performance" <?php echo $tipoRelatorio === 'performance' ? 'selected' : ''; ?>>Performance</option>
                            <option value="usuarios" <?php echo $tipoRelatorio === 'usuarios' ? 'selected' : ''; ?>>Atividade de Usuários</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Data Início:</label>
                        <input type="date" name="data_inicio" class="form-input" value="<?php echo $dataInicio; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Data Fim:</label>
                        <input type="date" name="data_fim" class="form-input" value="<?php echo $dataFim; ?>">
                    </div>
                </div>
                
                <div style="text-align: right;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Gerar Relatório
                    </button>
                </div>
            </form>
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
        </div>
        
        <div class="charts-grid">
            <div class="chart-card">
                <div class="chart-title">Movimentações por Dia (Últimos 7 dias)</div>
                <div class="chart-container">
                    <canvas id="movimentacoesChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-title">Distribuição por Tipo</div>
                <div class="chart-container">
                    <canvas id="tiposChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="table-card">
            <div class="table-header">
                <span><i class="fas fa-list"></i> Últimas Movimentações</span>
            </div>
            <div class="table-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Produto</th>
                            <th>Tipo</th>
                            <th>Quantidade</th>
                            <th>Origem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($movimentacoes, 0, 10) as $m): ?>
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
                                <td><?php echo $m['origem']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
        // Gráfico de movimentações por dia
        const ctx1 = document.getElementById('movimentacoesChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Entradas',
                    data: <?php echo json_encode($entradas); ?>,
                    borderColor: '#27ae60',
                    backgroundColor: 'rgba(39, 174, 96, 0.1)',
                    tension: 0.4
                }, {
                    label: 'Saídas',
                    data: <?php echo json_encode($saidas); ?>,
                    borderColor: '#e74c3c',
                    backgroundColor: 'rgba(231, 76, 60, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
        
        // Gráfico de distribuição por tipo
        const ctx2 = document.getElementById('tiposChart').getContext('2d');
        new Chart(ctx2, {
            type: 'doughnut',
            data: {
                labels: ['Entradas', 'Saídas', 'Ajustes'],
                datasets: [{
                    data: [
                        <?php echo $estatisticas['entradas']; ?>,
                        <?php echo $estatisticas['saidas']; ?>,
                        <?php echo $estatisticas['ajustes']; ?>
                    ],
                    backgroundColor: [
                        '#27ae60',
                        '#e74c3c',
                        '#f39c12'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
        
        function imprimirRelatorio() {
            window.print();
        }

        function exportarPDF() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'pdf');
            window.open('?' + params.toString(), '_blank');
        }

        function exportarExcel() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'excel');
            window.open('?' + params.toString(), '_blank');
        }

        function agendarRelatorio() {
            alert('Funcionalidade de agendamento será implementada');
        }

        // Atualizar gráficos quando filtros mudarem
        document.querySelector('form').addEventListener('submit', function() {
            // Mostrar loading
            document.body.style.cursor = 'wait';
        });
        
        // Auto-refresh dos dados a cada 60 segundos
        setInterval(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>

