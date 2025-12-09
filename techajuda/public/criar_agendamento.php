<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id']) || !$_SESSION['is_tecnico']) {
    header("Location: entrar.php");
    exit();
}

$conn = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];
$erro = '';
$sucesso = '';

// Verificar se o técnico está aprovado
$sql_tecnico = "SELECT t.id, t.usuario_id, u.nome 
                FROM tecnicos t 
                INNER JOIN usuarios u ON t.usuario_id = u.id 
                WHERE t.usuario_id = ? AND t.status = 'aprovado'";
$stmt_tecnico = $conn->prepare($sql_tecnico);
$stmt_tecnico->bind_param('i', $usuario_id);
$stmt_tecnico->execute();
$result_tecnico = $stmt_tecnico->get_result();

if ($result_tecnico->num_rows === 0) {
    header("Location: area_logada.php");
    exit();
}

$tecnico = $result_tecnico->fetch_assoc();
$tecnico_id = $tecnico['id'];

// Buscar todos os usuários ativos (incluindo técnicos, mas excluindo o próprio técnico)
$sql_clientes = "SELECT id, nome, email, is_tecnico 
                 FROM usuarios 
                 WHERE id != ? AND ativo = 1 
                 ORDER BY is_tecnico DESC, nome";
$stmt_clientes = $conn->prepare($sql_clientes);
$stmt_clientes->bind_param('i', $usuario_id);
$stmt_clientes->execute();
$result_clientes = $stmt_clientes->get_result();
$clientes = $result_clientes->fetch_all(MYSQLI_ASSOC);
$stmt_clientes->close();

// Processar formulário de criação de agendamento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cliente_id = intval($_POST['cliente_id']);
    $titulo = trim($_POST['titulo']);
    $descricao = trim($_POST['descricao']);
    $data_agendamento = $_POST['data_agendamento'];
    $hora_inicio = $_POST['hora_inicio'];
    $local_atendimento = $_POST['local_atendimento'];
    $valor = !empty($_POST['valor']) ? floatval($_POST['valor']) : NULL;
    $endereco_cliente = ($local_atendimento === 'local_cliente' && !empty($_POST['endereco_cliente'])) 
                        ? trim($_POST['endereco_cliente']) 
                        : NULL;

    // Validar dados
    $campos_obrigatorios = [
        'cliente_id' => 'Cliente',
        'titulo' => 'Título do serviço',
        'data_agendamento' => 'Data do agendamento',
        'hora_inicio' => 'Horário de início'
    ];

    $erros_validacao = [];
    foreach ($campos_obrigatorios as $campo => $nome) {
        if (empty($_POST[$campo])) {
            $erros_validacao[] = "O campo '$nome' é obrigatório";
        }
    }

    if (!empty($erros_validacao)) {
        $erro = implode('<br>', $erros_validacao);
    } else {
        try {
            // Inserir agendamento com status explícito
            $sql_inserir = "INSERT INTO agendamentos 
                            (tecnico_id, cliente_id, titulo, descricao, data_agendamento, hora_inicio, 
                            local_atendimento, valor, endereco_cliente, status, created_at, updated_at) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado', NOW(), NOW())";
            
            $stmt_inserir = $conn->prepare($sql_inserir);
            
            // Verificar se a preparação foi bem sucedida
            if (!$stmt_inserir) {
                throw new Exception("Erro ao preparar consulta: " . $conn->error);
            }
            
            $stmt_inserir->bind_param('iisssssds', 
                $tecnico_id, $cliente_id, $titulo, $descricao, $data_agendamento, 
                $hora_inicio, $local_atendimento, $valor, $endereco_cliente);
            
            if ($stmt_inserir->execute()) {
                $agendamento_id = $stmt_inserir->insert_id;
                $sucesso = "Agendamento criado com sucesso!";
                
                // Debug: Verificar inserção
                error_log("AGENDAMENTO CRIADO - ID: $agendamento_id, Técnico: $tecnico_id, Cliente: $cliente_id, Status: pendente_confirmacao");
                
                // Limpar formulário
                $_POST = [];
            } else {
                throw new Exception("Erro ao executar inserção: " . $stmt_inserir->error);
            }
            
            $stmt_inserir->close();
            
        } catch (Exception $e) {
            $erro = "Erro ao criar agendamento: " . $e->getMessage();
            error_log("ERRO AGENDAMENTO: " . $e->getMessage());
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Agendamento - TechAjuda</title>
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
            max-width: 800px;
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

        .form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 40px;
            border-radius: 15px;
            border: 2px solid #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #08ebf3;
            font-weight: 600;
        }

        .required::after {
            content: " *";
            color: #ff4444;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #333;
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #08ebf3;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 235, 243, 0.3);
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

        .mensagem.erro {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .info-box {
            background: rgba(8, 235, 243, 0.1);
            border: 1px solid #08ebf3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-box p {
            margin: 0;
            color: #08ebf3;
            font-size: 0.9em;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .conteudo-principal {
                padding: 20px 15px;
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
            <a href="agenda.php" class="voltar-link">Voltar para Agenda</a>
            
            <h1 class="titulo-pagina">Criar Agendamento</h1>

            <?php if ($sucesso): ?>
                <div class="mensagem sucesso"><?php echo $sucesso; ?></div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="mensagem erro"><?php echo $erro; ?></div>
            <?php endif; ?>

            <div class="info-box">
                <p><strong>Informação:</strong> O cliente receberá uma notificação para confirmar o agendamento antes que ele apareça na agenda.</p>
            </div>

            <div class="form-container">
                <form method="POST" id="formAgendamento">
                    <div class="form-group">
                        <label for="cliente_id" class="required">Cliente</label>
                        <select name="cliente_id" id="cliente_id" class="form-control" required>
                            <option value="">Selecione um cliente</option>
                            <?php foreach ($clientes as $cliente): ?>
                                <option value="<?php echo $cliente['id']; ?>" 
                                    <?php echo isset($_POST['cliente_id']) && $_POST['cliente_id'] == $cliente['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cliente['nome']); ?> 
                                    (<?php echo $cliente['email']; ?>)
                                    <?php echo $cliente['is_tecnico'] ? ' - [TÉCNICO]' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="titulo" class="required">Título do Serviço</label>
                        <input type="text" name="titulo" id="titulo" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" required 
                               placeholder="Ex: Manutenção de Computador">
                    </div>

                    <div class="form-group">
                        <label for="descricao">Descrição do Serviço</label>
                        <textarea name="descricao" id="descricao" class="form-control" rows="4" 
                                  placeholder="Descreva detalhadamente o serviço a ser realizado"><?php echo htmlspecialchars($_POST['descricao'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="data_agendamento" class="required">Data do Agendamento</label>
                            <input type="date" name="data_agendamento" id="data_agendamento" 
                                   class="form-control" value="<?php echo $_POST['data_agendamento'] ?? ''; ?>" required 
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>

                        <div class="form-group">
                            <label for="hora_inicio" class="required">Horário de Início</label>
                            <input type="time" name="hora_inicio" id="hora_inicio" 
                                   class="form-control" value="<?php echo $_POST['hora_inicio'] ?? '09:00'; ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="local_atendimento" class="required">Local de Atendimento</label>
                        <select name="local_atendimento" id="local_atendimento" class="form-control" required>
                            <option value="local_tecnico" <?php echo ($_POST['local_atendimento'] ?? '') === 'local_tecnico' ? 'selected' : ''; ?>>
                                Meu Local (Técnico)
                            </option>
                            <option value="local_cliente" <?php echo ($_POST['local_atendimento'] ?? '') === 'local_cliente' ? 'selected' : ''; ?>>
                                Local do Cliente
                            </option>
                        </select>
                    </div>

                    <div class="form-group" id="endereco_cliente_group" style="display: none;">
                        <label for="endereco_cliente" class="required">Endereço do Cliente</label>
                        <input type="text" name="endereco_cliente" id="endereco_cliente" 
                               class="form-control" value="<?php echo htmlspecialchars($_POST['endereco_cliente'] ?? ''); ?>" 
                               placeholder="Digite o endereço completo do cliente">
                    </div>

                    <div class="form-group">
                        <label for="valor">Valor do Serviço (R$)</label>
                        <input type="number" name="valor" id="valor" class="form-control" 
                               value="<?php echo $_POST['valor'] ?? ''; ?>" step="0.01" min="0" 
                               placeholder="0.00">
                    </div>

                    <button type="submit" class="btn-primary">Criar Agendamento</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        document.getElementById('local_atendimento').addEventListener('change', function() {
            const enderecoGroup = document.getElementById('endereco_cliente_group');
            const enderecoInput = document.getElementById('endereco_cliente');
            
            if (this.value === 'local_cliente') {
                enderecoGroup.style.display = 'block';
                enderecoInput.required = true;
            } else {
                enderecoGroup.style.display = 'none';
                enderecoInput.required = false;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar visibilidade do campo endereço
            document.getElementById('local_atendimento').dispatchEvent(new Event('change'));
            
            // Data mínima é hoje
            document.getElementById('data_agendamento').min = new Date().toISOString().split('T')[0];
            
            // Validação do formulário
            document.getElementById('formAgendamento').addEventListener('submit', function(e) {
                const clienteId = document.getElementById('cliente_id').value;
                const titulo = document.getElementById('titulo').value.trim();
                const data = document.getElementById('data_agendamento').value;
                const hora = document.getElementById('hora_inicio').value;
                
                if (!clienteId || !titulo || !data || !hora) {
                    e.preventDefault();
                    alert('Por favor, preencha todos os campos obrigatórios.');
                    return false;
                }
            });
        });
    </script>
</body>
</html>