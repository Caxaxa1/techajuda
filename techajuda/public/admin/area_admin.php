<?php
session_start();
// Verificação de acesso
$codigo_acesso = isset($_POST['codigo_acesso']) ? $_POST['codigo_acesso'] : '';
$acesso_permitido = false;

if ($codigo_acesso === 'PEIXE') {
    $acesso_permitido = true;
    $_SESSION['admin_logado'] = true;
} elseif (isset($_SESSION['admin_logado']) && $_SESSION['admin_logado'] === true) {
    $acesso_permitido = true;
}

if (!$acesso_permitido) {
    // Mostrar formulário de acesso
    ?>
    <!DOCTYPE html>
    <html lang="pt-br">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Acesso Administrativo - TechAjuda</title>
        <link rel="stylesheet" href="../../visualscript/css/style.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Arial', sans-serif;
                background: #0a0a0a !important;
                color: #e0e0e0;
                line-height: 1.6;
                overflow-x: hidden;
                min-height: 100vh;
                display: flex !important;
                justify-content: center;
                align-items: center;
            }
           
            .acesso-container {
                background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
                padding: 50px 40px;
                border-radius: 20px;
                text-align: center;
                box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
                border: 2px solid #08ebf3;
                width: 90%;
                max-width: 450px;
                position: relative;
                overflow: hidden;
            }

            .acesso-container::before {
                content: '';
                position: absolute;
                top: 0;
                left: -100%;
                width: 100%;
                height: 100%;
                background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.1), transparent);
                transition: left 0.6s;
            }

            .acesso-container:hover::before {
                left: 100%;
            }
           
            .acesso-container h2 {
                color: #08ebf3;
                margin-bottom: 30px;
                font-size: 2em;
                font-weight: 700;
                background: linear-gradient(135deg, #ffffff, #08ebf3);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-clip: text;
            }
           
            .acesso-container input {
                width: 100%;
                padding: 15px 20px;
                margin: 20px 0;
                border: 2px solid #333;
                border-radius: 10px;
                font-size: 1.1em;
                text-align: center;
                background: rgba(255, 255, 255, 0.1);
                color: #e0e0e0;
                transition: all 0.3s ease;
            }

            .acesso-container input:focus {
                border-color: #08ebf3;
                outline: none;
                box-shadow: 0 0 15px rgba(8, 235, 243, 0.3);
                background: rgba(255, 255, 255, 0.15);
            }
           
            .acesso-container button {
                width: 100%;
                padding: 16px;
                background: linear-gradient(135deg, #08ebf3, #00bcd4);
                color: #001a33;
                font-weight: 700;
                border: none;
                border-radius: 10px;
                font-size: 1.1em;
                margin-top: 20px;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3);
            }
           
            .acesso-container button:hover {
                background: linear-gradient(135deg, #00bcd4, #0097a7);
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(8, 235, 243, 0.4);
            }
           
            .erro {
                color: #ff6b6b;
                background: linear-gradient(135deg, #dc3545, #c82333);
                padding: 12px 20px;
                border-radius: 8px;
                margin-bottom: 20px;
                font-weight: 600;
                border: 2px solid #ff4444;
                display: <?php echo isset($_POST['codigo_acesso']) ? 'block' : 'none'; ?>;
            }
        </style>
    </head>
    <body>
        <div class="acesso-container">
            <h2>🔧 Acesso Administrativo</h2>
           
            <?php if (isset($_POST['codigo_acesso']) && $codigo_acesso !== 'PEIXE'): ?>
                <div class="erro">❌ Código de acesso incorreto!</div>
            <?php endif; ?>
           
            <form method="POST">
                <input type="password" name="codigo_acesso" placeholder="🔒 Digite o código de acesso" required>
                <button type="submit">🚀 Acessar Painel</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit();
}

require_once "../../src/config.php";

// Processar ações (editar/excluir/aprovar/rejeitar/remover_tecnico)
if (isset($_POST['acao'])) {
    $conn = getDBConnection();
   
    if ($_POST['acao'] === 'excluir' && isset($_POST['usuario_id'])) {
        $usuario_id = intval($_POST['usuario_id']);
        $sql = "DELETE FROM usuarios WHERE id = $usuario_id";
        $conn->query($sql);
    }
   
    if ($_POST['acao'] === 'editar' && isset($_POST['usuario_id'])) {
        $usuario_id = intval($_POST['usuario_id']);
        $nome = $conn->real_escape_string($_POST['nome']);
        $email = $conn->real_escape_string($_POST['email']);
        $idade = intval($_POST['idade']);
        $cpf = $conn->real_escape_string($_POST['cpf']);
        $senha_texto = $conn->real_escape_string($_POST['senha_texto']);
       
        // Se a senha foi alterada, atualizar o hash
        if (!empty($senha_texto)) {
            $senha_hash = password_hash($senha_texto, PASSWORD_DEFAULT);
            $sql = "UPDATE usuarios SET
                    nome = '$nome',
                    email = '$email',
                    idade = $idade,
                    cpf = '$cpf',
                    senha = '$senha_hash'
                    WHERE id = $usuario_id";
        } else {
            // Se a senha não foi alterada, manter a atual
            $sql = "UPDATE usuarios SET
                    nome = '$nome',
                    email = '$email',
                    idade = $idade,
                    cpf = '$cpf'
                    WHERE id = $usuario_id";
        }
        $conn->query($sql);
    }
   
    // Aprovar pedido de técnico
if ($_POST['acao'] === 'aprovar_tecnico' && isset($_POST['usuario_id'])) {
    $usuario_id = intval($_POST['usuario_id']);

    // Marcar para mostrar mensagem no primeiro login
    $sql_primeiro_login = "UPDATE usuarios SET primeiro_login_tecnico = TRUE WHERE id = $usuario_id";
    $conn->query($sql_primeiro_login);
    
    // Atualizar usuário para técnico e remover pedido pendente
    $sql_usuario = "UPDATE usuarios SET is_tecnico = TRUE, pedido_tecnico_pendente = FALSE WHERE id = $usuario_id";
    $conn->query($sql_usuario);
    
    // ATUALIZAR STATUS NA TABELA TECNICOS PARA 'aprovado'
    $sql_tecnico = "UPDATE tecnicos SET status = 'aprovado' WHERE usuario_id = $usuario_id";
    $conn->query($sql_tecnico);
    
    $_SESSION['mensagem'] = "✅ Usuário aprovado como técnico com sucesso!";
}
   
    // Rejeitar pedido de técnico
    if ($_POST['acao'] === 'rejeitar_pedido' && isset($_POST['usuario_id'])) {
    $usuario_id = intval($_POST['usuario_id']);
    
    // Atualizar usuário
    $sql_usuario = "UPDATE usuarios SET pedido_tecnico_pendente = FALSE WHERE id = $usuario_id";
    $conn->query($sql_usuario);
    
    // ATUALIZAR STATUS NA TABELA TECNICOS PARA 'rejeitado'
    $sql_tecnico = "UPDATE tecnicos SET status = 'rejeitado' WHERE usuario_id = $usuario_id";
    $conn->query($sql_tecnico);
    
    $_SESSION['mensagem'] = "❌ Pedido de técnico rejeitado com sucesso!";
}
   
    // Remover status de técnico
if ($_POST['acao'] === 'remover_tecnico' && isset($_POST['usuario_id'])) {
    $usuario_id = intval($_POST['usuario_id']);
    
    // ATUALIZAR STATUS NA TABELA TECNICOS PARA 'rejeitado' em vez de deletar
    $sql_tecnico = "UPDATE tecnicos SET status = 'rejeitado' WHERE usuario_id = $usuario_id";
    $conn->query($sql_tecnico);
    
    // Atualizar usuário para não técnico
    $sql_usuario = "UPDATE usuarios SET is_tecnico = FALSE, pedido_tecnico_pendente = FALSE WHERE id = $usuario_id";
    $conn->query($sql_usuario);
    
    $_SESSION['mensagem'] = "🔧 Status de técnico removido com sucesso!";
}
   
    $conn->close();
    header("Location: area_admin.php");
    exit();
}

// Buscar todos os usuários
$conn = getDBConnection();
$sql = "SELECT id, nome, email, idade, cpf, sexo, apelido, celular, senha, is_tecnico, pedido_tecnico_pendente, data_cadastro FROM usuarios ORDER BY data_cadastro DESC";
$result = $conn->query($sql);
$usuarios = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
}

// Buscar pedidos de técnico pendentes
$sql_pedidos = "SELECT id, nome, email, idade, cpf, data_cadastro 
               FROM usuarios 
               WHERE pedido_tecnico_pendente = TRUE 
               ORDER BY data_cadastro DESC";
$result_pedidos = $conn->query($sql_pedidos);
$pedidos_pendentes = [];
if ($result_pedidos->num_rows > 0) {
    while($row = $result_pedidos->fetch_assoc()) {
        $pedidos_pendentes[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Administrativa - TechAjuda</title>
    <link rel="stylesheet" href="../../visualscript/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #0a0a0a !important;
            color: #e0e0e0;
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
        }
       
        .admin-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid #333;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.5);
        }
       
        .admin-header h1 {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 0;
            font-size: 2.8em;
            font-weight: 800;
            text-shadow: 0 2px 8px rgba(8, 235, 243, 0.3);
        }

        .admin-header p {
            color: #b0b0b0;
            font-size: 1.2em;
            margin-top: 10px;
        }
       
        .admin-nav {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
       
        .admin-nav a {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 25px;
            border-radius: 8px;
            transition: all 0.3s ease;
            border: 1px solid #333;
            background: rgba(255, 255, 255, 0.05);
        }
       
        .admin-nav a:hover {
            color: #08ebf3;
            background: rgba(255, 255, 255, 0.1);
            border-color: #08ebf3;
            transform: translateY(-2px);
        }
       
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
       
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
       
        .stat-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 30px 25px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #333;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.6);
            border-color: #08ebf3;
        }
       
        .stat-number {
            font-size: 3em;
            font-weight: 800;
            color: #08ebf3;
            margin: 15px 0;
            text-shadow: 0 2px 8px rgba(8, 235, 243, 0.3);
        }

        .stat-card div:first-child {
            color: #b0b0b0;
            font-size: 1.1em;
            font-weight: 600;
        }
       
        .usuarios-table {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            overflow: hidden;
            margin-top: 20px;
            overflow-x: auto;
            border: 1px solid #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
        }
       
        .table-header {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            padding: 20px;
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
            font-weight: 700;
            min-width: 1000px;
            color: #001a33;
        }
       
        .usuario-row {
            padding: 20px;
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr;
            border-bottom: 1px solid #333;
            align-items: center;
            min-width: 1000px;
            transition: all 0.3s ease;
        }
       
        .usuario-row:hover {
            background: rgba(255, 255, 255, 0.05);
        }
       
        .usuario-row:last-child {
            border-bottom: none;
        }
       
        .acoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
       
        .btn-editar, .btn-excluir, .btn-aprovar, .btn-rejeitar, .btn-remover-tecnico {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
        }
       
        .btn-editar {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
        }

        .btn-editar:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
        }
       
        .btn-excluir {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
        }

        .btn-excluir:hover {
            background: linear-gradient(135deg, #ee5a52, #ff4757);
            transform: translateY(-2px);
        }
       
        .btn-aprovar {
            background: linear-gradient(135deg, #00cc00, #00aa00);
            color: white;
        }

        .btn-aprovar:hover {
            background: linear-gradient(135deg, #00aa00, #008800);
            transform: translateY(-2px);
        }
       
        .btn-rejeitar {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
        }

        .btn-rejeitar:hover {
            background: linear-gradient(135deg, #f7931e, #e68318);
            transform: translateY(-2px);
        }
       
        .btn-remover-tecnico {
            background: linear-gradient(135deg, #ff8800, #ff6600);
            color: white;
        }

        .btn-remover-tecnico:hover {
            background: linear-gradient(135deg, #ff6600, #e65500);
            transform: translateY(-2px);
        }
       
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
       
        .modal-content {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 40px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            border: 2px solid #08ebf3;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }
       
        .modal h3 {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px;
            font-size: 1.8em;
            font-weight: 700;
            text-align: center;
        }
       
        .form-group {
            margin-bottom: 20px;
        }
       
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #08ebf3;
            font-weight: 600;
        }
       
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #333;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group input:focus {
            border-color: #08ebf3;
            outline: none;
            box-shadow: 0 0 12px rgba(8, 235, 243, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }
       
        .modal-buttons {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            margin-top: 25px;
        }
       
        .btn-cancelar, .btn-salvar {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
       
        .btn-cancelar {
            background: linear-gradient(135deg, #666, #555);
            color: white;
        }

        .btn-cancelar:hover {
            background: linear-gradient(135deg, #555, #444);
            transform: translateY(-2px);
        }
       
        .btn-salvar {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
        }

        .btn-salvar:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
        }
       
        .mensagem {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 600;
            border: 2px solid #1e7e34;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
       
        .pedidos-pendentes {
            margin: 50px 0;
        }
       
        .pedidos-pendentes h3 {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 25px;
            font-size: 2em;
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid #08ebf3;
        }
       
        .status-tecnico {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 700;
        }
       
        .status-sim {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }
       
        .status-pendente {
            background: linear-gradient(135deg, #ffc107, #ffb300);
            color: black;
        }
       
        .status-nao {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: white;
        }

        .section-title {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin: 40px 0 25px 0;
            font-size: 2em;
            font-weight: 700;
            padding-bottom: 15px;
            border-bottom: 2px solid #08ebf3;
        }

        .empty-state {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 40px 20px;
            border-radius: 15px;
            text-align: center;
            border: 1px solid #333;
            color: #b0b0b0;
            font-size: 1.1em;
        }

        @media (max-width: 768px) {
            .admin-nav {
                flex-direction: column;
                gap: 10px;
            }
            
            .admin-nav a {
                width: 100%;
                text-align: center;
            }
            
            .stats {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                padding: 30px 20px;
            }
            
            .modal-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🔧 Área Administrativa</h1>
        <p>Gerenciamento Completo de Usuários e Pedidos de Técnico</p>
    </div>
   
    <div class="admin-nav">
        <a href="../minha_conta.php">← Voltar para Minha Conta</a>
        <a href="gerenciar_tecnicos.php">👥 Gerenciar Técnicos</a>
        <a href="area_admin.php">🔄 Atualizar Lista</a>
        <a href="?sair=1">🚪 Sair do Admin</a>
    </div>
   
    <div class="container">
        <?php if (isset($_SESSION['mensagem'])): ?>
            <div class="mensagem">
                <?php echo $_SESSION['mensagem']; ?>
                <?php unset($_SESSION['mensagem']); ?>
            </div>
        <?php endif; ?>
       
        <div class="stats">
            <div class="stat-card">
                <div>👥 Total de Usuários</div>
                <div class="stat-number"><?php echo count($usuarios); ?></div>
            </div>
            <div class="stat-card">
                <div>🔧 Técnicos Cadastrados</div>
                <div class="stat-number">
                    <?php echo count(array_filter($usuarios, function($u) { return $u['is_tecnico']; })); ?>
                </div>
            </div>
            <div class="stat-card">
                <div>⏳ Pedidos Pendentes</div>
                <div class="stat-number"><?php echo count($pedidos_pendentes); ?></div>
            </div>
        </div>
       
        <!-- Seção de Pedidos de Técnico Pendentes -->
        <div class="pedidos-pendentes">
            <h3>📋 Pedidos de Técnico Pendentes</h3>
           
            <?php if (count($pedidos_pendentes) > 0): ?>
                <div class="usuarios-table">
                    <div class="table-header">
                        <div>Nome</div>
                        <div>E-mail</div>
                        <div>Idade</div>
                        <div>CPF</div>
                        <div>Data do Pedido</div>
                        <div>Ações</div>
                    </div>
                   
                    <?php foreach ($pedidos_pendentes as $pedido): ?>
                    <div class="usuario-row">
                        <div><?php echo htmlspecialchars($pedido['nome']); ?></div>
                        <div><?php echo htmlspecialchars($pedido['email']); ?></div>
                        <div><?php echo $pedido['idade']; ?></div>
                        <div><?php echo htmlspecialchars($pedido['cpf']); ?></div>
                        <div><?php echo date('d/m/Y H:i', strtotime($pedido['data_cadastro'])); ?></div>
                        <div class="acoes">
                            <button class="btn-aprovar" onclick="aprovarTecnico(<?php echo $pedido['id']; ?>, '<?php echo addslashes($pedido['nome']); ?>')">
                                ✅ Aprovar
                            </button>
                            <button class="btn-rejeitar" onclick="rejeitarPedido(<?php echo $pedido['id']; ?>, '<?php echo addslashes($pedido['nome']); ?>')">
                                ❌ Rejeitar
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <p>🎉 Nenhum pedido de técnico pendente no momento.</p>
                </div>
            <?php endif; ?>
        </div>
       
        <!-- Seção de Todos os Usuários -->
        <h3 class="section-title">👥 Todos os Usuários</h3>
        <div class="usuarios-table">
            <div class="table-header">
                <div>Nome</div>
                <div>E-mail</div>
                <div>Idade</div>
                <div>CPF</div>
                <div>Técnico</div>
                <div>Ações</div>
            </div>
           
            <?php foreach ($usuarios as $usuario): ?>
            <div class="usuario-row">
                <div><?php echo htmlspecialchars($usuario['nome']); ?></div>
                <div><?php echo htmlspecialchars($usuario['email']); ?></div>
                <div><?php echo $usuario['idade']; ?></div>
                <div><?php echo htmlspecialchars($usuario['cpf']); ?></div>
                <div>
                    <?php 
                    if ($usuario['is_tecnico']) {
                        echo '<span class="status-tecnico status-sim">✅ Técnico</span>';
                    } elseif ($usuario['pedido_tecnico_pendente']) {
                        echo '<span class="status-tecnico status-pendente">⏳ Pendente</span>';
                    } else {
                        echo '<span class="status-tecnico status-nao">❌ Não</span>';
                    }
                    ?>
                </div>
                <div class="acoes">
                    <button class="btn-editar" onclick="editarUsuario(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>', '<?php echo addslashes($usuario['email']); ?>', <?php echo $usuario['idade']; ?>, '<?php echo addslashes($usuario['cpf']); ?>')">
                        ✏️ Editar
                    </button>
                    
                    <?php if ($usuario['is_tecnico']): ?>
                        <button class="btn-remover-tecnico" onclick="removerTecnico(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>')">
                            🔧 Remover Técnico
                        </button>
                    <?php endif; ?>
                    
                    <button class="btn-excluir" onclick="excluirUsuario(<?php echo $usuario['id']; ?>, '<?php echo addslashes($usuario['nome']); ?>')">
                        🗑️ Excluir
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
   
    <!-- Modal de Edição -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <h3>✏️ Editar Usuário</h3>
            <form id="formEditar" method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="usuario_id" id="editarUsuarioId">
               
                <div class="form-group">
                    <label for="editarNome">Nome:</label>
                    <input type="text" id="editarNome" name="nome" required>
                </div>
               
                <div class="form-group">
                    <label for="editarEmail">E-mail:</label>
                    <input type="email" id="editarEmail" name="email" required>
                </div>
               
                <div class="form-group">
                    <label for="editarIdade">Idade:</label>
                    <input type="number" id="editarIdade" name="idade" min="1" max="120" required>
                </div>
               
                <div class="form-group">
                    <label for="editarCpf">CPF:</label>
                    <input type="text" id="editarCpf" name="cpf" required>
                </div>
               
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">❌ Cancelar</button>
                    <button type="submit" class="btn-salvar">💾 Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
   
    <!-- Modal de Confirmação de Exclusão -->
    <div id="modalExcluir" class="modal">
        <div class="modal-content">
            <h3>🗑️ Confirmar Exclusão</h3>
            <p id="textoConfirmacao">Tem certeza que deseja excluir este usuário?</p>
            <form id="formExcluir" method="POST">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="usuario_id" id="excluirUsuarioId">
               
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">❌ Cancelar</button>
                    <button type="submit" class="btn-excluir">🗑️ Confirmar Exclusão</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Remover Técnico -->
    <div id="modalRemoverTecnico" class="modal">
        <div class="modal-content">
            <h3>🔧 Remover Status de Técnico</h3>
            <p id="textoRemoverTecnico">Tem certeza que deseja remover o status de técnico deste usuário?</p>
            <form id="formRemoverTecnico" method="POST">
                <input type="hidden" name="acao" value="remover_tecnico">
                <input type="hidden" name="usuario_id" id="removerTecnicoUsuarioId">
               
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">❌ Cancelar</button>
                    <button type="submit" class="btn-remover-tecnico">🔧 Confirmar Remoção</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editarUsuario(id, nome, email, idade, cpf) {
            document.getElementById('editarUsuarioId').value = id;
            document.getElementById('editarNome').value = nome;
            document.getElementById('editarEmail').value = email;
            document.getElementById('editarIdade').value = idade;
            document.getElementById('editarCpf').value = cpf;
            document.getElementById('modalEditar').style.display = 'flex';
        }
       
        function excluirUsuario(id, nome) {
            document.getElementById('excluirUsuarioId').value = id;
            document.getElementById('textoConfirmacao').textContent =
                'Tem certeza que deseja excluir o usuário "' + nome + '"? Esta ação não pode ser desfeita.';
            document.getElementById('modalExcluir').style.display = 'flex';
        }
       
        function aprovarTecnico(id, nome) {
            if (confirm('✅ Deseja aprovar "' + nome + '" como técnico?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'area_admin.php';
                
                const inputAcao = document.createElement('input');
                inputAcao.name = 'acao';
                inputAcao.value = 'aprovar_tecnico';
                form.appendChild(inputAcao);
                
                const inputId = document.createElement('input');
                inputId.name = 'usuario_id';
                inputId.value = id;
                form.appendChild(inputId);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
       
        function rejeitarPedido(id, nome) {
            if (confirm('❌ Deseja rejeitar o pedido de técnico de "' + nome + '"?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'area_admin.php';
                
                const inputAcao = document.createElement('input');
                inputAcao.name = 'acao';
                inputAcao.value = 'rejeitar_pedido';
                form.appendChild(inputAcao);
                
                const inputId = document.createElement('input');
                inputId.name = 'usuario_id';
                inputId.value = id;
                form.appendChild(inputId);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
       
        function removerTecnico(id, nome) {
            document.getElementById('removerTecnicoUsuarioId').value = id;
            document.getElementById('textoRemoverTecnico').textContent =
                'Tem certeza que deseja remover o status de técnico de "' + nome + '"? O usuário perderá acesso às funcionalidades de técnico.';
            document.getElementById('modalRemoverTecnico').style.display = 'flex';
        }
       
        function fecharModal() {
            document.getElementById('modalEditar').style.display = 'none';
            document.getElementById('modalExcluir').style.display = 'none';
            document.getElementById('modalRemoverTecnico').style.display = 'none';
        }
       
        // Fechar modal clicando fora
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>

<?php
// Logout
if (isset($_GET['sair'])) {
    unset($_SESSION['admin_logado']);
    header("Location: area_admin.php");
    exit();
}