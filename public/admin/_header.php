<?php
// Header reutilizável para área admin
// Requer que $usuarioAtual esteja definido na página que inclui
// Opcional: definir $activePage com um destes valores: estoque, produtos, movimentacoes, relatorios, usuarios, configuracoes, backup, logs

$activePage = $activePage ?? '';
?>
<style>
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
    .btn { padding: 0.5rem 1rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; text-decoration: none; display: inline-block; }
    .btn-danger { background: #e74c3c; color: #fff; }
</style>
<nav class="navbar">
    <div class="navbar-content">
        <div class="navbar-brand">
            <i class="fas fa-boxes"></i> Controle de Estoque
        </div>
        <ul class="navbar-nav">
            <li><a href="../admin.php" class="<?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="estoque.php" class="<?php echo $activePage === 'estoque' ? 'active' : ''; ?>">Estoque</a></li>
            <li><a href="produtos.php" class="<?php echo $activePage === 'produtos' ? 'active' : ''; ?>">Produtos</a></li>
            <li><a href="movimentacoes.php" class="<?php echo $activePage === 'movimentacoes' ? 'active' : ''; ?>">Movimentações</a></li>
            <li><a href="relatorios.php" class="<?php echo $activePage === 'relatorios' ? 'active' : ''; ?>">Relatórios</a></li>
            <li><a href="usuarios.php" class="<?php echo $activePage === 'usuarios' ? 'active' : ''; ?>">Usuários</a></li>
            <li><a href="configuracoes.php" class="<?php echo $activePage === 'configuracoes' ? 'active' : ''; ?>">Configurações</a></li>
            <li><a href="backup.php" class="<?php echo $activePage === 'backup' ? 'active' : ''; ?>">Backup</a></li>
            <li><a href="logs.php" class="<?php echo $activePage === 'logs' ? 'active' : ''; ?>">Logs</a></li>
        </ul>
        <div class="navbar-user">
            <span>Olá, <?php echo htmlspecialchars($usuarioAtual['nome'] ?? ''); ?></span>
            <a href="?logout=1" class="btn btn-danger">
                <i class="fas fa-sign-out-alt"></i> Sair
            </a>
        </div>
    </div>
    </nav>


