<?php
/**
 * Backup - Painel Administrativo
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
            case 'criar_backup':
                $tipo = $_POST['tipo'] ?? 'completo';
                $nomeArquivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $caminhoBackup = __DIR__ . '/../../backups/' . $nomeArquivo;
                
                // Criar diretório de backup se não existir
                if (!is_dir(__DIR__ . '/../../backups')) {
                    mkdir(__DIR__ . '/../../backups', 0755, true);
                }
                
                // Comando mysqldump
                $comando = "mysqldump -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASS . " " . DB_NAME . " > " . $caminhoBackup;
                
                // Executar backup
                exec($comando, $output, $returnCode);
                
                if ($returnCode === 0) {
                    header('Location: backup.php?msg=backup_criado&arquivo=' . urlencode($nomeArquivo));
                } else {
                    header('Location: backup.php?msg=erro_backup');
                }
                exit();
                break;
                
            case 'restaurar_backup':
                $arquivo = $_POST['arquivo'];
                $caminhoArquivo = __DIR__ . '/../../backups/' . $arquivo;
                
                if (file_exists($caminhoArquivo)) {
                    $comando = "mysql -h " . DB_HOST . " -u " . DB_USER . " -p" . DB_PASS . " " . DB_NAME . " < " . $caminhoArquivo;
                    exec($comando, $output, $returnCode);
                    
                    if ($returnCode === 0) {
                        header('Location: backup.php?msg=backup_restaurado');
                    } else {
                        header('Location: backup.php?msg=erro_restauracao');
                    }
                } else {
                    header('Location: backup.php?msg=arquivo_nao_encontrado');
                }
                exit();
                break;
                
            case 'excluir_backup':
                $arquivo = $_POST['arquivo'];
                $caminhoArquivo = __DIR__ . '/../../backups/' . $arquivo;
                
                if (file_exists($caminhoArquivo)) {
                    unlink($caminhoArquivo);
                    header('Location: backup.php?msg=backup_excluido');
                } else {
                    header('Location: backup.php?msg=arquivo_nao_encontrado');
                }
                exit();
                break;
        }
    }
}

// Listar arquivos de backup
$diretorioBackup = __DIR__ . '/../../backups/';
$backups = [];

if (is_dir($diretorioBackup)) {
    $arquivos = scandir($diretorioBackup);
    foreach ($arquivos as $arquivo) {
        if (pathinfo($arquivo, PATHINFO_EXTENSION) === 'sql') {
            $caminhoCompleto = $diretorioBackup . $arquivo;
            $backups[] = [
                'nome' => $arquivo,
                'tamanho' => filesize($caminhoCompleto),
                'data' => filemtime($caminhoCompleto)
            ];
        }
    }
    
    // Ordenar por data (mais recente primeiro)
    usort($backups, function($a, $b) {
        return $b['data'] - $a['data'];
    });
}

// Calcular tamanho total dos backups
$tamanhoTotal = array_sum(array_column($backups, 'tamanho'));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - Administração</title>
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
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
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
        
        .actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
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
        
        .alert-warning {
            background: #fef9e7;
            color: #856404;
            border: 1px solid #f39c12;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: #ecf0f1;
            border-radius: 10px;
            overflow: hidden;
            margin: 1rem 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            transition: width 0.3s ease;
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
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'backup'; include __DIR__ . '/_header.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert <?php echo strpos($_GET['msg'], 'erro') === 0 ? 'alert-error' : 'alert-success'; ?>">
                <?php
                switch ($_GET['msg']) {
                    case 'backup_criado':
                        echo 'Backup criado com sucesso! Arquivo: ' . htmlspecialchars($_GET['arquivo']);
                        break;
                    case 'backup_restaurado':
                        echo 'Backup restaurado com sucesso!';
                        break;
                    case 'backup_excluido':
                        echo 'Backup excluído com sucesso!';
                        break;
                    case 'erro_backup':
                        echo 'Erro ao criar backup. Verifique as permissões.';
                        break;
                    case 'erro_restauracao':
                        echo 'Erro ao restaurar backup. Verifique o arquivo.';
                        break;
                    case 'arquivo_nao_encontrado':
                        echo 'Arquivo não encontrado.';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>Backup e Restauração</h1>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-primary" onclick="criarBackupRapido()">
                    <i class="fas fa-plus"></i> Backup Rápido
                </button>
                <button class="btn btn-success" onclick="agendarBackup()">
                    <i class="fas fa-clock"></i> Agendar Backup
                </button>
                <button class="btn btn-info" onclick="uploadBackup()">
                    <i class="fas fa-upload"></i> Upload Backup
                </button>
                <button class="btn btn-warning" onclick="sincronizarNuvem()">
                    <i class="fas fa-cloud"></i> Sincronizar
                </button>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($backups); ?></div>
                <div class="stat-label">Total de Backups</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($tamanhoTotal / 1024 / 1024, 2); ?> MB</div>
                <div class="stat-label">Tamanho Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($backups) > 0 ? date('d/m/Y', $backups[0]['data']) : 'N/A'; ?></div>
                <div class="stat-label">Último Backup</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($backups) > 0 ? number_format($backups[0]['tamanho'] / 1024 / 1024, 2) . ' MB' : 'N/A'; ?></div>
                <div class="stat-label">Tamanho Último</div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-database"></i> Criar Novo Backup</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="criar_backup">
                    
                    <div class="form-group">
                        <label class="form-label">Tipo de Backup:</label>
                        <select name="tipo" class="form-input">
                            <option value="completo">Completo (Dados + Estrutura)</option>
                            <option value="dados">Apenas Dados</option>
                            <option value="estrutura">Apenas Estrutura</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Descrição (opcional):</label>
                        <input type="text" name="descricao" class="form-input" placeholder="Ex: Backup antes da atualização">
                    </div>
                    
                    <div style="text-align: right;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Criar Backup
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-list"></i> Backups Disponíveis</span>
            </div>
            <div class="card-body">
                <?php if (empty($backups)): ?>
                    <p>Nenhum backup encontrado.</p>
                <?php else: ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nome do Arquivo</th>
                                <th>Tamanho</th>
                                <th>Data de Criação</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($backup['nome']); ?></strong>
                                    </td>
                                    <td><?php echo number_format($backup['tamanho'] / 1024 / 1024, 2); ?> MB</td>
                                    <td><?php echo date('d/m/Y H:i', $backup['data']); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-info btn-sm" onclick="baixarBackup('<?php echo $backup['nome']; ?>')">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm" onclick="restaurarBackup('<?php echo $backup['nome']; ?>')">
                                            <i class="fas fa-undo"></i>
                                        </button>
                                        <button class="btn btn-danger btn-sm" onclick="excluirBackup('<?php echo $backup['nome']; ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="danger-zone">
            <h3><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
            <p>As ações de restauração irão substituir todos os dados atuais pelos dados do backup selecionado.</p>
            <p><strong>ATENÇÃO:</strong> Esta ação é irreversível e pode causar perda de dados!</p>
        </div>
    </div>
    
    <script>
        function criarBackupRapido() {
            if (confirm('Criar backup rápido do sistema?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="criar_backup">
                    <input type="hidden" name="tipo" value="completo">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function agendarBackup() {
            const horario = prompt('Digite o horário para backup automático (HH:MM):');
            if (horario) {
                alert('Backup agendado para ' + horario + ' (funcionalidade será implementada)');
            }
        }
        
        function uploadBackup() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.sql';
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    alert('Upload de backup: ' + file.name + ' (funcionalidade será implementada)');
                }
            };
            input.click();
        }
        
        function sincronizarNuvem() {
            if (confirm('Sincronizar backups com a nuvem?')) {
                alert('Sincronização com nuvem será implementada');
            }
        }
        
        function baixarBackup(arquivo) {
            window.open('download_backup.php?arquivo=' + encodeURIComponent(arquivo), '_blank');
        }
        
        function restaurarBackup(arquivo) {
            if (confirm('ATENÇÃO: Esta ação irá substituir todos os dados atuais pelos dados do backup "' + arquivo + '". Tem certeza?')) {
                if (confirm('Esta ação é IRREVERSÍVEL. Confirma a restauração?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="acao" value="restaurar_backup">
                        <input type="hidden" name="arquivo" value="${arquivo}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
        
        function excluirBackup(arquivo) {
            if (confirm('Tem certeza que deseja excluir o backup "' + arquivo + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="excluir_backup">
                    <input type="hidden" name="arquivo" value="${arquivo}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

