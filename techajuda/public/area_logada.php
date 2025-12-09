<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

// Verificar se deve mostrar mensagem de boas-vindas para técnico
$mostrar_mensagem_tecnico = false;
if (isset($_SESSION['mostrar_mensagem_tecnico']) && $_SESSION['mostrar_mensagem_tecnico']) {
    $mostrar_mensagem_tecnico = true;
    unset($_SESSION['mostrar_mensagem_tecnico']);
}

// Verificar se usuário é técnico
$is_tecnico = $_SESSION['is_tecnico'] ?? false;

// Verificar mensagens de sessão
$mensagem_sucesso = '';
$mensagem_info = '';

if (isset($_SESSION['mensagem_sucesso'])) {
    $mensagem_sucesso = $_SESSION['mensagem_sucesso'];
    unset($_SESSION['mensagem_sucesso']);
}

if (isset($_SESSION['mensagem_info'])) {
    $mensagem_info = $_SESSION['mensagem_info'];
    unset($_SESSION['mensagem_info']);
}

// Buscar categorias para os filtros
$conn = getDBConnection();
$sql_categorias = "SELECT id, nome FROM categorias_servico ORDER BY nome";
$result_categorias = $conn->query($sql_categorias);
$categorias = [];
if ($result_categorias->num_rows > 0) {
    while($row = $result_categorias->fetch_assoc()) {
        $categorias[] = $row;
    }
}

// Buscar cidades e estados disponíveis
$sql_localizacao = "SELECT DISTINCT cidade, estado FROM usuarios WHERE cidade IS NOT NULL AND estado IS NOT NULL ORDER BY estado, cidade";
$result_localizacao = $conn->query($sql_localizacao);
$cidades = [];
$estados = [];
if ($result_localizacao->num_rows > 0) {
    while($row = $result_localizacao->fetch_assoc()) {
        $cidades[] = $row['cidade'];
        if (!in_array($row['estado'], $estados)) {
            $estados[] = $row['estado'];
        }
    }
}
sort($cidades);
sort($estados);

// Verificar status do técnico
$usuario_id = $_SESSION['usuario_id'];
$sql_status_tecnico = "SELECT is_tecnico, pedido_tecnico_pendente FROM usuarios WHERE id = '$usuario_id'";
$result_status = $conn->query($sql_status_tecnico);
$status_tecnico = $result_status->fetch_assoc();

// Buscar agendamentos pendentes de confirmação (apenas para clientes)
$agendamentos_pendentes = 0;
$agendamentos_pendentes_detalhes = [];
if (!$is_tecnico) {
    // Primeiro, vamos fazer um debug para ver o que está acontecendo
    $sql_debug = "SELECT id, status FROM agendamentos WHERE cliente_id = ?";
    $stmt_debug = $conn->prepare($sql_debug);
    $stmt_debug->bind_param('i', $usuario_id);
    $stmt_debug->execute();
    $result_debug = $stmt_debug->get_result();
    
    // Debug: ver todos os agendamentos do usuário
    $debug_agendamentos = [];
    while ($row = $result_debug->fetch_assoc()) {
        $debug_agendamentos[] = $row;
    }
    $stmt_debug->close();
    
    // Agora busca os pendentes
    $sql_agendamentos_pendentes = "SELECT COUNT(*) as total FROM agendamentos 
                                  WHERE cliente_id = ? AND status = 'pendente_confirmacao'";
    $stmt_pendentes = $conn->prepare($sql_agendamentos_pendentes);
    $stmt_pendentes->bind_param('i', $usuario_id);
    $stmt_pendentes->execute();
    $result_pendentes = $stmt_pendentes->get_result();
    $agendamentos_pendentes = $result_pendentes->fetch_assoc()['total'];
    $stmt_pendentes->close();

    // Buscar detalhes dos agendamentos pendentes para a notificação
    if ($agendamentos_pendentes > 0) {
        $sql_detalhes = "SELECT a.*, u.nome as tecnico_nome 
                         FROM agendamentos a
                         INNER JOIN tecnicos t ON a.tecnico_id = t.id
                         INNER JOIN usuarios u ON t.usuario_id = u.id
                         WHERE a.cliente_id = ? AND a.status = 'pendente_confirmacao'
                         ORDER BY a.created_at DESC
                         LIMIT 1";
        $stmt_detalhes = $conn->prepare($sql_detalhes);
        $stmt_detalhes->bind_param('i', $usuario_id);
        $stmt_detalhes->execute();
        $agendamentos_pendentes_detalhes = $stmt_detalhes->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt_detalhes->close();
    }
}

// Buscar preferências de alerta do usuário
$preferencia_alerta = '1h'; // Padrão: 1 hora
$sql_preferencia = "SELECT valor FROM preferencias_usuario WHERE usuario_id = ? AND chave = 'alerta_agendamento'";
$stmt_preferencia = $conn->prepare($sql_preferencia);
$stmt_preferencia->bind_param('i', $usuario_id);
$stmt_preferencia->execute();
$result_preferencia = $stmt_preferencia->get_result();
if ($result_preferencia->num_rows > 0) {
    $preferencia = $result_preferencia->fetch_assoc();
    $preferencia_alerta = $preferencia['valor'];
}
$stmt_preferencia->close();

// Processar atualização da preferência de alerta
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['atualizar_alerta'])) {
    $novo_alerta = $_POST['alerta_tempo'];
    
    // Verificar se já existe uma preferência
    $sql_verificar = "SELECT id FROM preferencias_usuario WHERE usuario_id = ? AND chave = 'alerta_agendamento'";
    $stmt_verificar = $conn->prepare($sql_verificar);
    $stmt_verificar->bind_param('i', $usuario_id);
    $stmt_verificar->execute();
    
    if ($stmt_verificar->get_result()->num_rows > 0) {
        // Atualizar
        $sql_update = "UPDATE preferencias_usuario SET valor = ? WHERE usuario_id = ? AND chave = 'alerta_agendamento'";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('si', $novo_alerta, $usuario_id);
    } else {
        // Inserir
        $sql_update = "INSERT INTO preferencias_usuario (usuario_id, chave, valor) VALUES (?, 'alerta_agendamento', ?)";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param('is', $usuario_id, $novo_alerta);
    }
    
    if ($stmt_update->execute()) {
        $preferencia_alerta = $novo_alerta;
        $_SESSION['mensagem_sucesso'] = "Preferência de alerta atualizada com sucesso!";
        header("Location: area_logada.php");
        exit();
    }
    $stmt_update->close();
    $stmt_verificar->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Principal - TechAjuda</title>
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
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Notificação de Agendamento Pendente */
        .notificacao-overlay {
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
        }

        .notificacao-conteudo {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 0;
            max-width: 500px;
            width: 90%;
            border: 2px solid #08ebf3;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            animation: slideDown 0.4s ease-out;
        }

        .notificacao-header {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            padding: 20px;
            border-radius: 13px 13px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notificacao-header h3 {
            color: #001a33;
            margin: 0;
            font-size: 1.4em;
            font-weight: 700;
        }

        .fechar-notificacao {
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
        }

        .fechar-notificacao:hover {
            background: rgba(0, 0, 0, 0.1);
        }

        .notificacao-body {
            padding: 25px;
            color: #e0e0e0;
        }

        .info-agendamento p {
            margin-bottom: 10px;
            line-height: 1.5;
        }

        .botoes-acao {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            justify-content: center;
        }

        .btn-confirmar-notificacao {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-confirmar-notificacao:hover {
            background: linear-gradient(135deg, #20c997, #1e9c7a);
            transform: translateY(-2px);
        }

        .btn-ver-todos {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .btn-ver-todos:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
        }

        /* Bolinha de notificação */
        .notificacao-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7em;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        /* Modal de Preferências */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 2000;
        }

        .modal-conteudo {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 30px;
            max-width: 500px;
            width: 90%;
            border: 2px solid #08ebf3;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #333;
        }

        .modal-header h3 {
            color: #08ebf3;
            margin: 0;
        }

        .fechar-modal {
            background: none;
            border: none;
            color: #e0e0e0;
            font-size: 1.5em;
            cursor: pointer;
        }

        .form-group-modal {
            margin-bottom: 20px;
        }

        .form-group-modal label {
            display: block;
            margin-bottom: 8px;
            color: #08ebf3;
            font-weight: 600;
        }

        .form-control-modal {
            width: 100%;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #333;
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 1em;
        }

        .btn-salvar-preferences {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s ease;
        }

        .btn-salvar-preferences:hover {
            background: linear-gradient(135deg, #20c997, #1e9c7a);
            transform: translateY(-2px);
        }

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

        /* Mensagens de Sistema */
        .mensagem-sistema {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 20px;
            border-radius: 8px;
            font-weight: 600;
            z-index: 1000;
            animation: slideDown 0.5s ease-out, fadeOut 0.5s ease-in 4s forwards;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            text-align: center;
            font-size: 0.9em;
        }

        .mensagem-sucesso {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: 2px solid #1e7e34;
        }

        .mensagem-info {
            background: linear-gradient(135deg, #17a2b8, #6f42c1);
            color: white;
            border: 2px solid #117a8b;
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
                visibility: hidden;
            }
        }

        /* Header Menor */
        .topo-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 0 15px;
            height: 70px;
            box-shadow: 0 3px 15px rgba(0, 0, 0, 0.5);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #333;
        }

        .logo {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .logo img {
            height: 140px;
            width: auto;
            margin-left: 5px;
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
            margin-right: 15px;
        }

        .nav-link {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: all 0.3s ease;
            padding: 8px 12px;
            border-radius: 6px;
            position: relative;
            overflow: hidden;
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
            width: 35px;
            height: 35px;
            border-radius: 50%;
            margin-left: 10px;
            overflow: hidden;
            border: 2px solid #08ebf3;
            transition: all 0.3s ease;
            box-shadow: 0 0 12px rgba(8, 235, 243, 0.3);
        }

        .account-circle:hover {
            transform: scale(1.1);
            box-shadow: 0 0 15px rgba(8, 235, 243, 0.5);
        }

        main {
            flex: 1;
            padding-top: 90px;
        }

        .conteudo-principal {
            padding: 30px 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .bem-vindo {
            font-size: 2em;
            margin-bottom: 25px;
            color: #08ebf3;
            text-align: center;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(8, 235, 243, 0.3);
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Nova Seção de Pesquisa - AGORA COM FORM CORRETO */
        .pesquisa-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 35px;
            border-radius: 15px;
            margin-bottom: 35px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            position: relative;
            overflow: hidden;
        }

        .pesquisa-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(8, 235, 243, 0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .pesquisa-titulo {
            color: #08ebf3;
            margin-bottom: 12px;
            font-size: 1.8em;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }

        .pesquisa-subtitulo {
            color: #b0b0b0;
            margin-bottom: 25px;
            font-size: 1em;
            position: relative;
            z-index: 2;
        }

        /* FORMULÁRIO CORRETO - TODOS OS CAMPOS DENTRO DO FORM */
        #formPesquisa {
            position: relative;
            z-index: 2;
        }

        /* Barra de Pesquisa Principal */
        .barra-pesquisa {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            max-width: 800px;
            margin: 0 auto 15px;
        }

        .campo-pesquisa {
            flex: 1;
            min-width: 280px;
            padding: 15px 18px;
            border: none;
            border-radius: 10px;
            font-size: 0.95em;
            background: rgba(255, 255, 255, 0.95);
            color: #333;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .campo-pesquisa:focus {
            outline: none;
            border-color: #08ebf3;
            box-shadow: 0 3px 15px rgba(8, 235, 243, 0.3);
            transform: translateY(-2px);
        }

        .campo-pesquisa::placeholder {
            color: #666;
        }

        .botao-filtros {
            background: linear-gradient(135deg, #ff6b6b, #ee5a52);
            color: white;
            border: none;
            padding: 15px 22px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            white-space: nowrap;
            box-shadow: 0 3px 12px rgba(255, 107, 107, 0.3);
        }

        .botao-filtros:hover {
            background: linear-gradient(135deg, #ee5a52, #ff4757);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 107, 0.4);
        }

        .botao-buscar {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            white-space: nowrap;
            box-shadow: 0 3px 12px rgba(8, 235, 243, 0.3);
        }

        .botao-buscar:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 235, 243, 0.4);
        }

        /* Seção de Filtros - AGORA DENTRO DO FORM */
        .filtros-section {
            background: rgba(26, 26, 26, 0.95);
            padding: 25px;
            border-radius: 12px;
            margin-top: 20px;
            display: none;
            backdrop-filter: blur(10px);
            border: 1px solid #333;
        }

        .filtros-titulo {
            color: #08ebf3;
            margin-bottom: 20px;
            font-size: 1.3em;
            text-align: center;
            font-weight: 600;
        }

        .filtros-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .filtro-group {
            margin-bottom: 15px;
            background: rgba(255, 255, 255, 0.05);
            padding: 18px;
            border-radius: 8px;
            border: 1px solid #333;
            transition: all 0.3s ease;
        }

        .filtro-group:hover {
            border-color: #08ebf3;
            background: rgba(255, 255, 255, 0.08);
        }

        .filtro-label {
            display: block;
            color: #08ebf3;
            margin-bottom: 10px;
            font-weight: 600;
            font-size: 0.95em;
        }

        .filtro-select, .filtro-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #444;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 0.95em;
            box-sizing: border-box;
            transition: all 0.3s ease;
        }

        .filtro-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'><path fill='%2308ebf3' d='M2 0L0 2h4zm0 5L0 3h4z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 10px;
            padding-right: 35px;
        }

        .filtro-select option {
            background: #2a2a2a;
            color: white;
            padding: 8px;
        }

        .filtro-select:focus, .filtro-input:focus {
            border-color: #08ebf3;
            outline: none;
            box-shadow: 0 0 12px rgba(8, 235, 243, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        /* Checkboxes */
        .checkbox-container {
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin-top: 8px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: 6px;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid transparent;
        }

        .checkbox-group:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: #08ebf3;
        }

        .checkbox-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: #08ebf3;
            cursor: pointer;
        }

        .checkbox-group label {
            color: #e0e0e0;
            font-size: 0.9em;
            cursor: pointer;
            flex: 1;
        }

        .botoes-filtros {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .botao-aplicar {
            background: linear-gradient(135deg, #00cc00, #00aa00);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 3px 12px rgba(0, 204, 0, 0.3);
            font-size: 0.95em;
        }

        .botao-aplicar:hover {
            background: linear-gradient(135deg, #00aa00, #008800);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 204, 0, 0.4);
        }

        .botao-limpar {
            background: linear-gradient(135deg, #666, #555);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
        }

        .botao-limpar:hover {
            background: linear-gradient(135deg, #555, #444);
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.3);
        }

        /* Seção de Ações Rápidas */
        .acoes-rapidas {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-top: 35px;
        }

        .acao-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 25px 20px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.4s ease;
            border: 1px solid #333;
            position: relative;
            overflow: hidden;
        }

        .acao-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.1), transparent);
            transition: left 0.6s;
        }

        .acao-card:hover::before {
            left: 100%;
        }

        .acao-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 25px rgba(0, 0, 0, 0.4);
            border-color: #08ebf3;
        }

        .acao-icone {
            font-size: 2.5em;
            margin-bottom: 15px;
            display: block;
            transition: transform 0.3s ease;
        }

        .acao-card:hover .acao-icone {
            transform: scale(1.1);
        }

        .acao-titulo {
            color: #08ebf3;
            font-size: 1.2em;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .acao-descricao {
            color: #b0b0b0;
            font-size: 0.9em;
            line-height: 1.4;
        }

        /* Status do Técnico */
        .status-tecnico {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 15px auto;
            max-width: 380px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
            border: 2px solid #ff8c42;
            font-size: 0.9em;
        }

        .status-tecnico.aprovado {
            background: linear-gradient(135deg, #28a745, #20c997);
            border-color: #1e7e34;
        }

        /* Footer Menor */
        .rodape-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            padding: 20px;
            font-size: 0.85em;
            color: #888;
            margin-top: 40px;
            border-top: 1px solid #333;
        }

        .rodape-logado p {
            margin: 6px 0;
        }

        .rodape-logado a {
            color: #08ebf3;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
            transition: all 0.3s ease;
            position: relative;
        }

        .rodape-logado a::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #08ebf3;
            transition: width 0.3s ease;
        }

        .rodape-logado a:hover::after {
            width: 100%;
        }

        .rodape-logado a:hover {
            color: #00bcd4;
        }

        @media (max-width: 768px) {
            .barra-pesquisa {
                flex-direction: column;
            }
            
            .campo-pesquisa {
                min-width: 100%;
            }
            
            .filtros-grid {
                grid-template-columns: 1fr;
            }
            
            .botoes-filtros {
                flex-direction: column;
            }
            
            .nav-right {
                gap: 8px;
            }
            
            .nav-link {
                font-size: 0.85em;
                padding: 6px 10px;
            }
            
            .logo img {
                height: 120px;
            }
            
            .bem-vindo {
                font-size: 1.7em;
            }
            
            .topo-logado {
                height: 65px;
                padding: 0 10px;
            }
            
            main {
                padding-top: 80px;
            }
        }

        @media (max-width: 480px) {
            .topo-logado {
                padding: 0 8px;
            }
            
            .nav-right {
                gap: 6px;
            }
            
            .nav-link {
                font-size: 0.8em;
                padding: 5px 8px;
            }
            
            .logo img {
                height: 100px;
            }
            
            .pesquisa-section {
                padding: 25px 15px;
            }
            
            .acao-card {
                padding: 20px 15px;
            }
            
            .account-circle {
                width: 30px;
                height: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Mensagens do Sistema -->
    <?php if ($mensagem_sucesso): ?>
        <div class="mensagem-sistema mensagem-sucesso">
            <?php echo $mensagem_sucesso; ?>
        </div>
    <?php endif; ?>

    <?php if ($mensagem_info): ?>
        <div class="mensagem-sistema mensagem-info">
            <?php echo $mensagem_info; ?>
        </div>
    <?php endif; ?>

    <!-- Notificação de Agendamento Pendente -->
    <?php if (!$is_tecnico && !empty($agendamentos_pendentes_detalhes)): 
        $agendamento = $agendamentos_pendentes_detalhes[0];
    ?>
    <div id="notificacaoAgendamento" class="notificacao-overlay">
        <div class="notificacao-conteudo">
            <div class="notificacao-header">
                <h3>Novo Agendamento Solicitado</h3>
                <button class="fechar-notificacao" onclick="fecharNotificacao()">×</button>
            </div>
            <div class="notificacao-body">
                <div class="info-agendamento">
                    <p><strong>Técnico:</strong> <?php echo htmlspecialchars($agendamento['tecnico_nome']); ?></p>
                    <p><strong>Serviço:</strong> <?php echo htmlspecialchars($agendamento['titulo']); ?></p>
                    <p><strong>Data:</strong> <?php echo date('d/m/Y', strtotime($agendamento['data_agendamento'])); ?></p>
                    <p><strong>Horário:</strong> <?php echo date('H:i', strtotime($agendamento['hora_inicio'])); ?></p>
                    <p><strong>Local:</strong> <?php echo $agendamento['local_atendimento'] === 'local_tecnico' ? 'Local do Técnico' : 'Seu Local'; ?></p>
                    <?php if (!empty($agendamento['descricao'])): ?>
                        <p><strong>Descrição:</strong> <?php echo htmlspecialchars($agendamento['descricao']); ?></p>
                    <?php endif; ?>
                    <?php if ($agendamento['valor']): ?>
                        <p><strong>Valor:</strong> R$ <?php echo number_format($agendamento['valor'], 2, ',', '.'); ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="botoes-acao">
                    <a href="confirmar_agendamentos.php" class="btn-confirmar-notificacao">
                        Ver e Confirmar Agendamentos
                    </a>
                    <button class="btn-ver-todos" onclick="fecharNotificacao()">
                        Ver Mais Tarde
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal de Preferências de Alerta -->
    <div id="modalPreferencias" class="modal-overlay">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h3>Configurar Alertas de Agendamento</h3>
                <button class="fechar-modal" onclick="fecharModal()">×</button>
            </div>
            <form method="POST">
                <div class="form-group-modal">
                    <label for="alerta_tempo">Receber alerta para eventos próximos:</label>
                    <select name="alerta_tempo" id="alerta_tempo" class="form-control-modal">
                        <option value="30min" <?php echo $preferencia_alerta === '30min' ? 'selected' : ''; ?>>30 minutos antes</option>
                        <option value="1h" <?php echo $preferencia_alerta === '1h' ? 'selected' : ''; ?>>1 hora antes</option>
                        <option value="2h" <?php echo $preferencia_alerta === '2h' ? 'selected' : ''; ?>>2 horas antes</option>
                        <option value="6h" <?php echo $preferencia_alerta === '6h' ? 'selected' : ''; ?>>6 horas antes</option>
                        <option value="1d" <?php echo $preferencia_alerta === '1d' ? 'selected' : ''; ?>>1 dia antes</option>
                        <option value="none" <?php echo $preferencia_alerta === 'none' ? 'selected' : ''; ?>>Não receber alertas</option>
                    </select>
                </div>
                <button type="submit" name="atualizar_alerta" class="btn-salvar-preferences">
                    Salvar Preferências
                </button>
            </form>
        </div>
    </div>

    <!-- Header Menor -->
    <header class="topo-logado">
        <div class="logo">
            <img src="../visualscript/imagem/logotcc.png" alt="TechAjuda">
        </div>
        <div class="nav-right">
            <a href="area_logada.php" class="nav-link">Menu Principal</a>
            
            <!-- MOSTRAR APENAS SE NÃO FOR TÉCNICO E NÃO TIVER PEDIDO PENDENTE -->
            <?php if (!$is_tecnico && !$status_tecnico['pedido_tecnico_pendente']): ?>
                <a href="tecnico/tornar_tecnico.php" class="nav-link">Tornar Técnico</a>
            <?php elseif (!$is_tecnico && $status_tecnico['pedido_tecnico_pendente']): ?>
                <a href="tecnico/tornar_tecnico.php" class="nav-link" style="opacity: 0.6; cursor: not-allowed;" onclick="return false;" title="Pedido em análise">Pedido em Análise</a>
            <?php endif; ?>
            
            <a href="minha_conta.php" class="nav-link">Minha Conta</a>      
            
            <!-- Botão de Preferências de Alerta (apenas para clientes) -->
            <?php if (!$is_tecnico): ?>
                <a href="#" class="nav-link" onclick="abrirModal()">Alertas</a>
            <?php endif; ?>
            
            <?php if ($_SESSION['usuario_id'] == 1): ?>
                <a href="admin/dashboard_admin.php" class="nav-link">Área Admin</a>
            <?php endif; ?>
            
            <a href="logout.php" class="nav-link">Sair</a>
            
            <div class="account-circle">
                <?php if (!empty($_SESSION['usuario_foto'])): ?>
                    <img src="../<?php echo $_SESSION['usuario_foto']; ?>" alt="Foto Perfil"
                         style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #666, #888); display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-size: 16px;">👤</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <!-- Mensagem de Boas-Vindas para Técnico (Primeiro Login) -->
        <?php if ($mostrar_mensagem_tecnico && $is_tecnico): ?>
            <div class="notificacao-overlay" id="mensagemBoasVindas">
                <div class="notificacao-conteudo">
                    <div class="notificacao-header">
                        <h3>Parabéns, você agora é um Técnico!</h3>
                        <button class="fechar-notificacao" onclick="fecharMensagem()">×</button>
                    </div>
                    <div class="notificacao-body">
                        <p><strong>Bem-vindo à nossa rede de profissionais!</strong></p>
                        <p>Agora você pode:</p>
                        <ul style="margin: 15px 0; padding-left: 20px;">
                            <li style="margin-bottom: 8px;"><strong>Receber contatos</strong> de clientes pelo seu WhatsApp</li>
                            <li style="margin-bottom: 8px;"><strong>Gerenciar uma agenda</strong> com seus serviços</li>
                            <li style="margin-bottom: 8px;"><strong>Atender tanto usuários comuns quanto outros técnicos</strong></li>
                            <li style="margin-bottom: 8px;"><strong>Continuar pesquisando</strong> por outros técnicos normalmente</li>
                        </ul>
                        <p>Explore todas as funcionalidades e comece a receber serviços!</p>
                        <div style="text-align: center; margin-top: 20px;">
                            <button class="btn-confirmar-notificacao" onclick="fecharMensagem()" style="background: linear-gradient(135deg, #08ebf3, #00bcd4); color: #001a33;">
                                Entendi, vamos começar!
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        <div class="conteudo-principal">
            <div class="bem-vindo">
                Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!
            </div>

            <!-- Status do Técnico (APENAS para técnicos aprovados) -->
            <?php if ($status_tecnico['is_tecnico']): ?>
                <div class="status-tecnico aprovado">
                    Você é um técnico aprovado!
                </div>
            <?php elseif ($status_tecnico['pedido_tecnico_pendente']): ?>
                <div class="status-tecnico">
                    Seu cadastro como técnico está em análise. Aguarde a aprovação.
                </div>
            <?php endif; ?>

            <!-- Seção de Pesquisa COM FORMULÁRIO CORRETO -->
            <div class="pesquisa-section">
                <h2 class="pesquisa-titulo">Encontre o Técnico Perfeito</h2>
                <p class="pesquisa-subtitulo">
                    Pesquise por nome, serviço ou use os filtros para encontrar o técnico ideal
                </p>
                
                <!-- ⭐⭐ FORMULÁRIO CORRETO - TODOS OS CAMPOS DENTRO DO FORM ⭐⭐ -->
                <form id="formPesquisa" action="buscar_tecnicos_avancado.php" method="get">
                    <div class="barra-pesquisa">
                        <input type="text" name="busca" class="campo-pesquisa" placeholder="Digite nome do técnico, serviço, especialidade..." value="<?php echo isset($_GET['busca']) ? htmlspecialchars($_GET['busca']) : ''; ?>">
                        <button type="button" id="btnFiltros" class="botao-filtros">Filtros Avançados</button>
                        <button type="submit" class="botao-buscar">Buscar Técnicos</button>
                    </div>

                    <!-- ⭐⭐ FILTROS AGORA DENTRO DO FORMULÁRIO ⭐⭐ -->
                    <div id="filtrosSection" class="filtros-section">
                        <h3 class="filtros-titulo">Filtros Avançados</h3>
                        
                        <div class="filtros-grid">
                            <!-- Especialidade -->
                            <div class="filtro-group">
                                <label class="filtro-label">Especialidade</label>
                                <select name="especialidade" class="filtro-select">
                                    <option value="">Todas as especialidades</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" <?php echo (isset($_GET['especialidade']) && $_GET['especialidade'] == $categoria['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Cidade -->
                            <div class="filtro-group">
                                <label class="filtro-label">Cidade</label>
                                <input type="text" name="cidade" class="filtro-input" placeholder="Digite sua cidade..." 
                                       value="<?php echo isset($_GET['cidade']) ? htmlspecialchars($_GET['cidade']) : ''; ?>" 
                                       list="cidades-list">
                                <datalist id="cidades-list">
                                    <?php foreach ($cidades as $cidade): ?>
                                        <option value="<?php echo htmlspecialchars($cidade); ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <!-- Estado -->
                            <div class="filtro-group">
                                <label class="filtro-label">Estado</label>
                                <select name="estado" class="filtro-select">
                                    <option value="">Todos os estados</option>
                                    <?php foreach ($estados as $estado_option): ?>
                                        <option value="<?php echo htmlspecialchars($estado_option); ?>" <?php echo (isset($_GET['estado']) && $_GET['estado'] == $estado_option) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($estado_option); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Avaliação -->
                            <div class="filtro-group">
                                <label class="filtro-label">Avaliação Mínima</label>
                                <select name="avaliacao" class="filtro-select">
                                    <option value="">Qualquer avaliação</option>
                                    <option value="4.5" <?php echo (isset($_GET['avaliacao']) && $_GET['avaliacao'] == 4.5) ? 'selected' : ''; ?>>4.5+ estrelas</option>
                                    <option value="4.0" <?php echo (isset($_GET['avaliacao']) && $_GET['avaliacao'] == 4.0) ? 'selected' : ''; ?>>4.0+ estrelas</option>
                                    <option value="3.5" <?php echo (isset($_GET['avaliacao']) && $_GET['avaliacao'] == 3.5) ? 'selected' : ''; ?>>3.5+ estrelas</option>
                                    <option value="3.0" <?php echo (isset($_GET['avaliacao']) && $_GET['avaliacao'] == 3.0) ? 'selected' : ''; ?>>3.0+ estrelas</option>
                                </select>
                            </div>

                            <!-- Experiência -->
                            <div class="filtro-group">
                                <label class="filtro-label">Experiência Mínima</label>
                                <select name="experiencia" class="filtro-select">
                                    <option value="">Qualquer experiência</option>
                                    <option value="1" <?php echo (isset($_GET['experiencia']) && $_GET['experiencia'] == 1) ? 'selected' : ''; ?>>1+ anos</option>
                                    <option value="3" <?php echo (isset($_GET['experiencia']) && $_GET['experiencia'] == 3) ? 'selected' : ''; ?>>3+ anos</option>
                                    <option value="5" <?php echo (isset($_GET['experiencia']) && $_GET['experiencia'] == 5) ? 'selected' : ''; ?>>5+ anos</option>
                                    <option value="10" <?php echo (isset($_GET['experiencia']) && $_GET['experiencia'] == 10) ? 'selected' : ''; ?>>10+ anos</option>
                                </select>
                            </div>

                            <!-- Tipo de Atendimento -->
                            <div class="filtro-group">
                                <label class="filtro-label">Tipo de Atendimento</label>
                                <div class="checkbox-container">
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="local_proprio" id="local_proprio" value="sim" <?php echo (isset($_GET['local_proprio']) && $_GET['local_proprio'] == 'sim') ? 'checked' : ''; ?>>
                                        <label for="local_proprio">Atende em local próprio</label>
                                    </div>
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="atende_cliente" id="atende_cliente" value="sim" <?php echo (!isset($_GET['atende_cliente']) || $_GET['atende_cliente'] == 'sim') ? 'checked' : ''; ?>>
                                        <label for="atende_cliente">Vai até o cliente</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="botoes-filtros">
                            <button type="submit" class="botao-aplicar">Aplicar Filtros</button>
                            <button type="button" class="botao-limpar" onclick="limparFiltros()">Limpar Filtros</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Seção de Ações Rápidas -->
            <div class="acoes-rapidas">
                <div class="acao-card" onclick="location.href='favoritos.php'">
                    <div class="acao-icone">⭐</div>
                    <div class="acao-titulo">Técnicos Favoritos</div>
                    <div class="acao-descricao">Acesse sua lista de técnicos favoritados para contatos rápidos</div>
                </div>
                
                <!-- Card Agenda -->
                <div class="acao-card" onclick="location.href='agenda.php'">
                    <div class="acao-icone">📅</div>
                    <div class="acao-titulo">Minha Agenda</div>
                    <div class="acao-descricao">Visualize e gerencie seus agendamentos</div>
                </div>
                
                <div class="acao-card" onclick="location.href='suporte2.php'">
                    <div class="acao-icone">💬</div>
                    <div class="acao-titulo">Central de Ajuda</div>
                    <div class="acao-descricao">Precisa de suporte? Nossa equipe está aqui para ajudar</div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer Menor -->
    <footer class="rodape-logado">
        <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
        <p>
            <a href="suporte2.php">Suporte</a> |
            <a href="suporte2.php#termos">Termos de Uso</a> |
            <a href="suporte2.php#politica">Política de Privacidade</a>
        </p>
        <p style="margin-top: 8px; font-size: 0.8em; color: #666;">
            Conectando você aos melhores técnicos da região
        </p>
    </footer>

    <script>
        // Fechar notificação de agendamento
        function fecharNotificacao() {
            const notificacao = document.getElementById('notificacaoAgendamento');
            if (notificacao) {
                notificacao.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => {
                    notificacao.remove();
                }, 300);
            }
        }

        // Modal de preferências
        function abrirModal() {
            document.getElementById('modalPreferencias').style.display = 'flex';
        }

        function fecharModal() {
            document.getElementById('modalPreferencias').style.display = 'none';
        }

        // Fechar mensagem de boas-vindas
        function fecharMensagem() {
            const mensagem = document.getElementById('mensagemBoasVindas');
            if (mensagem) {
                mensagem.style.animation = 'fadeOut 0.3s ease-out forwards';
                setTimeout(() => {
                    mensagem.remove();
                }, 300);
            }
        }

        // Adicionar animação de fadeOut
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Fechar mensagem pressionando ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                fecharNotificacao();
                fecharModal();
                fecharMensagem();
            }
        });

        // Mostrar/ocultar filtros
        document.getElementById('btnFiltros').addEventListener('click', function() {
            const filtrosSection = document.getElementById('filtrosSection');
            if (filtrosSection.style.display === 'none' || filtrosSection.style.display === '') {
                filtrosSection.style.display = 'block';
                this.textContent = 'Ocultar Filtros';
                this.style.background = 'linear-gradient(135deg, #555, #444)';
            } else {
                filtrosSection.style.display = 'none';
                this.textContent = 'Filtros Avançados';
                this.style.background = 'linear-gradient(135deg, #ff6b6b, #ee5a52)';
            }
        });

        // Limpar filtros
        function limparFiltros() {
            const form = document.getElementById('formPesquisa');
            const inputs = form.querySelectorAll('input, select');
            inputs.forEach(input => {
                if (input.type === 'text' || input.type === 'select-one') {
                    input.value = '';
                } else if (input.type === 'checkbox') {
                    // Só limpa "local_proprio", mantém "atende_cliente" marcado
                    if (input.id === 'local_proprio') {
                        input.checked = false;
                    } else if (input.id === 'atende_cliente') {
                        input.checked = true;
                    }
                }
            });
            
            // Redireciona para limpar a URL
            window.location.href = 'area_logada.php';
        }

        // Adicionar efeitos de hover nos cards
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.acao-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-6px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            // Mostrar filtros se algum filtro está ativo
            const urlParams = new URLSearchParams(window.location.search);
            const hasFilters = urlParams.has('especialidade') || urlParams.has('cidade') || 
                              urlParams.has('estado') || urlParams.has('avaliacao') || 
                              urlParams.has('experiencia') || urlParams.has('local_proprio') || 
                              urlParams.has('atende_cliente');
            
            if (hasFilters) {
                document.getElementById('filtrosSection').style.display = 'block';
                document.getElementById('btnFiltros').textContent = 'Ocultar Filtros';
                document.getElementById('btnFiltros').style.background = 'linear-gradient(135deg, #555, #444)';
            }
        });
    </script>
</body>
</html>