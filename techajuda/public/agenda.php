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

// Buscar agendamentos do usuário
if ($is_tecnico) {
    // Se for técnico, buscar todos os agendamentos dele
    $sql_tecnico = "SELECT id FROM tecnicos WHERE usuario_id = ? AND status = 'aprovado'";
    $stmt_tecnico = $conn->prepare($sql_tecnico);
    $stmt_tecnico->bind_param('i', $usuario_id);
    $stmt_tecnico->execute();
    $result_tecnico = $stmt_tecnico->get_result();
    
    if ($result_tecnico->num_rows > 0) {
        $tecnico = $result_tecnico->fetch_assoc();
        $tecnico_id = $tecnico['id'];
        
        $sql_agendamentos = "SELECT 
                                a.*,
                                u.nome as cliente_nome,
                                u.email as cliente_email,
                                t.usuario_id as tecnico_user_id
                             FROM agendamentos a
                             INNER JOIN usuarios u ON a.cliente_id = u.id
                             INNER JOIN tecnicos t ON a.tecnico_id = t.id
                             WHERE a.tecnico_id = ?
                             ORDER BY a.data_agendamento DESC, a.hora_inicio DESC";
        $stmt_agendamentos = $conn->prepare($sql_agendamentos);
        $stmt_agendamentos->bind_param('i', $tecnico_id);
    }
} else {
    // Se for cliente, buscar todos os seus agendamentos
    $sql_agendamentos = "SELECT 
                            a.*,
                            u.nome as tecnico_nome,
                            u.email as tecnico_email
                         FROM agendamentos a
                         INNER JOIN tecnicos t ON a.tecnico_id = t.id
                         INNER JOIN usuarios u ON t.usuario_id = u.id
                         WHERE a.cliente_id = ?
                         ORDER BY a.data_agendamento DESC, a.hora_inicio DESC";
    $stmt_agendamentos = $conn->prepare($sql_agendamentos);
    $stmt_agendamentos->bind_param('i', $usuario_id);
}

$stmt_agendamentos->execute();
$agendamentos = $stmt_agendamentos->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_agendamentos->close();

// Processar atualização de status (apenas para técnicos)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_tecnico && isset($_POST['atualizar_status'])) {
    $agendamento_id = intval($_POST['agendamento_id']);
    $novo_status = $conn->real_escape_string($_POST['novo_status']);
    
    // Verificar se o agendamento pertence ao técnico
    $sql_verificar = "SELECT id FROM agendamentos WHERE id = ? AND tecnico_id = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param('ii', $agendamento_id, $tecnico_id);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows > 0) {
        if ($novo_status === 'cancelado') {
            // Se for cancelamento, apagar o agendamento
            $sql_update = "DELETE FROM agendamentos WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('i', $agendamento_id);
            $mensagem_sucesso = "Agendamento cancelado e removido com sucesso!";
        } else {
            // Para outros status, apenas atualizar
            $sql_update = "UPDATE agendamentos SET status = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param('si', $novo_status, $agendamento_id);
            $mensagem_sucesso = "Status do agendamento atualizado com sucesso!";
        }
        
        if ($stmt_update->execute()) {
            $_SESSION['mensagem_sucesso'] = $mensagem_sucesso;
            header("Location: agenda.php");
            exit();
        } else {
            $erro = "Erro ao atualizar agendamento: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $erro = "Agendamento não encontrado ou você não tem permissão para editá-lo.";
    }
    $stmt_verificar->close();
}

// Processar confirmação de agendamento (para clientes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_tecnico && isset($_POST['confirmar_agendamento'])) {
    $agendamento_id = intval($_POST['agendamento_id']);
    
    // Verificar se o agendamento pertence ao cliente
    $sql_verificar = "SELECT id FROM agendamentos WHERE id = ? AND cliente_id = ? AND status = 'agendado'";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param('ii', $agendamento_id, $usuario_id);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows > 0) {
        // Atualizar status para confirmado
        $sql_update = "UPDATE agendamentos SET status = 'confirmado' WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('i', $agendamento_id);
        
        if ($stmt_update->execute()) {
            $_SESSION['mensagem_sucesso'] = "Agendamento confirmado com sucesso!";
            header("Location: agenda.php");
            exit();
        } else {
            $erro = "Erro ao confirmar agendamento: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $erro = "Agendamento não encontrado ou você não tem permissão para confirmá-lo.";
    }
    $stmt_verificar->close();
}

// Processar cancelamento de agendamento (para clientes)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_tecnico && isset($_POST['cancelar_agendamento'])) {
    $agendamento_id = intval($_POST['agendamento_id']);
    
    // Verificar se o agendamento pertence ao cliente
    $sql_verificar = "SELECT id FROM agendamentos WHERE id = ? AND cliente_id = ?";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param('ii', $agendamento_id, $usuario_id);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows > 0) {
        // Apagar o agendamento
        $sql_update = "DELETE FROM agendamentos WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('i', $agendamento_id);
        
        if ($stmt_update->execute()) {
            $_SESSION['mensagem_sucesso'] = "Agendamento cancelado com sucesso!";
            header("Location: agenda.php");
            exit();
        } else {
            $erro = "Erro ao cancelar agendamento: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $erro = "Agendamento não encontrado ou você não tem permissão para cancelá-lo.";
    }
    $stmt_verificar->close();
}

// Verificar mensagens de sessão
if (isset($_SESSION['mensagem_sucesso'])) {
    $sucesso = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda - TechAjuda</title>
    <link rel="stylesheet" href="../visualscript/css/style.css">
    <!-- FullCalendar CSS -->
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
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
            overflow-x: hidden;
            min-height: 100vh;
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

        .logo {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .logo img {
            height: 150px;
            width: auto;
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
        }

        .logo img:hover {
            transform: scale(1.05);
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .nav-link {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 600;
            font-size: 1em;
            transition: all 0.3s ease;
            padding: 10px 18px;
            border-radius: 8px;
            position: relative;
            overflow: hidden;
            white-space: nowrap;
        }

        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.2), transparent);
            transition: left 0.5s;
        }

        .nav-link:hover::before {
            left: 100%;
        }

        .nav-link:hover {
            color: #08ebf3;
            background: rgba(255, 255, 255, 0.05);
        }

        .account-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            border: 2px solid #08ebf3;
        }

        main {
            flex: 1;
            padding-top: 100px;
        }

        .conteudo-principal {
            padding: 30px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .voltar-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            color: #08ebf3;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 20px;
            border: 2px solid #08ebf3;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .voltar-link:hover {
            background: #08ebf3;
            color: #001a33;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 235, 243, 0.3);
        }

        .titulo-pagina {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5em;
            margin-bottom: 30px;
            text-align: center;
            font-weight: 800;
        }

        /* Botão Criar Agendamento */
        .btn-criar-agendamento {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 30px;
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 20px;
            border: 2px solid #08ebf3;
            border-radius: 10px;
            transition: all 0.3s ease;
            margin-left: 15px;
        }

        .btn-criar-agendamento:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 235, 243, 0.3);
            color: #001a33;
        }

        /* Container Principal */
        .agenda-container {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        /* Calendar Container */
        .calendar-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        /* FullCalendar Customization */
        .fc {
            color: #e0e0e0;
        }

        .fc-toolbar-title {
            color: #08ebf3;
            font-weight: 700;
        }

        .fc-button {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            border: none;
            color: #001a33;
            font-weight: 600;
        }

        .fc-button:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
        }

        .fc-button:disabled {
            background: #666;
            color: #999;
        }

        .fc-daygrid-day-number, 
        .fc-col-header-cell-cushion {
            color: #e0e0e0;
            text-decoration: none;
        }

        .fc-day-today {
            background: rgba(8, 235, 243, 0.1);
        }

        /* GARANTIR QUE EVENTOS SEJAM VISÍVEIS */
        .fc-event {
            border: none !important;
            border-radius: 4px !important;
            padding: 2px 4px !important;
            margin: 1px 0 !important;
            font-size: 0.8em !important;
            font-weight: bold !important;
            cursor: pointer !important;
        }

        .fc-event-title {
            color: white !important;
            font-weight: bold !important;
            font-size: 0.9em !important;
        }

        /* CORES DOS EVENTOS - COM !IMPORTANT */
        .fc-event-pendente_confirmacao,
        .fc-event[class*="pendente_confirmacao"] {
            background: #ff9800 !important;
            border-color: #ff9800 !important;
        }

        .fc-event-agendado,
        .fc-event[class*="agendado"] {
            background: #ff9800 !important;
            border-color: #ff9800 !important;
        }

        .fc-event-confirmado,
        .fc-event[class*="confirmado"] {
            background: #4caf50 !important;
            border-color: #4caf50 !important;
        }

        .fc-event-concluido,
        .fc-event[class*="concluido"] {
            background: #2196f3 !important;
            border-color: #2196f3 !important;
        }

        /* GARANTIR QUE OS EVENTOS APAREÇAM NOS DIAS */
        .fc-daygrid-event {
            margin: 1px 2px !important;
        }

        .fc-daygrid-day-events {
            min-height: 20px !important;
        }

        .fc-daygrid-day-frame {
            min-height: 100px !important;
        }

        /* Lista de Agendamentos */
        .lista-agendamentos {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 30px;
            border-radius: 15px;
            border: 2px solid #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .lista-agendamentos h2 {
            color: #08ebf3;
            font-size: 1.5em;
            margin-bottom: 25px;
            text-align: center;
            border-bottom: 2px solid #08ebf3;
            padding-bottom: 10px;
        }

        .agendamento-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #08ebf3;
            transition: all 0.3s ease;
        }

        .agendamento-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .agendamento-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .agendamento-titulo {
            color: #08ebf3;
            font-size: 1.2em;
            font-weight: bold;
            flex: 1;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            margin-left: 10px;
        }

        .status-pendente_confirmacao { background: #ff9800; color: white; }
        .status-agendado { background: #ff9800; color: white; }
        .status-confirmado { background: #4caf50; color: white; }
        .status-concluido { background: #2196f3; color: white; }

        .agendamento-info {
            color: #ccc;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-status {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin: 5px;
            font-size: 0.8em;
            transition: all 0.3s ease;
        }

        .btn-confirmar { background: #4caf50; color: white; }
        .btn-concluir { background: #2196f3; color: white; }
        .btn-cancelar { background: #f44336; color: white; }
        .btn-disabled { background: #666; color: #999; cursor: not-allowed; }

        .btn-status:hover:not(.btn-disabled) {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Botão de cancelamento para clientes */
        .btn-cancelar-cliente {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 10px;
            font-size: 0.8em;
            transition: all 0.3s ease;
        }

        .btn-cancelar-cliente:hover {
            background: #d32f2f;
            transform: translateY(-1px);
        }

        /* Mensagens */
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
            border: 2px solid #1e7e34;
        }

        .mensagem.erro {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 2px solid #ff4444;
        }

        .rodape-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            padding: 30px 20px;
            font-size: 0.9em;
            color: #a0a0a0;
            border-top: 1px solid #333;
            margin-top: 60px;
        }

        .rodape-logado a {
            color: #08ebf3;
            text-decoration: none;
            font-weight: 600;
            margin: 0 15px;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .rodape-logado a:hover {
            color: #00bcd4;
            background: rgba(255, 255, 255, 0.05);
        }

        @media (max-width: 768px) {
            .conteudo-principal {
                padding: 20px 15px;
            }
            
            .titulo-pagina {
                font-size: 2em;
            }
            
            .fc-toolbar {
                flex-direction: column;
                gap: 10px;
            }
            
            .agendamento-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .btn-criar-agendamento {
                margin-left: 0;
                margin-top: 10px;
                width: 100%;
                text-align: center;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="topo-logado">
        <div class="logo">
            <img src="../visualscript/imagem/logotcc.png" alt="TechAjuda">
        </div>
        <div class="nav-right">
            <a href="area_logada.php" class="nav-link">Menu Principal</a>
            <a href="minha_conta.php" class="nav-link">Minha Conta</a>
            <a href="logout.php" class="nav-link">Sair</a>
            <div class="account-circle">
                <?php if (!empty($_SESSION['usuario_foto'])): ?>
                    <img src="../<?php echo $_SESSION['usuario_foto']; ?>" alt="Foto Perfil"
                         style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #08ebf3, #007acc); display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-size: 18px;">👤</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="conteudo-principal">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                <a href="area_logada.php" class="voltar-link">Voltar ao Menu Principal</a>
                
                <!-- Botão para criar novo agendamento (apenas para técnicos) -->
                <?php if ($is_tecnico && isset($tecnico_id)): ?>
                    <a href="criar_agendamento.php" class="btn-criar-agendamento">
                        Criar Novo Agendamento
                    </a>
                <?php endif; ?>
            </div>
            
            <h1 class="titulo-pagina">Minha Agenda</h1>

            <?php if (isset($sucesso)): ?>
                <div class="mensagem sucesso"><?php echo $sucesso; ?></div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
                <div class="mensagem erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="agenda-container">
                <!-- Calendário -->
                <div class="calendar-container">
                    <div id="calendar"></div>
                </div>

                <!-- Lista de Agendamentos -->
                <div class="lista-agendamentos">
                    <h2>Meus Agendamentos</h2>
                    
                    <?php if (empty($agendamentos)): ?>
                        <div style="text-align: center; padding: 40px; color: #888;">
                            <h3 style="color: #08ebf3; margin-bottom: 10px;">Nenhum agendamento</h3>
                            <p>Você ainda não possui agendamentos.</p>
                            <?php if ($is_tecnico && isset($tecnico_id)): ?>
                                <p style="margin-top: 15px;">
                                    <a href="criar_agendamento.php" style="color: #08ebf3; text-decoration: none; font-weight: 600;">
                                        Clique aqui para criar seu primeiro agendamento
                                    </a>
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($agendamentos as $agendamento): ?>
                            <div class="agendamento-item">
                                <div class="agendamento-header">
                                    <div class="agendamento-titulo"><?php echo htmlspecialchars($agendamento['titulo']); ?></div>
                                    <span class="status-badge status-<?php echo $agendamento['status']; ?>">
                                        <?php 
                                        switch($agendamento['status']) {
                                            case 'pendente_confirmacao': echo 'Aguardando Confirmação'; break;
                                            case 'agendado': echo 'Agendado'; break;
                                            case 'confirmado': echo 'Confirmado'; break;
                                            case 'concluido': echo 'Concluído'; break;
                                        }
                                        ?>
                                    </span>
                                </div>
                                
                                <div class="agendamento-info">
                                    <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?>
                                    <?php echo date('H:i', strtotime($agendamento['hora_inicio'])); ?>
                                </div>
                                
                                <div class="agendamento-info">
                                    <?php echo $agendamento['local_atendimento'] === 'local_tecnico' ? 'Local do Técnico' : 'Local do Cliente'; ?>
                                </div>
                                
                                <div class="agendamento-info">
                                    <?php if ($is_tecnico): ?>
                                        Cliente: <?php echo htmlspecialchars($agendamento['cliente_nome']); ?>
                                    <?php else: ?>
                                        Técnico: <?php echo htmlspecialchars($agendamento['tecnico_nome']); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($agendamento['descricao'])): ?>
                                    <div class="agendamento-info">
                                        <?php echo htmlspecialchars($agendamento['descricao']); ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($agendamento['valor']): ?>
                                    <div class="agendamento-info">
                                        Valor: R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?>
                                    </div>
                                <?php endif; ?>

                                <!-- BOTÕES DE AÇÃO -->
                                <?php if ($is_tecnico): ?>
                                    <!-- BOTÕES PARA TÉCNICO -->
                                    <div style="margin-top: 15px;">
                                        <?php if ($agendamento['status'] === 'agendado'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="atualizar_status" value="1">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <input type="hidden" name="novo_status" value="confirmado">
                                                <button type="submit" class="btn-status btn-confirmar">Confirmar</button>
                                            </form>
                                        <?php elseif ($agendamento['status'] === 'confirmado'): ?>
                                            <?php
                                            // Verificar se a data do agendamento já passou
                                            $data_agendamento = new DateTime($agendamento['data_agendamento'] . ' ' . $agendamento['hora_inicio']);
                                            $agora = new DateTime();
                                            $pode_concluir = $data_agendamento <= $agora;
                                            ?>
                                            
                                            <?php if ($pode_concluir): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="atualizar_status" value="1">
                                                    <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                    <input type="hidden" name="novo_status" value="concluido">
                                                    <button type="submit" class="btn-status btn-concluir">Concluir</button>
                                                </form>
                                            <?php else: ?>
                                                <button type="button" class="btn-status btn-disabled" 
                                                        title="Disponível apenas após a data do agendamento">
                                                    Concluir (após <?php echo date('d/m', strtotime($agendamento['data_agendamento'])); ?>)
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        
                                        <!-- Botão de Cancelamento - Disponível para todos os status exceto concluído -->
                                        <?php if ($agendamento['status'] !== 'concluido'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="atualizar_status" value="1">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <input type="hidden" name="novo_status" value="cancelado">
                                                <button type="submit" class="btn-status btn-cancelar" 
                                                        onclick="return confirm('Tem certeza que deseja cancelar este agendamento? Esta ação não pode ser desfeita.')">
                                                    Cancelar
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- BOTÕES PARA CLIENTE -->
                                    <div style="margin-top: 15px;">
                                        <?php if ($agendamento['status'] === 'agendado'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="confirmar_agendamento" value="1">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <button type="submit" class="btn-status btn-confirmar">Confirmar Agendamento</button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <!-- Botão de Cancelamento - Disponível para todos os status exceto concluído -->
                                        <?php if ($agendamento['status'] !== 'concluido'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="cancelar_agendamento" value="1">
                                                <input type="hidden" name="agendamento_id" value="<?php echo $agendamento['id']; ?>">
                                                <button type="submit" class="btn-cancelar-cliente" 
                                                        onclick="return confirm('Tem certeza que deseja cancelar este agendamento? Esta ação não pode ser desfeita.')">
                                                    Cancelar Agendamento
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="rodape-logado">
        <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
        <p>
            <a href="suporte2.php">Suporte</a> |
            <a href="suporte2.php#termos">Termos de Uso</a> |
            <a href="suporte2.php#politica">Política de Privacidade</a>
        </p>
    </footer>

    <!-- FullCalendar JS -->
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // URL dos eventos
            const eventsUrl = 'agenda_events.php?<?php echo $is_tecnico ? "tecnico_id=$tecnico_id" : "cliente_id=$usuario_id"; ?>';
            
            // Inicializar calendário
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Hoje',
                    month: 'Mês',
                    week: 'Semana',
                    day: 'Dia'
                },
                events: eventsUrl,
                
                // Configurações importantes para visualização
                eventDisplay: 'block',
                displayEventTime: true,
                displayEventEnd: true,
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                },
                
                // Para garantir que eventos apareçam
                dayMaxEvents: 4,
                dayMaxEventRows: 4,
                
                eventClick: function(info) {
                    const event = info.event;
                    const props = event.extendedProps;
                    
                    // Na agenda normal, NÃO está visualizando outro técnico
                    const visualizandoOutroTecnico = false;
                    
                    // Criar modal personalizado
                    criarModalEvento(event, visualizandoOutroTecnico);
                },
                
                // Debug
                eventDidMount: function(info) {
                    console.log('Evento montado:', info.event.title);
                },
                
                loading: function(isLoading) {
                    if (!isLoading) {
                        const events = calendar.getEvents();
                        console.log('Total de eventos carregados:', events.length);
                    }
                }
            });

            calendar.render();

            // ⭐⭐ SISTEMA SIMPLES DE ALERTAS - COLE AQUI ⭐⭐
            // Sistema SIMPLES de alertas visuais - APENAS NA TELA
            function verificarAlertasSimples() {
                const eventos = calendar.getEvents();
                const agora = new Date();
                
                console.log('🔍 Verificando alertas para', eventos.length, 'eventos');
                
                eventos.forEach(evento => {
                    const dataEvento = new Date(evento.start);
                    const diferenca = dataEvento - agora;
                    
                    // Verificar se o evento é hoje (em até 24 horas)
                    if (diferenca > 0 && diferenca <= 24 * 60 * 60 * 1000) {
                        console.log('📅 Evento hoje:', evento.title);
                        mostrarAlertaSimples(evento);
                    }
                    
                    // Verificar se o evento é amanhã (entre 24h e 48h)
                    if (diferenca > 24 * 60 * 60 * 1000 && diferenca <= 48 * 60 * 60 * 1000) {
                        console.log('📅 Evento amanhã:', evento.title);
                        mostrarAlertaSimples(evento, 'amanha');
                    }
                });
            }

            function mostrarAlertaSimples(evento, tipo = 'hoje') {
                const mensagens = {
                    'hoje': `⏰ HOJE: "${evento.title}" às ${evento.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}`,
                    'amanha': `🔔 AMANHÃ: "${evento.title}"`
                };
                
                // Verificar se já mostrou alerta para este evento hoje
                const alertaKey = `alerta_${evento.id}_${new Date().toDateString()}`;
                if (localStorage.getItem(alertaKey)) {
                    console.log('⏭️ Alerta já mostrado hoje para:', evento.title);
                    return; // Já mostrou hoje, não mostrar de novo
                }
                
                // Marcar que já mostrou o alerta hoje
                localStorage.setItem(alertaKey, 'true');
                
                // Criar alerta SIMPLES na página
                const alerta = document.createElement('div');
                alerta.style.cssText = `
                    position: fixed;
                    top: 120px;
                    right: 20px;
                    background: linear-gradient(135deg, #ff9800, #ff5722);
                    color: white;
                    padding: 15px 20px;
                    border-radius: 10px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                    z-index: 1001;
                    max-width: 300px;
                    font-weight: bold;
                    border-left: 5px solid #ffeb3b;
                    animation: slideInRight 0.5s ease-out;
                `;
                
                alerta.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 1.3em;">${tipo === 'hoje' ? '⏰' : '🔔'}</span>
                        <div style="flex: 1;">
                            <div style="margin-bottom: 5px;">${mensagens[tipo]}</div>
                            <div style="font-size: 0.8em; opacity: 0.9;">
                                ${evento.start.toLocaleDateString('pt-BR')} • 
                                ${evento.extendedProps.tecnico_nome || evento.extendedProps.cliente_nome || 'Agendamento'}
                            </div>
                        </div>
                        <button onclick="this.parentElement.parentElement.remove()" 
                                style="background: none; border: none; color: white; font-size: 1.2em; cursor: pointer; padding: 5px;">
                            ×
                        </button>
                    </div>
                `;
                
                document.body.appendChild(alerta);
                
                // Auto-remover após 8 segundos
                setTimeout(() => {
                    if (alerta.parentElement) {
                        alerta.remove();
                    }
                }, 8000);
                
                console.log(`🔔 Alerta ${tipo} mostrado:`, evento.title);
            }

            // CSS para animação
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInRight {
                    from {
                        transform: translateX(100%);
                        opacity: 0;
                    }
                    to {
                        transform: translateX(0);
                        opacity: 1;
                    }
                }
                
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
            `;
            document.head.appendChild(style);

            // Executar os alertas quando a página carregar (3 segundos depois)
            setTimeout(verificarAlertasSimples, 3000);

            // Função para criar modal personalizado
            function criarModalEvento(event, visualizandoOutroTecnico) {
                const props = event.extendedProps;
                const statusText = {
                    'pendente_confirmacao': 'Aguardando Confirmação',
                    'agendado': 'Agendado',
                    'confirmado': 'Confirmado',
                    'concluido': 'Concluído'
                };
                
                // Criar overlay
                const overlay = document.createElement('div');
                overlay.className = 'modal-overlay-evento';
                overlay.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 2000;
                    animation: fadeIn 0.3s ease-out;
                `;
                
                // Criar conteúdo do modal
                const modalContent = document.createElement('div');
                modalContent.className = 'modal-conteudo-evento';
                modalContent.style.cssText = `
                    background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
                    border-radius: 15px;
                    padding: 0;
                    max-width: 500px;
                    width: 90%;
                    border: 2px solid #08ebf3;
                    box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
                    animation: slideDown 0.4s ease-out;
                `;
                
                // Header do modal
                const modalHeader = document.createElement('div');
                modalHeader.style.cssText = `
                    background: linear-gradient(135deg, #08ebf3, #00bcd4);
                    padding: 20px;
                    border-radius: 13px 13px 0 0;
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                `;
                
                const titulo = document.createElement('h3');
                titulo.textContent = event.title;
                titulo.style.cssText = `
                    color: #001a33;
                    margin: 0;
                    font-size: 1.4em;
                    font-weight: 700;
                `;
                
                const btnFechar = document.createElement('button');
                btnFechar.textContent = '×';
                btnFechar.style.cssText = `
                    background: none;
                    border: none;
                    color: #001a33;
                    font-size: 1.8em;
                    cursor: pointer;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    border-radius: 50%;
                    transition: background 0.3s;
                `;
                btnFechar.onmouseover = () => btnFechar.style.background = 'rgba(0, 0, 0, 0.1)';
                btnFechar.onmouseout = () => btnFechar.style.background = 'none';
                btnFechar.onclick = () => document.body.removeChild(overlay);
                
                modalHeader.appendChild(titulo);
                modalHeader.appendChild(btnFechar);
                
                // Body do modal
                const modalBody = document.createElement('div');
                modalBody.style.cssText = `
                    padding: 25px;
                    color: #e0e0e0;
                `;
                
                // Informações básicas (sempre visíveis)
                const infoBasica = document.createElement('div');
                infoBasica.innerHTML = `
                    <div style="margin-bottom: 15px;">
                        <p><strong>Data:</strong> ${event.start.toLocaleDateString('pt-BR')}</p>
                        <p><strong>Horário:</strong> ${event.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute:'2-digit'})}</p>
                        <p><strong>Status:</strong> ${statusText[props.status] || props.status}</p>
                    </div>
                `;
                
                // Descrição (sempre visível)
                const descricao = document.createElement('div');
                if (props.descricao) {
                    descricao.innerHTML = `
                        <div style="margin-bottom: 15px;">
                            <p><strong>Descrição do Serviço:</strong></p>
                            <div style="background: rgba(255, 255, 255, 0.05); padding: 15px; border-radius: 8px; border-left: 3px solid #08ebf3;">
                                ${props.descricao}
                            </div>
                        </div>
                    `;
                }
                
                // Informações sensíveis (borradas se visualizando outro técnico)
                const infoSensivel = document.createElement('div');
                if (visualizandoOutroTecnico) {
                    // Modo visualização - informações borradas
                    infoSensivel.innerHTML = `
                        <div style="margin-bottom: 15px;">
                            <p><strong>Local:</strong> <span style="filter: blur(3px); user-select: none;">${props.local === 'local_tecnico' ? 'Local do Técnico' : 'Local do Cliente'}</span></p>
                            <p><strong>Cliente:</strong> <span style="filter: blur(3px); user-select: none;">Informação confidencial</span></p>
                            <p><strong>Valor:</strong> <span style="filter: blur(3px); user-select: none;">Informação confidencial</span></p>
                        </div>
                        <div style="background: rgba(255, 193, 7, 0.1); padding: 10px; border-radius: 8px; border: 1px solid #ffc107; text-align: center;">
                            <small>Algumas informações estão ocultas para proteger a privacidade</small>
                        </div>
                    `;
                } else {
                    // Modo normal - todas informações visíveis
                    infoSensivel.innerHTML = `
                        <div style="margin-bottom: 15px;">
                            <p><strong>Local:</strong> ${props.local === 'local_tecnico' ? 'Local do Técnico' : 'Local do Cliente'}</p>
                            ${props.cliente_nome ? `<p><strong>Cliente:</strong> ${props.cliente_nome}</p>` : ''}
                            ${props.tecnico_nome ? `<p><strong>Técnico:</strong> ${props.tecnico_nome}</p>` : ''}
                            ${props.valor ? `<p><strong>Valor:</strong> R$ ${parseFloat(props.valor).toFixed(2).replace('.', ',')}</p>` : ''}
                        </div>
                    `;
                }
                
                modalBody.appendChild(infoBasica);
                modalBody.appendChild(descricao);
                modalBody.appendChild(infoSensivel);
                
                // Fechar clicando fora
                overlay.onclick = (e) => {
                    if (e.target === overlay) {
                        document.body.removeChild(overlay);
                    }
                };
                
                // Fechar com ESC
                const fecharComESC = (e) => {
                    if (e.key === 'Escape') {
                        document.body.removeChild(overlay);
                        document.removeEventListener('keydown', fecharComESC);
                    }
                };
                document.addEventListener('keydown', fecharComESC);
                
                // Montar modal
                modalContent.appendChild(modalHeader);
                modalContent.appendChild(modalBody);
                overlay.appendChild(modalContent);
                document.body.appendChild(overlay);
                
                // Remover listener quando modal fechar
                overlay.addEventListener('animationend', function handler() {
                    if (overlay.style.animationName === 'fadeOut') {
                        document.removeEventListener('keydown', fecharComESC);
                        overlay.removeEventListener('animationend', handler);
                    }
                });
            }

            // Adicionar estilos de animação
            const modalStyle = document.createElement('style');
            modalStyle.textContent = `
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                
                @keyframes slideDown {
                    from {
                        opacity: 0;
                        transform: translateY(-30px) scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0) scale(1);
                    }
                }
                
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                
                .modal-overlay-evento {
                    cursor: pointer;
                }
                
                .modal-conteudo-evento {
                    cursor: default;
                }
            `;
            document.head.appendChild(modalStyle);

        }); // ⬅️ FIM do document.addEventListener
    </script>
</body>
</html>