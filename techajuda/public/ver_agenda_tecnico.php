<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

$tecnico_id = isset($_GET['tecnico_id']) ? intval($_GET['tecnico_id']) : 0;

if ($tecnico_id <= 0) {
    header("Location: area_logada.php");
    exit();
}

// Buscar informações do técnico
$conn = getDBConnection();
$sql_tecnico = "SELECT u.nome, u.foto_perfil, t.descricao 
                FROM tecnicos t 
                INNER JOIN usuarios u ON t.usuario_id = u.id 
                WHERE t.id = ? AND t.status = 'aprovado'";
$stmt_tecnico = $conn->prepare($sql_tecnico);
$stmt_tecnico->bind_param('i', $tecnico_id);
$stmt_tecnico->execute();
$result_tecnico = $stmt_tecnico->get_result();

if ($result_tecnico->num_rows === 0) {
    header("Location: area_logada.php");
    exit();
}

$tecnico = $result_tecnico->fetch_assoc();
$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda do Técnico - TechAjuda</title>
    <link rel="stylesheet" href="../visualscript/css/style.css">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
    <style>
        /* Use os mesmos estilos da agenda.php, mas adicione: */
        .info-privacidade {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border: 2px solid #ff8c42;
        }
    </style>
</head>
<body>
    <!-- Header similar ao agenda.php -->
    <header class="topo-logado">
        <div class="logo">
            <img src="../visualscript/imagem/logotcc.png" alt="TechAjuda">
        </div>
        <div class="nav-right">
            <a href="area_logada.php" class="nav-link">Menu Principal</a>
            <a href="minha_conta.php" class="nav-link">Minha Conta</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </header>

    <main>
        <div class="conteudo-principal">
            <a href="javascript:history.back()" class="voltar-link">← Voltar</a>
            
            <h1 class="titulo-pagina">Agenda de <?php echo htmlspecialchars($tecnico['nome']); ?></h1>

            <div class="info-privacidade">
                ⚠️ Algumas informações estão ocultas para proteger a privacidade dos clientes
            </div>

            <div class="calendar-container">
                <div id="calendar"></div>
            </div>
        </div>
    </main>

    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/pt-br.js'></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
                events: 'agenda_events.php?tecnico_id=<?php echo $tecnico_id; ?>',
                eventClick: function(info) {
                    const event = info.event;
                    const props = event.extendedProps;
                    
                    // Sempre em modo visualização para agenda de outro técnico
                    criarModalEvento(event, true);
                },
                eventDisplay: 'block',
                eventTimeFormat: {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                }
            });

            calendar.render();
        });

        // Use a mesma função criarModalEvento do agenda.php aqui
    </script>
</body>
</html>