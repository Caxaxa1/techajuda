<?php
session_start();


// Verificação de acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: area_admin.php");
    exit();
}


require_once "../../src/config.php";


// Processar ações (editar/excluir/alterar status)
if (isset($_POST['acao'])) {
    $conn = getDBConnection();
   
    if ($_POST['acao'] === 'excluir' && isset($_POST['tecnico_id'])) {
        $tecnico_id = intval($_POST['tecnico_id']);
       
        // Primeiro buscar o usuario_id para atualizar o usuário
        $sql_busca = "SELECT usuario_id FROM tecnicos WHERE id = $tecnico_id";
        $result = $conn->query($sql_busca);
        if ($result->num_rows > 0) {
            $tecnico = $result->fetch_assoc();
            $usuario_id = $tecnico['usuario_id'];
           
            // Excluir técnico
            $sql = "DELETE FROM tecnicos WHERE id = $tecnico_id";
            $conn->query($sql);
           
            // Atualizar usuário para não técnico
            $sql_update = "UPDATE usuarios SET is_tecnico = FALSE WHERE id = $usuario_id";
            $conn->query($sql_update);
        }
    }
   
    if ($_POST['acao'] === 'editar' && isset($_POST['tecnico_id'])) {
        $tecnico_id = intval($_POST['tecnico_id']);
        $usuario_id = intval($_POST['usuario_id']);
       
        // Dados do usuário
        $nome = $conn->real_escape_string($_POST['nome']);
        $email = $conn->real_escape_string($_POST['email']);
        $idade = intval($_POST['idade']);
        $cpf = $conn->real_escape_string($_POST['cpf']);
        $sexo = $conn->real_escape_string($_POST['sexo']);
        $apelido = $conn->real_escape_string($_POST['apelido']);
        $celular = $conn->real_escape_string($_POST['celular']);
        $endereco = $conn->real_escape_string($_POST['endereco']);
        $cep = $conn->real_escape_string($_POST['cep']);
        $numero = $conn->real_escape_string($_POST['numero']);
       
        // Dados do técnico
        $cpf_tecnico = $conn->real_escape_string($_POST['cpf_tecnico']);
        $anos_experiencia = intval($_POST['anos_experiencia']);
        $possui_local_proprio = $conn->real_escape_string($_POST['possui_local_proprio']);
        $logradouro = $conn->real_escape_string($_POST['logradouro']);
        $numero_local = $conn->real_escape_string($_POST['numero_local']);
        $cep_local = $conn->real_escape_string($_POST['cep_local']);
        $informacao_localizacao = $conn->real_escape_string($_POST['informacao_localizacao']);
        $descricao = $conn->real_escape_string($_POST['descricao']);
        $status = $conn->real_escape_string($_POST['status']);
       
        // Iniciar transação
        $conn->begin_transaction();
       
        try {
            // Atualizar usuário
            $sql_usuario = "UPDATE usuarios SET
                    nome = '$nome',
                    email = '$email',
                    idade = $idade,
                    cpf = '$cpf',
                    sexo = '$sexo',
                    apelido = '$apelido',
                    celular = '$celular',
                    endereco = '$endereco',
                    cep = '$cep',
                    numero = '$numero'
                    WHERE id = $usuario_id";
            $conn->query($sql_usuario);
           
            // Atualizar técnico
            $sql_tecnico = "UPDATE tecnicos SET
                    cpf = '$cpf_tecnico',
                    anos_experiencia = $anos_experiencia,
                    possui_local_proprio = '$possui_local_proprio',
                    logradouro = '$logradouro',
                    numero = '$numero_local',
                    cep = '$cep_local',
                    informacao_localizacao = '$informacao_localizacao',
                    descricao = '$descricao',
                    status = '$status'
                    WHERE id = $tecnico_id";
            $conn->query($sql_tecnico);
           
            $conn->commit();
            $mensagem_sucesso = "Técnico atualizado com sucesso!";
        } catch (Exception $e) {
            $conn->rollback();
            $mensagem_erro = "Erro ao atualizar técnico: " . $e->getMessage();
        }
    }
   
    if ($_POST['acao'] === 'alterar_status' && isset($_POST['tecnico_id'])) {
        $tecnico_id = intval($_POST['tecnico_id']);
        $status = $conn->real_escape_string($_POST['status']);
       
        $sql = "UPDATE tecnicos SET status = '$status' WHERE id = $tecnico_id";
        $conn->query($sql);
    }
   
    $conn->close();
    header("Location: gerenciar_tecnicos.php");
    exit();
}


// Buscar todos os técnicos com dados completos
$conn = getDBConnection();
$sql = "SELECT t.*, u.nome, u.email, u.idade, u.cpf as cpf_usuario, u.sexo, u.apelido,
               u.celular, u.endereco, u.cep, u.numero, u.data_cadastro,
               u.foto_perfil, u.ativo
        FROM tecnicos t
        INNER JOIN usuarios u ON t.usuario_id = u.id
        ORDER BY t.data_cadastro DESC";
$result = $conn->query($sql);
$tecnicos = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $tecnicos[] = $row;
    }
}


// Buscar especialidades de cada técnico
foreach ($tecnicos as &$tecnico) {
    $tecnico_id = $tecnico['id'];
    $sql_especialidades = "SELECT e.nome, e.icone
                           FROM tecnico_especialidades te
                           INNER JOIN especialidades e ON te.especialidade_id = e.id
                           WHERE te.tecnico_id = $tecnico_id";
    $result_esp = $conn->query($sql_especialidades);
    $especialidades = [];
    if ($result_esp->num_rows > 0) {
        while($row = $result_esp->fetch_assoc()) {
            $especialidades[] = $row;
        }
    }
    $tecnico['especialidades'] = $especialidades;
}


$conn->close();
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Técnicos - TechAjuda</title>
    <link rel="stylesheet" href="../../visualscript/css/style.css">
    <style>
        body {
            background-color: #2a2a2a;
            color: #e0e0e0;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
       
        .admin-header {
            background-color: #1a1a1a;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ff4444;
        }
       
        .admin-header h1 {
            color: #ff4444;
            margin: 0;
            font-size: 2.5em;
        }
       
        .admin-nav {
            background-color: #2a2a2a;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #444;
        }
       
        .admin-nav a {
            color: #08ebf3;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
       
        .admin-nav a:hover {
            background-color: #08ebf3;
            color: #2a2a2a;
        }
       
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
       
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
       
        .stat-card {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #08ebf3;
        }
       
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #08ebf3;
            margin: 10px 0;
        }
       
        .tecnicos-table {
            background-color: #1a1a1a;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
            overflow-x: auto;
        }
       
        .table-header {
            background-color: #ff4444;
            padding: 15px;
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1fr 1fr 2fr;
            font-weight: bold;
            min-width: 1400px;
        }
       
        .tecnico-row {
            padding: 15px;
            display: grid;
            grid-template-columns: 2fr 2fr 1fr 1fr 1fr 1fr 1fr 1fr 2fr;
            border-bottom: 1px solid #444;
            align-items: center;
            min-width: 1400px;
        }
       
        .tecnico-row:hover {
            background-color: #2a2a2a;
        }
       
        .tecnico-row:last-child {
            border-bottom: none;
        }
       
        .acoes {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
       
        .btn-editar, .btn-excluir, .btn-status {
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.9em;
        }
       
        .btn-editar {
            background-color: #08ebf3;
            color: #000;
        }
       
        .btn-excluir {
            background-color: #ff4444;
            color: white;
        }
       
        .btn-status {
            background-color: #ffaa00;
            color: #000;
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
            overflow-y: auto;
            padding: 20px;
        }
       
        .modal-content {
            background-color: #2a2a2a;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 800px;
            max-height: 90vh;
            overflow-y: auto;
        }
       
        .modal h3 {
            color: #08ebf3;
            margin-bottom: 20px;
            text-align: center;
        }
       
        .form-group {
            margin-bottom: 15px;
        }
       
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #08ebf3;
            font-weight: bold;
        }
       
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #1a1a1a;
            color: white;
            box-sizing: border-box;
        }
       
        .form-group textarea {
            height: 80px;
            resize: vertical;
        }
       
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
       
        .form-section {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #444;
        }
       
        .form-section-title {
            color: #ff4444;
            font-size: 1.2em;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ff4444;
        }
       
        .modal-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
       
        .btn-cancelar, .btn-salvar {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
       
        .btn-cancelar {
            background-color: #666;
            color: white;
        }
       
        .btn-salvar {
            background-color: #08ebf3;
            color: #000;
        }
       
        .status-aprovado {
            color: #00cc00;
            font-weight: bold;
        }
       
        .status-pendente {
            color: #ffaa00;
            font-weight: bold;
        }
       
        .status-rejeitado {
            color: #ff4444;
            font-weight: bold;
        }
       
        .especialidades-list {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
            margin-top: 5px;
        }
       
        .especialidade-item {
            background-color: #2a2a2a;
            padding: 3px 8px;
            border-radius: 12px;
            border: 1px solid #08ebf3;
            font-size: 0.8em;
            display: flex;
            align-items: center;
            gap: 3px;
        }
       
        .local-proprio-fields {
            margin-top: 10px;
            padding: 10px;
            background-color: rgba(255,255,255,0.05);
            border-radius: 5px;
            border-left: 3px solid #ff4444;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🔧 Gerenciar Técnicos - TechAjuda</h1>
        <p>Gestão Completa de Técnicos Cadastrados</p>
    </div>
   
    <div class="admin-nav">
        <a href="dashboard_admin.php">← Voltar ao Dashboard</a>
        <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
        <a href="gerenciar_tecnicos.php">Atualizar Lista</a>
        <a href="?sair=1">Sair do Admin</a>
    </div>
   
    <div class="container">
        <!-- Mensagens -->
        <?php if (isset($mensagem_sucesso)): ?>
            <div style="background-color: #eeffee; color: #00cc00; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?php echo $mensagem_sucesso; ?>
            </div>
        <?php endif; ?>
       
        <?php if (isset($mensagem_erro)): ?>
            <div style="background-color: #ffeeee; color: #cc0000; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?php echo $mensagem_erro; ?>
            </div>
        <?php endif; ?>
       
        <div class="stats">
            <div class="stat-card">
                <div>Total de Técnicos</div>
                <div class="stat-number"><?php echo count($tecnicos); ?></div>
            </div>
            <div class="stat-card">
                <div>Técnicos Aprovados</div>
                <div class="stat-number">
                    <?php echo count(array_filter($tecnicos, function($t) { return $t['status'] == 'aprovado'; })); ?>
                </div>
            </div>
            <div class="stat-card">
                <div>Técnicos Pendentes</div>
                <div class="stat-number">
                    <?php echo count(array_filter($tecnicos, function($t) { return $t['status'] == 'pendente'; })); ?>
                </div>
            </div>
        </div>
       
        <div class="tecnicos-table">
            <div class="table-header">
                <div>Nome</div>
                <div>E-mail</div>
                <div>CPF (Técnico)</div>
                <div>Experiência</div>
                <div>Especialidades</div>
                <div>Local Próprio</div>
                <div>Status</div>
                <div>Data Cadastro</div>
                <div>Ações</div>
            </div>
           
            <?php foreach ($tecnicos as $tecnico): ?>
            <div class="tecnico-row">
                <div>
                    <?php echo htmlspecialchars($tecnico['nome']); ?>
                    <?php if (!empty($tecnico['apelido'])): ?>
                        <br><small>(<?php echo htmlspecialchars($tecnico['apelido']); ?>)</small>
                    <?php endif; ?>
                </div>
                <div><?php echo htmlspecialchars($tecnico['email']); ?></div>
                <div><?php echo htmlspecialchars($tecnico['cpf']); ?></div>
                <div><?php echo $tecnico['anos_experiencia']; ?> anos</div>
                <div>
                    <?php if (!empty($tecnico['especialidades'])): ?>
                        <div class="especialidades-list">
                            <?php foreach ($tecnico['especialidades'] as $esp): ?>
                                <div class="especialidade-item" title="<?php echo htmlspecialchars($esp['nome']); ?>">
                                    <?php echo $esp['icone']; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <small>Nenhuma</small>
                    <?php endif; ?>
                </div>
                <div><?php echo $tecnico['possui_local_proprio'] == 'sim' ? 'Sim' : 'Não'; ?></div>
                <div class="status-<?php echo $tecnico['status']; ?>">
                    <?php
                    $status_text = [
                        'aprovado' => 'Aprovado',
                        'pendente' => 'Pendente',
                        'rejeitado' => 'Rejeitado'
                    ];
                    echo $status_text[$tecnico['status']] ?? $tecnico['status'];
                    ?>
                </div>
                <div><?php echo date('d/m/Y H:i', strtotime($tecnico['data_cadastro'])); ?></div>
                <div class="acoes">
                    <button class="btn-editar" onclick="editarTecnico(<?php echo $tecnico['id']; ?>)">
                        Editar
                    </button>
                    <button class="btn-status" onclick="alterarStatus(<?php echo $tecnico['id']; ?>, '<?php echo $tecnico['status']; ?>')">
                        Status
                    </button>
                    <button class="btn-excluir" onclick="excluirTecnico(<?php echo $tecnico['id']; ?>, '<?php echo addslashes($tecnico['nome']); ?>')">
                        Excluir
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
   
    <!-- Modal de Edição -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <h3>Editar Técnico</h3>
            <form id="formEditar" method="POST">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="tecnico_id" id="editarTecnicoId">
                <input type="hidden" name="usuario_id" id="editarUsuarioId">
               
                <div class="form-section">
                    <div class="form-section-title">Dados do Usuário</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editarNome">Nome:</label>
                            <input type="text" id="editarNome" name="nome" required>
                        </div>
                        <div class="form-group">
                            <label for="editarEmail">E-mail:</label>
                            <input type="email" id="editarEmail" name="email" required>
                        </div>
                    </div>
                   
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editarIdade">Idade:</label>
                            <input type="number" id="editarIdade" name="idade" min="1" max="120" required>
                        </div>
                        <div class="form-group">
                            <label for="editarCpf">CPF (Usuário):</label>
                            <input type="text" id="editarCpf" name="cpf" required readonly>
                        </div>
                    </div>
                   
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editarSexo">Sexo:</label>
                            <select id="editarSexo" name="sexo" required>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                                <option value="Outro">Outro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="editarApelido">Apelido:</label>
                            <input type="text" id="editarApelido" name="apelido">
                        </div>
                    </div>
                   
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editarCelular">Celular:</label>
                            <input type="text" id="editarCelular" name="celular" required>
                        </div>
                        <div class="form-group">
                            <label for="editarStatus">Status do Técnico:</label>
                            <select id="editarStatus" name="status" required>
                                <option value="pendente">Pendente</option>
                                <option value="aprovado">Aprovado</option>
                                <option value="rejeitado">Rejeitado</option>
                            </select>
                        </div>
                    </div>
                </div>
               
                <div class="form-section">
                    <div class="form-section-title">Dados do Técnico</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editarCpfTecnico">CPF (Técnico):</label>
                            <input type="text" id="editarCpfTecnico" name="cpf_tecnico" required>
                        </div>
                        <div class="form-group">
                            <label for="editarAnosExperiencia">Anos de Experiência:</label>
                            <input type="number" id="editarAnosExperiencia" name="anos_experiencia" min="0" max="50" required>
                        </div>
                    </div>
                   
                    <div class="form-group">
                        <label for="editarPossuiLocal">Possui Local Próprio:</label>
                        <select id="editarPossuiLocal" name="possui_local_proprio" required onchange="toggleLocalProprio()">
                            <option value="sim">Sim</option>
                            <option value="nao">Não</option>
                        </select>
                    </div>
                   
                    <div id="localProprioFields" class="local-proprio-fields">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editarLogradouro">Logradouro:</label>
                                <input type="text" id="editarLogradouro" name="logradouro">
                            </div>
                            <div class="form-group">
                                <label for="editarNumeroLocal">Número:</label>
                                <input type="text" id="editarNumeroLocal" name="numero_local">
                            </div>
                        </div>
                       
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editarCepLocal">CEP:</label>
                                <input type="text" id="editarCepLocal" name="cep_local">
                            </div>
                            <div class="form-group">
                                <label for="editarInfoLocalizacao">Informações de Localização:</label>
                                <textarea id="editarInfoLocalizacao" name="informacao_localizacao"></textarea>
                            </div>
                        </div>
                    </div>
                   
                    <div class="form-group">
                        <label for="editarDescricao">Descrição Profissional:</label>
                        <textarea id="editarDescricao" name="descricao" required></textarea>
                    </div>
                </div>
               
                <div class="form-section">
                    <div class="form-section-title">Endereço Residencial</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editarEndereco">Endereço:</label>
                            <input type="text" id="editarEndereco" name="endereco" required>
                        </div>
                        <div class="form-group">
                            <label for="editarNumero">Número:</label>
                            <input type="text" id="editarNumero" name="numero" required>
                        </div>
                    </div>
                   
                    <div class="form-row">
                        <div class="form-group">
                            <label for="editarCep">CEP:</label>
                            <input type="text" id="editarCep" name="cep" required>
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <!-- Espaço vazio para alinhamento -->
                        </div>
                    </div>
                </div>
               
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-salvar">Salvar Alterações</button>
                </div>
            </form>
        </div>
    </div>
   
    <!-- Modal de Confirmação de Exclusão -->
    <div id="modalExcluir" class="modal">
        <div class="modal-content">
            <h3>Confirmar Exclusão</h3>
            <p id="textoConfirmacao">Tem certeza que deseja excluir este técnico?</p>
            <form id="formExcluir" method="POST">
                <input type="hidden" name="acao" value="excluir">
                <input type="hidden" name="tecnico_id" id="excluirTecnicoId">
               
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-excluir">Confirmar Exclusão</button>
                </div>
            </form>
        </div>
    </div>
   
    <!-- Modal de Alteração de Status -->
    <div id="modalStatus" class="modal">
        <div class="modal-content">
            <h3>Alterar Status do Técnico</h3>
            <p id="textoStatus">Selecione o novo status para este técnico:</p>
            <form id="formStatus" method="POST">
                <input type="hidden" name="acao" value="alterar_status">
                <input type="hidden" name="tecnico_id" id="statusTecnicoId">
               
                <div class="form-group">
                    <label for="novoStatus">Novo Status:</label>
                    <select id="novoStatus" name="status" required>
                        <option value="pendente">Pendente</option>
                        <option value="aprovado">Aprovado</option>
                        <option value="rejeitado">Rejeitado</option>
                    </select>
                </div>
               
                <div class="modal-buttons">
                    <button type="button" class="btn-cancelar" onclick="fecharModal()">Cancelar</button>
                    <button type="submit" class="btn-salvar">Alterar Status</button>
                </div>
            </form>
        </div>
    </div>


    <script>
        // Função para mostrar/ocultar campos de local próprio
        function toggleLocalProprio() {
            const possuiLocal = document.getElementById('editarPossuiLocal').value;
            const localFields = document.getElementById('localProprioFields');
           
            if (possuiLocal === 'sim') {
                localFields.style.display = 'block';
            } else {
                localFields.style.display = 'none';
            }
        }


        // Função para editar técnico
        function editarTecnico(tecnicoId) {
            // Buscar dados do técnico (em uma implementação real, isso viria do servidor via AJAX)
            // Por enquanto, vamos preencher com dados estáticos para demonstração
            document.getElementById('editarTecnicoId').value = tecnicoId;
            document.getElementById('editarUsuarioId').value = 1; // Em produção, buscar do servidor
           
            // Aqui você implementaria uma chamada AJAX para buscar os dados do técnico
            // Por enquanto, vamos apenas mostrar o modal
            document.getElementById('modalEditar').style.display = 'flex';
        }


        // Função para alterar status
        function alterarStatus(tecnicoId, statusAtual) {
            document.getElementById('statusTecnicoId').value = tecnicoId;
            document.getElementById('novoStatus').value = statusAtual;
            document.getElementById('modalStatus').style.display = 'flex';
        }


        // Função para excluir técnico
        function excluirTecnico(tecnicoId, nome) {
            document.getElementById('excluirTecnicoId').value = tecnicoId;
            document.getElementById('textoConfirmacao').textContent =
                'Tem certeza que deseja excluir o técnico "' + nome + '"? Esta ação não pode ser desfeita.';
            document.getElementById('modalExcluir').style.display = 'flex';
        }


        // Função para fechar modal
        function fecharModal() {
            document.getElementById('modalEditar').style.display = 'none';
            document.getElementById('modalExcluir').style.display = 'none';
            document.getElementById('modalStatus').style.display = 'none';
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


        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            toggleLocalProprio(); // Inicializar estado dos campos de local próprio
        });
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

