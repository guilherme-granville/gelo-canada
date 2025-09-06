<?php
/**
 * Página de Login
 * Autenticação de usuários
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Usuario.php';

session_start();

// Se já está logado, redirecionar
if (isset($_SESSION['usuario_id'])) {
    $usuario = new Usuario();
    $usuarioAtual = $usuario->buscarPorId($_SESSION['usuario_id']);
    
    if ($usuarioAtual['perfil'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: ui.php');
    }
    exit();
}

$erro = '';
$sucesso = '';

// Processar login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    if (empty($login) || empty($senha)) {
        $erro = 'Preencha todos os campos';
    } else {
        try {
            $usuario = new Usuario();
            $usuarioAutenticado = $usuario->autenticar($login, $senha);
            
            if ($usuarioAutenticado) {
                $_SESSION['usuario_id'] = $usuarioAutenticado['id'];
                $_SESSION['usuario_nome'] = $usuarioAutenticado['nome'];
                $_SESSION['usuario_perfil'] = $usuarioAutenticado['perfil'];
                
                // Redirecionar baseado no perfil
                if ($usuarioAutenticado['perfil'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: ui.php');
                }
                exit();
            } else {
                $erro = 'Login ou senha incorretos';
            }
        } catch (Exception $e) {
            $erro = 'Erro ao autenticar: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle de Estoque</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .login-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .login-title {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        
        .login-subtitle {
            opacity: 0.9;
            font-size: 0.9rem;
        }
        
        .login-form {
            padding: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #333;
        }
        
        .form-input {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            outline: none;
        }
        
        .form-input:focus {
            border-color: #667eea;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        
        .input-with-icon {
            padding-left: 3rem;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border-color: #dc3545;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-color: #28a745;
        }
        
        .login-footer {
            text-align: center;
            padding: 1rem 2rem 2rem;
            color: #666;
            font-size: 0.9rem;
        }
        
        .demo-credentials {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
            font-size: 0.8rem;
        }
        
        .demo-credentials h4 {
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .demo-credentials p {
            margin: 0.25rem 0;
        }
        
        .totem-link {
            display: block;
            text-align: center;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 10px;
            text-decoration: none;
            color: #667eea;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .totem-link:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }
        
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-header,
            .login-form {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-warehouse"></i>
            </div>
            <div class="login-title">Controle de Estoque</div>
            <div class="login-subtitle">Faça login para acessar o sistema</div>
        </div>
        
        <form class="login-form" method="POST">
            <?php if ($erro): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($erro); ?>
            </div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($sucesso); ?>
            </div>
            <?php endif; ?>
            
            <div class="form-group">
                <label class="form-label" for="login">Usuário:</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="login" name="login" class="form-input input-with-icon" 
                           placeholder="Digite seu usuário" required 
                           value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label" for="senha">Senha:</label>
                <div class="input-group">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="senha" name="senha" class="form-input input-with-icon" 
                           placeholder="Digite sua senha" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>
        
        <div class="login-footer">
            <div class="demo-credentials">
                <h4><i class="fas fa-info-circle"></i> Credenciais de Demonstração:</h4>
                <p><strong>Admin:</strong> admin / admin123</p>
                <p><strong>Operador:</strong> operador / operador123</p>
            </div>
            
            <a href="totem.php" class="totem-link">
                <i class="fas fa-touchscreen"></i> Acessar Totem (Raspberry Pi)
            </a>
        </div>
    </div>
    
    <script>
        // Auto-focus no campo de login
        document.getElementById('login').focus();
        
        // Permitir login com Enter
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('form').submit();
            }
        });
        
        // Mostrar/ocultar senha
        function togglePassword() {
            const senhaInput = document.getElementById('senha');
            const icon = document.querySelector('.fa-lock');
            
            if (senhaInput.type === 'password') {
                senhaInput.type = 'text';
                icon.classList.remove('fa-lock');
                icon.classList.add('fa-eye-slash');
            } else {
                senhaInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-lock');
            }
        }
    </script>
</body>
</html>
