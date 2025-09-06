<?php
/**
 * Interface do Totem - Raspberry Pi
 * Registro rápido de entrada/saída de estoque
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Produto.php';
require_once __DIR__ . '/../app/core/Movimentacao.php';

// Verificar se é requisição AJAX
if (isset($_POST['acao'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $db = Database::getInstance();
        $produto = new Produto();
        $movimentacao = new Movimentacao();
        
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
                    'origem' => 'pi'
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
    <title>Controle de Estoque</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            height: 100vh;
            overflow: hidden;
            user-select: none;
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .header {
            text-align: center;
            background: #34495e;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .main-content {
            flex: 1;
            display: flex;
            gap: 30px;
        }
        
        .section {
            flex: 1;
            background: white;
            border-radius: 10px;
            padding: 40px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 3px solid transparent;
        }
        
        .section.entrada {
            background: #27ae60;
            color: white;
        }
        
        .section.saida {
            background: #e74c3c;
            color: white;
        }
        
        .section.entrada:hover, .section.entrada.selected {
            background: #229954;
            transform: translateY(-2px);
        }
        
        .section.saida:hover, .section.saida.selected {
            background: #c0392b;
            transform: translateY(-2px);
        }
        
        .section-number {
            font-size: 6rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .section.entrada .section-number,
        .section.saida .section-number {
            color: white;
        }
        
        .section-title {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .section.entrada .section-title,
        .section.saida .section-title {
            color: white;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: #7f8c8d;
            text-align: center;
        }
        
        .section.entrada .section-subtitle,
        .section.saida .section-subtitle {
            color: rgba(255, 255, 255, 0.9);
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
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 40px;
            max-width: 500px;
            width: 90%;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-title {
            font-size: 1.8rem;
            margin-bottom: 30px;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .input-group {
            margin-bottom: 25px;
        }
        
        .input-label {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 10px;
            color: #2c3e50;
            font-weight: bold;
        }
        
        .input-field {
            width: 100%;
            padding: 20px;
            font-size: 2rem;
            border: 3px solid #bdc3c7;
            border-radius: 8px;
            text-align: center;
            outline: none;
            transition: border-color 0.3s ease;
            font-weight: bold;
        }
        
        .input-field:focus {
            border-color: #3498db;
        }
        
        .product-info {
            background: #ecf0f1;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            display: none;
        }
        
        .product-name {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .product-stock {
            font-size: 1.2rem;
            color: #7f8c8d;
        }
        
        .message {
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            font-size: 1.1rem;
            display: none;
            font-weight: bold;
        }
        
        .message.success {
            background: #d5f4e6;
            color: #27ae60;
            border: 2px solid #27ae60;
        }
        
        .message.error {
            background: #fadbd8;
            color: #e74c3c;
            border: 2px solid #e74c3c;
        }
        
        .message.info {
            background: #d6eaf8;
            color: #3498db;
            border: 2px solid #3498db;
        }
        
        .instructions {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            font-size: 0.9rem;
        }
        
        .instruction-item {
            margin: 5px 0;
        }
        
        .key {
            background: #34495e;
            padding: 2px 8px;
            border-radius: 4px;
            font-weight: bold;
            margin: 0 5px;
        }
        
        @media (max-width: 768px) {
            .header h1 {
                font-size: 2rem;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
            
            .section-number {
                font-size: 4rem;
            }
            
            .modal-content {
                padding: 20px;
            }
            
            .modal-title {
                font-size: 1.5rem;
            }
            
            .input-field {
                font-size: 1.5rem;
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>CONTROLE DE ESTOQUE</h1>
        </div>
        
        <div class="main-content">
            <div class="section entrada" id="entradaSection">
                <div class="section-number">1</div>
                <div class="section-title">ENTRADA</div>
                <div class="section-subtitle">Registrar entrada de produtos</div>
            </div>
            
            <div class="section saida" id="saidaSection">
                <div class="section-number">2</div>
                <div class="section-title">SAÍDA</div>
                <div class="section-subtitle">Registrar saída de produtos</div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Movimentação -->
    <div class="modal" id="modalMovimentacao">
        <div class="modal-content">
            <div class="modal-title" id="modalTitle">REGISTRAR MOVIMENTAÇÃO</div>
            
            <div class="message" id="message"></div>
            
            <div class="input-group">
                <label class="input-label" for="codigoProduto">CÓDIGO DO PRODUTO:</label>
                <input type="text" id="codigoProduto" class="input-field" placeholder="DIGITE O CÓDIGO" autocomplete="off">
            </div>
            
            <div class="product-info" id="productInfo">
                <div class="product-name" id="productName"></div>
                <div class="product-stock" id="productStock"></div>
            </div>
            
            <div class="input-group" id="quantidadeGroup" style="display: none;">
                <label class="input-label" for="quantidade">QUANTIDADE:</label>
                <input type="number" id="quantidade" class="input-field" placeholder="DIGITE A QUANTIDADE" min="0.01" step="0.01">
            </div>
        </div>
    </div>
    
    <!-- Instruções -->
    <div class="instructions">
        <div class="instruction-item">DIGITE O CÓDIGO DO PRODUTO</div>
        <div class="instruction-item"><span class="key">Backspace</span> - APAGAR</div>
        <div class="instruction-item"><span class="key">.</span> - LIMPAR TUDO</div>
    </div>
    
    <script>
        let tipoMovimentacao = '';
        let produtoAtual = null;
        let currentStep = 'menu'; // menu, codigo, confirmacao, quantidade
        let codigoDigitado = '';
        let searchTimeout = null;
        let processandoAcao = false; // Proteção contra duplo clique/enter
        
        // Atalhos de teclado
        document.addEventListener('keydown', function(e) {
            // Se uma ação está sendo processada, ignorar outras teclas importantes
            if (processandoAcao && ['Enter', '0', '1', '2'].includes(e.key)) {
                e.preventDefault();
                return;
            }
            
            // Prevenir comportamento padrão para números quando não estiver digitando
            if (['1', '2', '3', '4', '5', '6', '7', '8', '9', '0'].includes(e.key)) {
                if (currentStep === 'menu') {
                    e.preventDefault();
                } else if (currentStep === 'codigo') {
                    e.preventDefault();
                    adicionarDigito(e.key);
                    return;
                }
                // Para outros passos, permitir comportamento padrão
            }
            
            // Permitir Backspace para apagar
            if (e.key === 'Backspace' && currentStep === 'codigo') {
                e.preventDefault();
                apagarDigito();
                return;
            }
            
            switch(e.key) {
                case '1':
                    if (currentStep === 'menu' && !processandoAcao) {
                        iniciarMovimentacao('ENTRADA');
                    }
                    break;
                case '2':
                    if (currentStep === 'menu' && !processandoAcao) {
                        iniciarMovimentacao('SAIDA');
                    }
                    break;
                case '0':
                    if (currentStep === 'quantidade' && !processandoAcao) {
                        confirmarMovimentacao();
                    }
                    break;
                case 'Enter':
                    if (currentStep === 'codigo' && produtoAtual && !processandoAcao) {
                        // Só avança se há produto encontrado
                        avancarParaQuantidade();
                    } else if (currentStep === 'quantidade' && !processandoAcao) {
                        confirmarMovimentacao();
                    }
                    break;
                case '.':
                    if (currentStep === 'codigo') {
                        limparCodigo();
                    } else if (currentStep === 'confirmacao') {
                        voltarParaCodigo();
                    } else if (currentStep === 'quantidade') {
                        voltarParaConfirmacao();
                    } else if (currentStep !== 'menu') {
                        fecharModal();
                    }
                    break;
            }
        });
        
        function iniciarMovimentacao(tipo) {
            tipoMovimentacao = tipo;
            currentStep = 'codigo';
            codigoDigitado = '';
            produtoAtual = null;
            
            document.getElementById('modalTitle').textContent = `REGISTRAR ${tipo}`;
            document.getElementById('codigoProduto').value = '';
            document.getElementById('productInfo').style.display = 'none';
            document.getElementById('quantidadeGroup').style.display = 'none';
            document.getElementById('message').style.display = 'none';
            document.getElementById('modalMovimentacao').style.display = 'flex';
            
            setTimeout(() => {
                document.getElementById('codigoProduto').focus();
                mostrarMensagem(`DIGITE O CÓDIGO DO PRODUTO`, 'info');
            }, 100);
        }
        
        function adicionarDigito(digito) {
            if (currentStep !== 'codigo') return;
            
            codigoDigitado += digito;
            document.getElementById('codigoProduto').value = codigoDigitado;
            
            // Limpar produto atual se mudou o código
            produtoAtual = null;
            document.getElementById('productInfo').style.display = 'none';
            document.getElementById('message').style.display = 'none';
            
            // Buscar produto imediatamente
            clearTimeout(searchTimeout);
            buscarProdutoAutomatico();
        }
        
        function apagarDigito() {
            if (currentStep !== 'codigo') return;
            
            if (codigoDigitado.length > 0) {
                codigoDigitado = codigoDigitado.slice(0, -1);
                document.getElementById('codigoProduto').value = codigoDigitado;
                
                // Limpar produto atual se mudou o código
                produtoAtual = null;
                document.getElementById('productInfo').style.display = 'none';
                document.getElementById('message').style.display = 'none';
                
                // Buscar imediatamente após apagar
                clearTimeout(searchTimeout);
                buscarProdutoAutomatico();
            }
        }
        
        function limparCodigo() {
            codigoDigitado = '';
            document.getElementById('codigoProduto').value = '';
            document.getElementById('productInfo').style.display = 'none';
            document.getElementById('message').style.display = 'none';
            mostrarMensagem('DIGITE O CÓDIGO DO PRODUTO', 'info');
        }
        
        function buscarProdutoAutomatico() {
            if (!codigoDigitado) {
                // Se não tem código, limpar tudo
                produtoAtual = null;
                document.getElementById('productInfo').style.display = 'none';
                document.getElementById('message').style.display = 'none';
                mostrarMensagem('DIGITE O CÓDIGO DO PRODUTO', 'info');
                return;
            }
            
            // Mostrar que está buscando
            mostrarMensagem('BUSCANDO PRODUTO...', 'info');
            
            // Sempre fazer nova busca
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
                    mostrarConfirmacaoProduto();
                } else {
                    mostrarErro('PRODUTO NÃO ENCONTRADO');
                }
            })
            .catch(error => {
                mostrarErro('ERRO AO BUSCAR PRODUTO');
            });
        }
        
        function mostrarConfirmacaoProduto() {
            // NÃO mudar o currentStep para 'confirmacao' ainda
            // Manter em 'codigo' para permitir edição
            document.getElementById('productInfo').style.display = 'block';
            document.getElementById('productName').textContent = produtoAtual.nome;
            document.getElementById('productStock').textContent = `Estoque: ${produtoAtual.quantidade_atual} ${produtoAtual.unidade}`;
            document.getElementById('message').style.display = 'none';
            mostrarMensagem('PRODUTO ENCONTRADO - ENTER PARA CONTINUAR', 'success');
        }
        
        function avancarParaQuantidade() {
            if (!produtoAtual) return;
            
            // Agora sim mudar para quantidade
            currentStep = 'quantidade';
            document.getElementById('quantidadeGroup').style.display = 'block';
            document.getElementById('quantidade').value = '';
            mostrarMensagem('DIGITE A QUANTIDADE - ENTER PARA CONFIRMAR', 'info');
            
            setTimeout(() => {
                document.getElementById('quantidade').focus();
            }, 100);
        }
        
        function voltarParaCodigo() {
            currentStep = 'codigo';
            document.getElementById('productInfo').style.display = 'none';
            document.getElementById('quantidadeGroup').style.display = 'none';
            document.getElementById('message').style.display = 'none';
            mostrarMensagem('DIGITE O CÓDIGO DO PRODUTO', 'info');
        }
        
        function voltarParaConfirmacao() {
            currentStep = 'confirmacao';
            document.getElementById('quantidadeGroup').style.display = 'none';
            document.getElementById('message').style.display = 'none';
            mostrarMensagem('PRODUTO ENCONTRADO - ENTER PARA CONTINUAR', 'success');
        }
        
        function mostrarErro(mensagem) {
            document.getElementById('message').textContent = mensagem;
            document.getElementById('message').className = 'message error';
            document.getElementById('message').style.display = 'block';
            mostrarMensagem('PONTO (.) PARA LIMPAR E TENTAR NOVAMENTE', 'error');
        }
        
        function buscarProduto() {
            const codigo = document.getElementById('codigoProduto').value.trim();
            
            if (!codigo) {
                mostrarMensagem('DIGITE O CÓDIGO DO PRODUTO', 'error');
                return;
            }
            
            const formData = new FormData();
            formData.append('acao', 'buscar_produto');
            formData.append('codigo', codigo);
            
            fetch('totem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    produtoAtual = data.produto;
                    mostrarProduto(data.produto);
                    document.getElementById('quantidadeGroup').style.display = 'block';
                    currentStep = 'quantidade';
                    document.getElementById('quantidade').focus();
                    mostrarMensagem(`DIGITE A QUANTIDADE`, 'info');
                } else {
                    mostrarMensagem('PRODUTO NÃO ENCONTRADO', 'error');
                }
            })
            .catch(error => {
                mostrarMensagem('ERRO AO BUSCAR PRODUTO', 'error');
            });
        }
        
        function mostrarProduto(produto) {
            document.getElementById('productInfo').style.display = 'block';
            document.getElementById('productName').textContent = produto.nome.toUpperCase();
            document.getElementById('productStock').textContent = `ESTOQUE ATUAL: ${produto.quantidade_atual || 0} ${produto.unidade}`;
        }
        
        function confirmarMovimentacao() {
            // Proteção contra duplo clique/enter
            if (processandoAcao) {
                return;
            }
            
            if (!produtoAtual) {
                mostrarMensagem('PRODUTO NÃO SELECIONADO', 'error');
                return;
            }
            
            const quantidade = parseFloat(document.getElementById('quantidade').value);
            
            if (!quantidade || quantidade <= 0) {
                mostrarMensagem('DIGITE UMA QUANTIDADE VÁLIDA', 'error');
                return;
            }
            
            // Marcar que uma ação está sendo processada
            processandoAcao = true;
            mostrarMensagem('PROCESSANDO...', 'info');
            
            const formData = new FormData();
            formData.append('acao', 'registrar_movimentacao');
            formData.append('produto_id', produtoAtual.id);
            formData.append('tipo', tipoMovimentacao);
            formData.append('quantidade', quantidade);
            
            fetch('totem.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarMensagem('MOVIMENTAÇÃO REGISTRADA COM SUCESSO!', 'success');
                    setTimeout(() => {
                        fecharModal();
                    }, 3000);
                } else {
                    mostrarMensagem(data.message.toUpperCase(), 'error');
                }
            })
            .catch(error => {
                mostrarMensagem('ERRO AO REGISTRAR MOVIMENTAÇÃO', 'error');
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
            currentStep = 'menu';
            processandoAcao = false; // Resetar proteção
        }
        
        function mostrarMensagem(texto, tipo) {
            const message = document.getElementById('message');
            message.textContent = texto;
            message.className = `message ${tipo}`;
            message.style.display = 'block';
        }
        
        // Auto-logout após inatividade
        let timeoutId;
        function resetTimeout() {
            clearTimeout(timeoutId);
            timeoutId = setTimeout(() => {
                if (currentStep !== 'menu') {
                    fecharModal();
                }
            }, <?php echo TOTEM_AUTO_LOGOUT * 1000; ?>);
        }
        
        document.addEventListener('keydown', resetTimeout);
        resetTimeout();
    </script>
</body>
</html>
