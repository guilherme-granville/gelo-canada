<?php
/**
 * Logs - Painel Administrativo
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
            case 'limpar_logs':
                $tipo = $_POST['tipo'] ?? 'todos';
                
                if ($tipo === 'todos') {
                    // Limpar todos os logs
                    file_put_contents(__DIR__ . '/../../logs/php_errors.log', '');
                    file_put_contents(__DIR__ . '/../../logs/application.log', '');
                } elseif ($tipo === 'php') {
                    file_put_contents(__DIR__ . '/../../logs/php_errors.log', '');
                } elseif ($tipo === 'app') {
                    file_put_contents(__DIR__ . '/../../logs/application.log', '');
                }
                
                header('Location: logs.php?msg=logs_limpos');
                exit();
                break;
        }
    }
}

// Filtros
$tipoLog = $_GET['tipo'] ?? 'todos';
$nivel = $_GET['nivel'] ?? 'todos';
$dataInicio = $_GET['data_inicio'] ?? '';
$dataFim = $_GET['data_fim'] ?? '';

// Obter logs
$logs = [];

// Logs do PHP
if ($tipoLog === 'todos' || $tipoLog === 'php') {
    $phpLogFile = __DIR__ . '/../../logs/php_errors.log';
    if (file_exists($phpLogFile)) {
        $phpLogs = file($phpLogFile, FILE_IGNORE_NEW_LINES);
        foreach ($phpLogs as $line) {
            if (!empty(trim($line))) {
                $logs[] = [
                    'tipo' => 'PHP',
                    'nivel' => 'ERROR',
                    'mensagem' => $line,
                    'data' => 'N/A',
                    'origem' => 'PHP Error Log'
                ];
            }
        }
    }
}

// Logs da aplicação
if ($tipoLog === 'todos' || $tipoLog === 'app') {
    $appLogFile = __DIR__ . '/../../logs/application.log';
    if (file_exists($appLogFile)) {
        $appLogs = file($appLogFile, FILE_IGNORE_NEW_LINES);
        foreach ($appLogs as $line) {
            if (!empty(trim($line))) {
                // Tentar parsear formato de log estruturado
                if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[(\w+)\] (.+)$/', $line, $matches)) {
                    $logs[] = [
                        'tipo' => 'APP',
                        'nivel' => $matches[2],
                        'mensagem' => $matches[3],
                        'data' => $matches[1],
                        'origem' => 'Application Log'
                    ];
                } else {
                    $logs[] = [
                        'tipo' => 'APP',
                        'nivel' => 'INFO',
                        'mensagem' => $line,
                        'data' => 'N/A',
                        'origem' => 'Application Log'
                    ];
                }
            }
        }
    }
}

// Ordenar por data (mais recente primeiro)
usort($logs, function($a, $b) {
    if ($a['data'] === 'N/A' && $b['data'] === 'N/A') return 0;
    if ($a['data'] === 'N/A') return 1;
    if ($b['data'] === 'N/A') return -1;
    return strtotime($b['data']) - strtotime($a['data']);
});

// Aplicar filtros
if ($nivel !== 'todos') {
    $logs = array_filter($logs, function($log) use ($nivel) {
        return $log['nivel'] === $nivel;
    });
}

// Estatísticas
$totalLogs = count($logs);
$logsErro = count(array_filter($logs, function($log) { return $log['nivel'] === 'ERROR'; }));
$logsWarning = count(array_filter($logs, function($log) { return $log['nivel'] === 'WARNING'; }));
$logsInfo = count(array_filter($logs, function($log) { return $log['nivel'] === 'INFO'; }));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs - Administração</title>
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
        
        .log-entry {
            padding: 1rem;
            border-bottom: 1px solid #ecf0f1;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }
        
        .log-entry:last-child {
            border-bottom: none;
        }
        
        .log-entry:hover {
            background: #f8f9fa;
        }
        
        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .log-tipo {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .log-tipo-php {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .log-tipo-app {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .log-nivel {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        
        .log-nivel-error {
            background: #ffebee;
            color: #c62828;
        }
        
        .log-nivel-warning {
            background: #fff3e0;
            color: #ef6c00;
        }
        
        .log-nivel-info {
            background: #e8f5e8;
            color: #2e7d32;
        }
        
        .log-data {
            color: #7f8c8d;
            font-size: 0.75rem;
        }
        
        .log-mensagem {
            color: #2c3e50;
            word-break: break-all;
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
        
        .alert-warning {
            background: #fef9e7;
            color: #856404;
            border: 1px solid #f39c12;
        }
        
        .danger-zone {
            background: #fadbd8;
            border: 1px solid #e74c3c;
            border-radius: 10px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .danger-zone h3 {
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        
        .danger-zone p {
            color: #721c24;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .log-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'logs'; include __DIR__ . '/_header.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['msg']) {
                    case 'logs_limpos':
                        echo 'Logs limpos com sucesso!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>Logs do Sistema</h1>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-success" onclick="exportarLogs()">
                    <i class="fas fa-download"></i> Exportar Logs
                </button>
                <button class="btn btn-info" onclick="atualizarLogs()">
                    <i class="fas fa-sync"></i> Atualizar
                </button>
                <button class="btn btn-primary" onclick="buscarLogs()">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <button class="btn btn-warning" onclick="limparLogsEspecificos()">
                    <i class="fas fa-broom"></i> Limpeza Seletiva
                </button>
                <button class="btn btn-danger" onclick="limparLogs()">
                    <i class="fas fa-trash"></i> Limpar Tudo
                </button>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $totalLogs; ?></div>
                <div class="stat-label">Total de Logs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $logsErro; ?></div>
                <div class="stat-label">Erros</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $logsWarning; ?></div>
                <div class="stat-label">Avisos</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $logsInfo; ?></div>
                <div class="stat-label">Informações</div>
            </div>
        </div>
        
        <!-- Barra de Busca -->
        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-body">
                <div style="display: flex; gap: 1rem; align-items: center;">
                    <input type="text" id="busca-logs" class="form-input" placeholder="Buscar nos logs..." style="flex: 1;">
                    <button type="button" class="btn btn-primary" onclick="filtrarLogs()">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <button type="button" class="btn btn-warning" onclick="limparBusca()">
                        <i class="fas fa-times"></i> Limpar
                    </button>
                </div>
            </div>
        </div>
        
        <div class="filters">
            <h3><i class="fas fa-filter"></i> Filtros</h3>
            <form method="GET">
                <div class="filter-row">
                    <div class="form-group">
                        <label class="form-label">Tipo de Log:</label>
                        <select name="tipo" class="form-input">
                            <option value="todos" <?php echo $tipoLog === 'todos' ? 'selected' : ''; ?>>Todos os tipos</option>
                            <option value="php" <?php echo $tipoLog === 'php' ? 'selected' : ''; ?>>PHP Errors</option>
                            <option value="app" <?php echo $tipoLog === 'app' ? 'selected' : ''; ?>>Application</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Nível:</label>
                        <select name="nivel" class="form-input">
                            <option value="todos" <?php echo $nivel === 'todos' ? 'selected' : ''; ?>>Todos os níveis</option>
                            <option value="ERROR" <?php echo $nivel === 'ERROR' ? 'selected' : ''; ?>>Erro</option>
                            <option value="WARNING" <?php echo $nivel === 'WARNING' ? 'selected' : ''; ?>>Aviso</option>
                            <option value="INFO" <?php echo $nivel === 'INFO' ? 'selected' : ''; ?>>Informação</option>
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
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <a href="logs.php" class="btn btn-warning">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list"></i> Logs do Sistema</span>
                <span><?php echo $totalLogs; ?> entradas</span>
            </div>
            <div class="card-body">
                <?php if (empty($logs)): ?>
                    <p>Nenhum log encontrado.</p>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-entry">
                            <div class="log-header">
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <span class="log-tipo log-tipo-<?php echo strtolower($log['tipo']); ?>">
                                        <?php echo $log['tipo']; ?>
                                    </span>
                                    <span class="log-nivel log-nivel-<?php echo strtolower($log['nivel']); ?>">
                                        <?php echo $log['nivel']; ?>
                                    </span>
                                </div>
                                <div class="log-data">
                                    <?php echo $log['data']; ?>
                                </div>
                            </div>
                            <div class="log-mensagem">
                                <?php echo htmlspecialchars($log['mensagem']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="danger-zone">
            <h3><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
            <p>Limpar logs irá remover permanentemente todas as entradas de log.</p>
            <p><strong>ATENÇÃO:</strong> Esta ação é irreversível!</p>
        </div>
    </div>
    
    <script>
        function exportarLogs() {
            const params = new URLSearchParams(window.location.search);
            params.set('export', 'txt');
            window.open('?' + params.toString(), '_blank');
        }
        
        function atualizarLogs() {
            location.reload();
        }
        
        function buscarLogs() {
            const termo = document.getElementById('busca-logs').value;
            if (termo) {
                filtrarLogs();
            } else {
                alert('Digite um termo para buscar');
            }
        }
        
        function filtrarLogs() {
            const termo = document.getElementById('busca-logs').value.toLowerCase();
            const linhas = document.querySelectorAll('.log-entry');
            
            linhas.forEach(linha => {
                const texto = linha.textContent.toLowerCase();
                if (termo === '' || texto.includes(termo)) {
                    linha.style.display = 'block';
                } else {
                    linha.style.display = 'none';
                }
            });
        }
        
        function limparBusca() {
            document.getElementById('busca-logs').value = '';
            document.querySelectorAll('.log-entry').forEach(linha => {
                linha.style.display = 'block';
            });
        }
        
        function limparLogsEspecificos() {
            const opcoes = [
                'Apenas erros',
                'Apenas avisos', 
                'Logs antigos (>7 dias)',
                'Logs PHP',
                'Logs da aplicação'
            ];
            
            let escolha = '';
            for (let i = 0; i < opcoes.length; i++) {
                escolha += (i + 1) + '. ' + opcoes[i] + '\n';
            }
            
            const opcao = prompt('Escolha o tipo de limpeza:\n' + escolha);
            if (opcao && opcao >= 1 && opcao <= opcoes.length) {
                if (confirm('Confirma a limpeza seletiva: ' + opcoes[opcao-1] + '?')) {
                    alert('Limpeza seletiva será implementada');
                }
            }
        }
        
        function limparLogs() {
            if (confirm('Tem certeza que deseja limpar todos os logs?')) {
                if (confirm('Esta ação é IRREVERSÍVEL. Confirma a limpeza?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="acao" value="limpar_logs">
                        <input type="hidden" name="tipo" value="todos">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
        
        // Auto-refresh a cada 10 segundos
        setInterval(() => {
            if (!document.getElementById('busca-logs').value) {
                location.reload();
            }
        }, 10000);
    </script>
</body>
</html>

