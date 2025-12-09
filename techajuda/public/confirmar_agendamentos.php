<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

$conn = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];
$is_tecnico = $_SESSION['is_tecnico'] ?? false;

// Clientes não podem acessar esta página
if ($is_tecnico) {
    header("Location: agenda.php");
    exit();
}

// Buscar agendamentos pendentes de confirmação
$sql_agendamentos = "SELECT a.*, u.nome as tecnico_nome, u.email as tecnico_email
                    FROM agendamentos a
                    INNER JOIN tecnicos t ON a.tecnico_id = t.id
                    INNER JOIN usuarios u ON t.usuario_id = u.id
                    WHERE a.cliente_id = ? AND a.status = 'pendente_confirmacao'
                    ORDER BY a.data_agendamento, a.hora_inicio";
$stmt_agendamentos = $conn->prepare($sql_agendamentos);
$stmt_agendamentos->bind_param('i', $usuario_id);
$stmt_agendamentos->execute();
$agendamentos = $stmt_agendamentos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_agendamentos->close();

// Processar confirmação/recusa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $agendamento_id = intval($_POST['agendamento_id']);
    $acao = $_POST['acao']; // 'confirmar' ou 'recusar'
    
    // Verificar se o agendamento pertence ao cliente
    $sql_verificar = "SELECT id FROM agendamentos WHERE id = ? AND cliente_id = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param('ii', $agendamento_id, $usuario_id);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows > 0) {
        if ($acao === 'confirmar') {
            $sql_update = "UPDATE agendamentos SET status = 'agendado' WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('i', $agendamento_id);
            
            if ($stmt_update->execute()) {
                $mensagem = "Agendamento confirmado com sucesso!";
                header("Location: confirmar_agendamentos.php?sucesso=" . urlencode($mensagem));
                exit();
            }
            $stmt_update->close();
        } else {
            // Recusar - apagar o agendamento
            $sql_delete = "DELETE FROM agendamentos WHERE id = ?";
            $stmt_delete = $conn->prepare($sql_delete);
            $stmt_delete->bind_param('i', $agendamento_id);
            
            if ($stmt_delete->execute()) {
                $mensagem = "Agendamento recusado e removido.";
                header("Location: confirmar_agendamentos.php?sucesso=" . urlencode($mensagem));
                exit();
            }
            $stmt_delete->close();
        }
    }
    $stmt_verificar->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmar Agendamentos - TechAjuda</title>
    <link rel="stylesheet" href="../visualscript/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #0a0a0a;
            color: #e0e0e0;
            line-height: 1.6;
        }

        .topo-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 0 20px;
            height: 80px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.5);
            border-bottom: 1px solid #333;
        }

        .logo img {
            height: 150px;
            filter: brightness(0) invert(1);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-link {
            color: #e0e0e0;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #08ebf3;
            background: rgba(255, 255, 255, 0.05);
        }

        main {
            padding-top: 100px;
            min-height: 100vh;
        }

        .conteudo-principal {
            max-width: 900px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .voltar-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            color: #08ebf3;
            text-decoration: none;
            padding: 12px 20px;
            border: 2px solid #08ebf3;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .voltar-link:hover {
            background: #08ebf3;
            color: #001a33;
        }

        .titulo-pagina {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-align: center;
        }

        .agendamento-item {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 20px;
            border: 2px solid #08ebf3;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.3);
        }

        .agendamento-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }

        .agendamento-titulo {
            color: #08ebf3;
            font-size: 1.3em;
            font-weight: bold;
        }

        .status-pendente {
            background: linear-gradient(135deg, #ffa726, #ff9800);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .agendamento-info {
            color: #ccc;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .agendamento-descricao {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            border-left: 3px solid #08ebf3;
        }

        .botoes-acao {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .btn-confirmar {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-recusar {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            flex: 1;
        }

        .btn-confirmar:hover, .btn-recusar:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .mensagem {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 600;
        }

        .mensagem.sucesso {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .sem-agendamentos {
            text-align: center;
            padding: 60px 20px;
            color: #888;
        }

        .sem-agendamentos h3 {
            color: #08ebf3;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        @media (max-width: 768px) {
            .agendamento-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .botoes-acao {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <header class="topo-logado">
        <div class="logo">
            <img src="../visualscript/imagem/logotcc.png" alt="TechAjuda">
        </div>
        <div class="nav-right">
            <a href="area_logada.php" class="nav-link">Menu Principal</a>
            <a href="agenda.php" class="nav-link">Minha Agenda</a>
            <a href="minha_conta.php" class="nav-link">Minha Conta</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </header>

    <main>
        <div class="conteudo-principal">
            <a href="area_logada.php" class="voltar-link">Voltar ao Menu Principal</a>
            
            <h1 class="titulo-pagina">Confirmar Agendamentos</h1>

            <?php if (isset($_GET['sucesso'])): ?>
                <div class="mensagem sucesso"><?php echo htmlspecialchars($_GET['sucesso']); ?></div>
            <?php endif; ?>

            <?php if (empty($agendamentos)): ?>
                <div class="sem-agendamentos">
                    <h3>Nenhum agendamento pendente</h3>
                    <p>Você não possui agendamentos aguardando confirmação.</p>
                </div>
            <?php else: ?>
                <?php foreach ($agendamentos as $agendamento): ?>
                    <div class="agendamento-item">
                        <div class="agendamento-header">
                            <div class="agendamento-titulo"><?php echo htmlspecialchars($agendamento['titulo']); ?></div>
                            <span class="status-pendente">Aguardando Confirmação</span>
                        </div>
                        
                        <div class="agendamento-info">
                            Técnico: <?php echo htmlspecialchars($agendamento['tecnico_nome']); ?>
                        </div>
                        
                        <div class="agendamento-info">
                            Data: <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                        </div>
                        
                        <div class="agendamento-info">
                            Horário: <?php echo date('H:i', strtotime($agendamento['hora_inicio'])); ?>
                        </div>
                        
                        <div class="agendamento-info">
                            Local: <?php echo $agendamento['local_atendimento'] === 'local_tecnico' ? 'Local do Técnico' : 'Meu Local'; ?>
                        </div>
                        
                        <?php if ($agendamento['valor']): ?>
                            <div class="agendamento-info">
                                Valor: R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($agendamento['descricao'])): ?>
                            <div class="agendamento-descricao">
                                <strong>Descrição:</strong><br>
                                <?php echo htmlspecialchars($agendamento['descricao']); ?>
                            </div>
                        <?php endif; ?>

                        <div class="botoes-acao">
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                <input type="hidden" name="acao" value="confirmar">
                                <button type="submit" class="btn-confirmar">Confirmar Agendamento</button>
                            </form>
                            
                            <form method="POST" style="flex: 1;">
                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                <input type="hidden" name="acao" value="recusar">
                                <button type="submit" class="btn-recusar">Recusar Agendamento</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>