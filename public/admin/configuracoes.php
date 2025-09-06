<?php
/**
 * Configurações - Painel Administrativo
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
            case 'atualizar_configuracoes':
                // Aqui você pode implementar a lógica para salvar configurações
                header('Location: configuracoes.php?msg=configuracoes_atualizadas');
                exit();
                break;
        }
    }
}

// Obter informações do sistema
$infoSistema = [
    'versao_php' => phpversion(),
    'versao_mysql' => $db->fetchOne("SELECT VERSION() as version")['version'],
    'tamanho_db' => $db->fetchOne("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'DB Size in MB' FROM information_schema.tables WHERE table_schema = ?", [DB_NAME])['DB Size in MB'],
    'uptime' => 'N/A', // Implementar se necessário
    'memoria_limite' => ini_get('memory_limit'),
    'upload_max' => ini_get('upload_max_filesize'),
    'post_max' => ini_get('post_max_size'),
    'timezone' => date_default_timezone_get()
];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Administração</title>
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
        
        .config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
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
        
        .form-textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            resize: vertical;
            min-height: 100px;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-checkbox input {
            width: auto;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .info-label {
            font-size: 0.875rem;
            color: #7f8c8d;
            margin-bottom: 0.25rem;
        }
        
        .info-value {
            font-size: 1.1rem;
            font-weight: bold;
            color: #2c3e50;
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
        
        .alert-info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #17a2b8;
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
            .config-grid {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php $activePage = 'configuracoes'; include __DIR__ . '/_header.php'; ?>
    
    <div class="container">
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['msg']) {
                    case 'configuracoes_atualizadas':
                        echo 'Configurações atualizadas com sucesso!';
                        break;
                }
                ?>
            </div>
        <?php endif; ?>
        
        <div class="header">
            <h1>Configurações do Sistema</h1>
            <div style="display: flex; gap: 1rem;">
                <button class="btn btn-success" onclick="salvarTodasConfiguracoes()">
                    <i class="fas fa-save"></i> Salvar Tudo
                </button>
                <button class="btn btn-warning" onclick="restaurarPadroes()">
                    <i class="fas fa-undo"></i> Restaurar Padrões
                </button>
                <button class="btn btn-info" onclick="exportarConfiguracoes()">
                    <i class="fas fa-download"></i> Exportar Config
                </button>
            </div>
        </div>
        
        <div class="config-grid">
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-cog"></i> Configurações Gerais</span>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="acao" value="atualizar_configuracoes">
                        
                        <div class="form-group">
                            <label class="form-label">Nome da Empresa:</label>
                            <input type="text" name="empresa_nome" class="form-input" value="Gelo Canada" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Timezone:</label>
                            <select name="timezone" class="form-input">
                                <option value="America/Sao_Paulo" selected>America/Sao_Paulo</option>
                                <option value="America/New_York">America/New_York</option>
                                <option value="Europe/London">Europe/London</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Idioma:</label>
                            <select name="idioma" class="form-input">
                                <option value="pt-BR" selected>Português (Brasil)</option>
                                <option value="en-US">English (US)</option>
                                <option value="es-ES">Español</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Moeda:</label>
                            <select name="moeda" class="form-input">
                                <option value="BRL" selected>Real Brasileiro (R$)</option>
                                <option value="USD">Dólar Americano ($)</option>
                                <option value="EUR">Euro (€)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Formato de Data:</label>
                            <select name="formato_data" class="form-input">
                                <option value="d/m/Y" selected>DD/MM/AAAA</option>
                                <option value="m/d/Y">MM/DD/AAAA</option>
                                <option value="Y-m-d">AAAA-MM-DD</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-checkbox">
                                <input type="checkbox" name="manutencao" id="manutencao">
                                <label for="manutencao">Modo de manutenção</label>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-checkbox">
                                <input type="checkbox" name="debug" id="debug">
                                <label for="debug">Modo Debug</label>
                            </div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 1.5rem;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Salvar Configurações
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <span><i class="fas fa-database"></i> Configurações de Banco</span>
                </div>
                <div class="card-body">
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Tipo de Banco</div>
                            <div class="info-value"><?php echo DB_TYPE; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Host</div>
                            <div class="info-value"><?php echo DB_HOST; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Nome do Banco</div>
                            <div class="info-value"><?php echo DB_NAME; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Usuário</div>
                            <div class="info-value"><?php echo DB_USER; ?></div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Tamanho do Banco</div>
                            <div class="info-value"><?php echo $infoSistema['tamanho_db']; ?> MB</div>
                        </div>
                        <div class="info-item">
                            <div class="info-label">Charset</div>
                            <div class="info-value"><?php echo DB_CHARSET; ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Configurações de Notificações -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-bell"></i> Notificações</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="atualizar_notificacoes">
                    
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" name="notif_estoque_baixo" id="notif_estoque_baixo" checked>
                            <label for="notif_estoque_baixo">Notificar estoque baixo</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" name="notif_estoque_zero" id="notif_estoque_zero" checked>
                            <label for="notif_estoque_zero">Notificar estoque zerado</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Email para Notificações:</label>
                        <input type="email" name="email_notif" class="form-input" value="admin@gelocanada.com">
                    </div>
                    
                    <div style="text-align: right; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar Notificações
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Configurações de Segurança -->
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-shield-alt"></i> Segurança</span>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="acao" value="atualizar_seguranca">
                    
                    <div class="form-group">
                        <label class="form-label">Tempo de Sessão (minutos):</label>
                        <input type="number" name="session_timeout" class="form-input" value="60" min="5" max="480">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Tentativas de Login:</label>
                        <input type="number" name="max_login_attempts" class="form-input" value="3" min="1" max="10">
                    </div>
                    
                    <div class="form-group">
                        <div class="form-checkbox">
                            <input type="checkbox" name="force_https" id="force_https">
                            <label for="force_https">Forçar HTTPS</label>
                        </div>
                    </div>
                    
                    <div style="text-align: right; margin-top: 1.5rem;">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Salvar Segurança
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <span><i class="fas fa-info-circle"></i> Informações do Sistema</span>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">Versão do PHP</div>
                        <div class="info-value"><?php echo $infoSistema['versao_php']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Versão do MySQL</div>
                        <div class="info-value"><?php echo $infoSistema['versao_mysql']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Memória Limite</div>
                        <div class="info-value"><?php echo $infoSistema['memoria_limite']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Upload Máximo</div>
                        <div class="info-value"><?php echo $infoSistema['upload_max']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">POST Máximo</div>
                        <div class="info-value"><?php echo $infoSistema['post_max']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Timezone</div>
                        <div class="info-value"><?php echo $infoSistema['timezone']; ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="danger-zone">
            <h3><i class="fas fa-exclamation-triangle"></i> Zona de Perigo</h3>
            <p>As ações abaixo são irreversíveis e podem causar perda de dados.</p>
            
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <button class="btn btn-warning" onclick="limparLogs()">
                    <i class="fas fa-trash"></i> Limpar Logs
                </button>
                <button class="btn btn-warning" onclick="otimizarBanco()">
                    <i class="fas fa-tools"></i> Otimizar Banco
                </button>
                <button class="btn btn-danger" onclick="resetarSistema()">
                    <i class="fas fa-bomb"></i> Resetar Sistema
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function limparLogs() {
            if (confirm('Tem certeza que deseja limpar todos os logs?')) {
                alert('Funcionalidade será implementada');
            }
        }
        
        function otimizarBanco() {
            if (confirm('Tem certeza que deseja otimizar o banco de dados?')) {
                alert('Funcionalidade será implementada');
            }
        }
        
        function resetarSistema() {
            if (confirm('ATENÇÃO: Esta ação irá resetar todo o sistema e apagar todos os dados. Tem certeza?')) {
                if (confirm('Esta ação é IRREVERSÍVEL. Digite "CONFIRMAR" para continuar:')) {
                    alert('Funcionalidade será implementada');
                }
            }
        }
        
        function salvarTodasConfiguracoes() {
            if (confirm('Salvar todas as configurações?')) {
                // Submeter todos os formulários
                document.querySelectorAll('form').forEach(form => {
                    form.submit();
                });
            }
        }
        
        function restaurarPadroes() {
            if (confirm('Restaurar todas as configurações para os valores padrão?')) {
                alert('Funcionalidade será implementada');
            }
        }
        
        function exportarConfiguracoes() {
            // Criar arquivo de configuração para download
            const config = {
                timestamp: new Date().toISOString(),
                empresa: 'Gelo Canada',
                configuracoes: {
                    timezone: 'America/Sao_Paulo',
                    idioma: 'pt-BR',
                    moeda: 'BRL',
                    formato_data: 'd/m/Y'
                }
            };
            
            const blob = new Blob([JSON.stringify(config, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'configuracoes_' + new Date().toISOString().split('T')[0] + '.json';
            a.click();
        }
    </script>
</body>
</html>

