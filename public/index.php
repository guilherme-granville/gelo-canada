<?php
/**
 * Página de Índice
 * Redireciona para a interface apropriada baseada no contexto
 */

require_once __DIR__ . '/../config/config.php';

// Verificar se é uma requisição do Totem (Raspberry Pi)
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$isTotem = strpos($userAgent, 'Raspberry') !== false || 
           strpos($userAgent, 'Pi') !== false ||
           isset($_GET['totem']);

// Verificar se é uma requisição mobile
$isMobile = preg_match('/(android|iphone|ipad|mobile)/i', $userAgent) || 
            isset($_GET['mobile']);

// Verificar se é uma requisição de API
$isApi = strpos($_SERVER['REQUEST_URI'], '/api/') !== false;

// Se for API, não redirecionar
if ($isApi) {
    return false;
}

// Redirecionar baseado no contexto
if ($isTotem) {
    // Totem (Raspberry Pi)
    header('Location: totem.php');
    exit();
} elseif ($isMobile) {
    // Interface mobile para entregadores
    header('Location: ui.php');
    exit();
} else {
    // Interface desktop - verificar se está logado
    session_start();
    
    if (isset($_SESSION['usuario_id'])) {
        // Usuário logado - redirecionar baseado no perfil
        require_once __DIR__ . '/../app/core/Database.php';
        require_once __DIR__ . '/../app/core/Usuario.php';
        
        try {
            $usuario = new Usuario();
            $usuarioAtual = $usuario->buscarPorId($_SESSION['usuario_id']);
            
            if ($usuarioAtual && $usuarioAtual['perfil'] === 'admin') {
                header('Location: admin.php');
            } else {
                header('Location: ui.php');
            }
        } catch (Exception $e) {
            // Em caso de erro, ir para login
            header('Location: login.php');
        }
    } else {
        // Usuário não logado
        header('Location: login.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Controle de Estoque</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 50px;
            background: #f5f7fa;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        p {
            color: #666;
            margin-bottom: 30px;
        }
        .links {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .link {
            display: inline-block;
            padding: 15px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.3s ease;
        }
        .link:hover {
            background: #5a6fd8;
        }
        .link.totem {
            background: #28a745;
        }
        .link.totem:hover {
            background: #218838;
        }
        .link.mobile {
            background: #fd7e14;
        }
        .link.mobile:hover {
            background: #e8690b;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎯 Sistema de Controle de Estoque</h1>
        <p>Escolha a interface que deseja acessar:</p>
        
        <div class="links">
            <a href="login.php" class="link">
                <strong>👨‍💼 Administração</strong><br>
                <small>Painel completo</small>
            </a>
            
            <a href="ui.php" class="link mobile">
                <strong>📱 Entregadores</strong><br>
                <small>Interface mobile</small>
            </a>
            
            <a href="totem.php" class="link totem">
                <strong>🖥️ Totem</strong><br>
                <small>Raspberry Pi</small>
            </a>
        </div>
        
        <p style="margin-top: 30px; font-size: 0.9rem; color: #999;">
            Sistema desenvolvido para fábrica de gelo<br>
            Suporte offline-first com sincronização automática
        </p>
    </div>
</body>
</html>
