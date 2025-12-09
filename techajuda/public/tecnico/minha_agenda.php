<?php
session_start();
require_once "../../src/config.php";

// Verificar se o técnico está logado E se está aprovado
if (!isset($_SESSION['tecnico_id'])) {
    header("Location: tornar_tecnico.php");
    exit();
}

// Verificar se o técnico está aprovado
$conn = getDBConnection();
$tecnico_id = $_SESSION['tecnico_id'];

$sql_status = "SELECT status FROM tecnicos WHERE id = $tecnico_id";
$result_status = $conn->query($sql_status);

if ($result_status->num_rows > 0) {
    $tecnico_status = $result_status->fetch_assoc();
   
    if ($tecnico_status['status'] !== 'aprovado') {
        $conn->close();
        header("Location: tornar_tecnico.php?erro=nao_aprovado");
        exit();
    }
} else {
    $conn->close();
    header("Location: tornar_tecnico.php");
    exit();
}

// DEBUG: Verificar se estamos recebendo dados POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("DEBUG: Recebido POST - Data: " . $_POST['data'] . ", Disponivel: " . $_POST['disponivel']);
}

// Processar alteração de disponibilidade
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data']) && isset($_POST['disponivel'])) {
    $data = $conn->real_escape_string($_POST['data']);
    $disponivel = $_POST['disponivel'] === 'true' ? 1 : 0;
    
    error_log("DEBUG: Processando - Data: $data, Disponivel: $disponivel, Tecnico ID: $tecnico_id");
    
    // Verificar se já existe registro para esta data
    $sql_check = "SELECT id, disponivel FROM agenda_tecnico WHERE tecnico_id = $tecnico_id AND data = '$data'";
    $result_check = $conn->query($sql_check);
    
    if ($result_check->num_rows > 0) {
        // Atualizar registro existente
        $row = $result_check->fetch_assoc();
        $sql_update = "UPDATE agenda_tecnico SET disponivel = $disponivel WHERE tecnico_id = $tecnico_id AND data = '$data'";
        $result_update = $conn->query($sql_update);
        
        if ($result_update) {
            error_log("DEBUG: Registro atualizado com sucesso - ID: " . $row['id']);
        } else {
            error_log("DEBUG: Erro ao atualizar: " . $conn->error);
        }
    } else {
        // Inserir novo registro
        $sql_insert = "INSERT INTO agenda_tecnico (tecnico_id, data, disponivel) VALUES ($tecnico_id, '$data', $disponivel)";
        $result_insert = $conn->query($sql_insert);
        
        if ($result_insert) {
            error_log("DEBUG: Novo registro inserido com sucesso - ID: " . $conn->insert_id);
        } else {
            error_log("DEBUG: Erro ao inserir: " . $conn->error);
        }
    }
    
    // Verificar quantos registros existem para este técnico (para debug)
    $sql_count = "SELECT COUNT(*) as total FROM agenda_tecnico WHERE tecnico_id = $tecnico_id";
    $result_count = $conn->query($sql_count);
    $count = $result_count->fetch_assoc()['total'];
    error_log("DEBUG: Total de registros na agenda: $count");
    
    echo json_encode(['success' => true, 'message' => 'Disponibilidade atualizada com sucesso!']);
    exit();
}

// Buscar disponibilidades do técnico
$sql_agenda = "SELECT data, disponivel FROM agenda_tecnico WHERE tecnico_id = $tecnico_id";
$result_agenda = $conn->query($sql_agenda);
$disponibilidades = [];

if ($result_agenda->num_rows > 0) {
    while($row = $result_agenda->fetch_assoc()) {
        $disponibilidades[$row['data']] = (bool)$row['disponivel'];
    }
    error_log("DEBUG: Carregadas " . count($disponibilidades) . " disponibilidades do banco");
} else {
    error_log("DEBUG: Nenhuma disponibilidade encontrada no banco para técnico ID: $tecnico_id");
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Agenda - TechAjuda</title>
    <link rel="stylesheet" href="../../visualscript/css/style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: url('../../visualscript/imagem/background2.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .topo-logado {
            background-color: #1a1a1a;
            padding: 0 5px;
            height: 90px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .logo img {
            height: 200px;
            width: auto;
            margin-left: 20px;
        }

        .nav-right {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-right: 20px;
        }

        .nav-link {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1em;
            transition: color 0.3s;
            padding: 8px 12px;
        }

        .nav-link:hover {
            color: #ffffff;
        }

        .account-circle {
            width: 30px;
            height: 30px;
            background-color: white;
            border-radius: 50%;
            margin-left: 10px;
        }

        .conteudo-agenda {
            padding: 140px 20px 80px;
            max-width: 1200px;
            margin: 0 auto;
            color: white;
        }

        .titulo-agenda {
            text-align: center;
            color: #ff4444;
            font-size: 2.5em;
            margin-bottom: 30px;
        }

        .controles-agenda {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
        }

        .controles-mes {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .botao-mes {
            background-color: #ff4444;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .botao-mes:hover {
            background-color: #cc0000;
        }

        .mes-atual {
            font-size: 1.5em;
            font-weight: bold;
            color: #08ebf3;
        }

        .legenda {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .item-legenda {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .cor-legenda {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .disponivel {
            background-color: #00cc00;
        }

        .indisponivel {
            background-color: #ff4444;
        }

        .padrao {
            background-color: #666;
        }

        .calendario {
            background: rgba(0, 0, 0, 0.7);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .dias-semana {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
            margin-bottom: 15px;
            text-align: center;
            font-weight: bold;
            color: #08ebf3;
        }

        .dias-mes {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 10px;
        }

        .dia {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #333;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: bold;
            border: 2px solid transparent;
            position: relative;
        }

        .dia:hover {
            transform: scale(1.05);
            border-color: #08ebf3;
        }

        .dia.disponivel {
            background-color: #00cc00;
            color: white;
        }

        .dia.indisponivel {
            background-color: #ff4444;
            color: white;
        }

        .dia.vazio {
            background-color: transparent;
            cursor: default;
        }

        .dia.vazio:hover {
            transform: none;
            border-color: transparent;
        }

        .dia.hoje {
            border: 3px solid #08ebf3;
        }

        .status-dia {
            position: absolute;
            bottom: 2px;
            right: 2px;
            font-size: 0.6em;
            background: rgba(0,0,0,0.5);
            padding: 1px 3px;
            border-radius: 3px;
        }

        .botao-voltar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 25px;
            background-color: #08ebf3;
            color: #000;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .botao-voltar:hover {
            background-color: #007acc;
            color: white;
        }

        .estatisticas {
            background: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .estatisticas h3 {
            color: #08ebf3;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .stat-item {
            background: #333;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-numero {
            font-size: 2em;
            font-weight: bold;
            color: #08ebf3;
        }

        .stat-label {
            color: #ccc;
            font-size: 0.9em;
        }

        .debug-info {
            background: rgba(255, 0, 0, 0.2);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            border-left: 4px solid #ff4444;
        }

        .debug-info h4 {
            color: #ff4444;
            margin-bottom: 10px;
        }

        .mensagem-status {
            position: fixed;
            top: 100px;
            right: 20px;
            background: #00cc00;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .mensagem-status.mostrar {
            opacity: 1;
        }
    </style>
</head>
<body>
    <!-- Header da Área Logada -->
    <header class="topo-logado">
        <div class="logo">
            <img src="../../visualscript/imagem/logotcc.png" alt="TechAjuda">
        </div>
        <div class="nav-right">
            <a href="area_tecnico.php" class="nav-link">Área do Técnico</a>
            <a href="minha_conta_tecnico.php" class="nav-link">Minha Conta</a>
            <a href="../logout.php" class="nav-link">Sair</a>
            <div class="account-circle">
                <?php if (!empty($_SESSION['usuario_foto'])): ?>
                    <img src="../../<?php echo $_SESSION['usuario_foto']; ?>" alt="Foto Perfil"
                         style="width: 30px; height: 30px; border-radius: 50%; object-fit: cover;">
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Mensagem de Status -->
    <div class="mensagem-status" id="mensagemStatus"></div>

    <!-- Conteúdo da Agenda -->
    <div class="conteudo-agenda">
        <h1 class="titulo-agenda">📅 Minha Agenda</h1>
        
        <div class="controles-agenda">
            <div class="controles-mes">
                <button class="botao-mes" onclick="mudarMes(-1)">← Mês Anterior</button>
                <span class="mes-atual" id="mesAtual"></span>
                <button class="botao-mes" onclick="mudarMes(1)">Próximo Mês →</button>
            </div>
            
            <div class="legenda">
                <div class="item-legenda">
                    <div class="cor-legenda disponivel"></div>
                    <span>Disponível</span>
                </div>
                <div class="item-legenda">
                    <div class="cor-legenda indisponivel"></div>
                    <span>Indisponível</span>
                </div>
                <div class="item-legenda">
                    <div class="cor-legenda padrao"></div>
                    <span>Padrão</span>
                </div>
            </div>
        </div>

        <div class="calendario">
            <div class="dias-semana">
                <div>Dom</div>
                <div>Seg</div>
                <div>Ter</div>
                <div>Qua</div>
                <div>Qui</div>
                <div>Sex</div>
                <div>Sáb</div>
            </div>
            <div class="dias-mes" id="diasMes"></div>
        </div>

        <div class="estatisticas">
            <h3>📊 Estatísticas do Mês</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-numero" id="diasDisponiveis">0</div>
                    <div class="stat-label">Dias Disponíveis</div>
                </div>
                <div class="stat-item">
                    <div class="stat-numero" id="diasIndisponiveis">0</div>
                    <div class="stat-label">Dias Indisponíveis</div>
                </div>
                <div class="stat-item">
                    <div class="stat-numero" id="totalDias">0</div>
                    <div class="stat-label">Total de Dias</div>
                </div>
            </div>
        </div>

        <!-- Debug Info -->
        <div class="debug-info">
            <h4>🔍 Informações de Debug</h4>
            <p><strong>Técnico ID:</strong> <?php echo $tecnico_id; ?></p>
            <p><strong>Disponibilidades carregadas:</strong> <?php echo count($disponibilidades); ?> dias</p>
            <p><strong>Dias no banco:</strong> <span id="debugDiasBanco"><?php echo count($disponibilidades); ?></span></p>
            <p><strong>Última ação:</strong> <span id="debugUltimaAcao">Nenhuma</span></p>
        </div>
    </div>

    <!-- Botão Voltar -->
    <a href="area_tecnico.php" class="botao-voltar">← Voltar</a>

    <script>
        let mesAtual = new Date().getMonth();
        let anoAtual = new Date().getFullYear();
        const disponibilidades = <?php echo json_encode($disponibilidades); ?>;

        function mostrarMensagem(texto, tipo = 'success') {
            const mensagem = document.getElementById('mensagemStatus');
            mensagem.textContent = texto;
            mensagem.style.backgroundColor = tipo === 'success' ? '#00cc00' : '#ff4444';
            mensagem.classList.add('mostrar');
            
            setTimeout(() => {
                mensagem.classList.remove('mostrar');
            }, 3000);
        }

        function atualizarCalendario() {
            const mesAtualElem = document.getElementById('mesAtual');
            const diasMesElem = document.getElementById('diasMes');
            
            const primeiroDia = new Date(anoAtual, mesAtual, 1);
            const ultimoDia = new Date(anoAtual, mesAtual + 1, 0);
            const diasNoMes = ultimoDia.getDate();
            const primeiroDiaSemana = primeiroDia.getDay();
            
            // Atualizar título do mês
            const opcoes = { month: 'long', year: 'numeric' };
            mesAtualElem.textContent = primeiroDia.toLocaleDateString('pt-BR', opcoes);
            
            // Limpar calendário
            diasMesElem.innerHTML = '';
            
            // Adicionar dias vazios no início
            for (let i = 0; i < primeiroDiaSemana; i++) {
                const diaVazio = document.createElement('div');
                diaVazio.className = 'dia vazio';
                diasMesElem.appendChild(diaVazio);
            }
            
            // Adicionar dias do mês
            const hoje = new Date();
            let diasDisponiveis = 0;
            let diasIndisponiveis = 0;
            
            for (let dia = 1; dia <= diasNoMes; dia++) {
                const data = new Date(anoAtual, mesAtual, dia);
                const dataString = data.toISOString().split('T')[0];
                
                const diaElem = document.createElement('div');
                diaElem.className = 'dia';
                diaElem.textContent = dia;
                diaElem.dataset.data = dataString;
                
                // Verificar se é hoje
                if (data.toDateString() === hoje.toDateString()) {
                    diaElem.classList.add('hoje');
                }
                
                // Verificar disponibilidade
                let statusTexto = 'Padrão';
                if (disponibilidades[dataString] !== undefined) {
                    if (disponibilidades[dataString]) {
                        diaElem.classList.add('disponivel');
                        statusTexto = 'Disponível';
                        diasDisponiveis++;
                    } else {
                        diaElem.classList.add('indisponivel');
                        statusTexto = 'Indisponível';
                        diasIndisponiveis++;
                    }
                }
                
                // Adicionar indicador de status
                const statusElem = document.createElement('div');
                statusElem.className = 'status-dia';
                statusElem.textContent = statusTexto;
                diaElem.appendChild(statusElem);
                
                // Adicionar evento de clique
                diaElem.addEventListener('click', function() {
                    alternarDisponibilidade(this);
                });
                
                diasMesElem.appendChild(diaElem);
            }
            
            // Atualizar estatísticas
            document.getElementById('diasDisponiveis').textContent = diasDisponiveis;
            document.getElementById('diasIndisponiveis').textContent = diasIndisponiveis;
            document.getElementById('totalDias').textContent = diasNoMes;
        }
        
        function alternarDisponibilidade(diaElem) {
            const data = diaElem.dataset.data;
            const estaDisponivel = diaElem.classList.contains('disponivel');
            const novaDisponibilidade = !estaDisponivel;
            
            // Atualizar debug
            document.getElementById('debugUltimaAcao').textContent = 
                `Data: ${data}, Novo status: ${novaDisponibilidade ? 'Disponível' : 'Indisponível'}`;
            
            // Enviar requisição para o servidor
            fetch('minha_agenda.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `data=${data}&disponivel=${novaDisponibilidade}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Atualizar interface
                    diaElem.classList.remove('disponivel', 'indisponivel');
                    if (novaDisponibilidade) {
                        diaElem.classList.add('disponivel');
                        diaElem.querySelector('.status-dia').textContent = 'Disponível';
                    } else {
                        diaElem.classList.add('indisponivel');
                        diaElem.querySelector('.status-dia').textContent = 'Indisponível';
                    }
                    
                    // Atualizar objeto de disponibilidades
                    disponibilidades[data] = novaDisponibilidade;
                    
                    // Mostrar mensagem de sucesso
                    mostrarMensagem(data.message || 'Disponibilidade atualizada com sucesso!');
                    
                    // Recalcular estatísticas
                    setTimeout(atualizarCalendario, 100);
                    
                    // Atualizar contador de dias no banco
                    document.getElementById('debugDiasBanco').textContent = 
                        Object.keys(disponibilidades).length;
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarMensagem('Erro ao atualizar disponibilidade. Tente novamente.', 'error');
            });
        }
        
        function mudarMes(direcao) {
            mesAtual += direcao;
            if (mesAtual < 0) {
                mesAtual = 11;
                anoAtual--;
            } else if (mesAtual > 11) {
                mesAtual = 0;
                anoAtual++;
            }
            atualizarCalendario();
        }
        
        // Inicializar calendário
        document.addEventListener('DOMContentLoaded', atualizarCalendario);
    </script>
</body>
</html>