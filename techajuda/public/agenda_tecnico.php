<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

if (!isset($_GET['tecnico_id']) || empty($_GET['tecnico_id'])) {
    header("Location: area_logada.php");
    exit();
}

$tecnico_id_visualizado = intval($_GET['tecnico_id']);
$usuario_id = $_SESSION['usuario_id'];
$is_tecnico = $_SESSION['is_tecnico'] ?? false;

$conn = getDBConnection();

// Buscar dados do técnico para exibir no cabeçalho
$sql_tecnico = "SELECT u.nome, u.foto_perfil FROM tecnicos t 
                INNER JOIN usuarios u ON t.usuario_id = u.id 
                WHERE t.id = ? AND t.status = 'aprovado'";
$stmt_tecnico = $conn->prepare($sql_tecnico);
$stmt_tecnico->bind_param('i', $tecnico_id_visualizado);
$stmt_tecnico->execute();
$result_tecnico = $stmt_tecnico->get_result();

if ($result_tecnico->num_rows === 0) {
    header("Location: area_logada.php");
    exit();
}

$tecnico_info = $result_tecnico->fetch_assoc();
$stmt_tecnico->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agenda do Técnico - TechAjuda</title>
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

        .info-tecnico {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            border: 2px solid #333;
        }

        .foto-tecnico {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #08ebf3;
        }

        .foto-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #666, #888);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #08ebf3;
            color: white;
            font-size: 1.5em;
        }

        .nome-tecnico {
            color: #08ebf3;
            font-size: 1.5em;
            font-weight: bold;
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

        /* Cores diferentes para cada status */
        .fc-event-pendente_confirmacao {
            background: linear-gradient(135deg, #ffa726, #ff9800);
            border: none;
        }

        .fc-event-agendado {
            background: linear-gradient(135deg, #ffa726, #ff9800);
            border: none;
        }

        .fc-event-confirmado {
            background: linear-gradient(135deg, #66bb6a, #4caf50);
            border: none;
        }

        .fc-event-concluido {
            background: linear-gradient(135deg, #42a5f5, #2196f3);
            border: none;
        }

        .fc-event {
            border-radius: 6px;
            padding: 4px 8px;
            font-size: 0.85em;
            cursor: pointer;
            border: none;
        }

        .fc-event:hover {
            opacity: 0.9;
            transform: translateY(-1px);
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
            
            .info-tecnico {
                flex-direction: column;
                text-align: center;
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
            <a href="perfil_tecnico.php?tecnico_id=<?php echo $tecnico_id_visualizado; ?>" class="voltar-link">
                ← Voltar ao Perfil do Técnico
            </a>
            
            <h1 class="titulo-pagina">Agenda do Técnico</h1>

            <!-- Informações do Técnico -->
            <div class="info-tecnico">
                <?php if (!empty($tecnico_info['foto_perfil'])): ?>
                    <img src="../<?php echo htmlspecialchars($tecnico_info['foto_perfil']); ?>" 
                         alt="Foto de <?php echo htmlspecialchars($tecnico_info['nome']); ?>" 
                         class="foto-tecnico">
                <?php else: ?>
                    <div class="foto-placeholder">
                        👨‍🔧
                    </div>
                <?php endif; ?>
                
                <div>
                    <div class="nome-tecnico"><?php echo htmlspecialchars($tecnico_info['nome']); ?></div>
                    <div style="color: #a0a0a0; margin-top: 5px;">Visualizando agenda pública</div>
                </div>
            </div>

            <!-- Calendário -->
            <div class="calendar-container">
                <div id="calendar"></div>
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
                events: 'agenda_events.php?tecnico_id=<?php echo $tecnico_id_visualizado; ?>&visualizando_outro_tecnico=true',
                eventClick: function(info) {
                    const event = info.event;
                    const props = event.extendedProps;
                    
                    // SEMPRE visualizando outro técnico nesta página
                    const visualizandoOutroTecnico = true;
                    
                    // Criar modal personalizado
                    criarModalEvento(event, visualizandoOutroTecnico);
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
            
            // Informações sensíveis (SEMPRE borradas nesta página)
            const infoSensivel = document.createElement('div');
            infoSensivel.innerHTML = `
                <div style="margin-bottom: 15px;">
                    <p><strong>Local:</strong> <span style="filter: blur(3px); user-select: none;">${props.local === 'local_tecnico' ? 'Local do Técnico' : 'Local do Cliente'}</span></p>
                    <p><strong>Cliente:</strong> <span style="filter: blur(3px); user-select: none;">Informação confidencial</span></p>
                    <p><strong>Valor:</strong> <span style="filter: blur(3px); user-select: none;">Informação confidencial</span></p>
                </div>
                <div style="background: rgba(255, 193, 7, 0.1); padding: 10px; border-radius: 8px; border: 1px solid #ffc107; text-align: center;">
                    <small>Algumas informações estão ocultas para proteger a privacidade dos clientes</small>
                </div>
            `;
            
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
        const style = document.createElement('style');
        style.textContent = `
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
        document.head.appendChild(style);
    </script>
</body>
</html>