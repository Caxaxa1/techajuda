<?php
session_start();
require_once "../../src/config.php";

if (!isset($_SESSION['tecnico_id'])) {
    header("Location: tornar_tecnico.php");
    exit();
}

// NOVA VALIDAÇÃO: Verificar se o técnico está aprovado
$conn = getDBConnection();
$tecnico_id = $_SESSION['tecnico_id'];

$sql_status = "SELECT status FROM tecnicos WHERE id = $tecnico_id";
$result_status = $conn->query($sql_status);

if ($result_status->num_rows > 0) {
    $tecnico_status = $result_status->fetch_assoc();
    
    // Se o técnico não estiver aprovado, redirecionar
    if ($tecnico_status['status'] !== 'aprovado') {
        $conn->close();
        header("Location: tornar_tecnico.php?erro=nao_aprovado");
        exit();
    }
} else {
    // Técnico não encontrado
    $conn->close();
    header("Location: tornar_tecnico.php");
    exit();
}

// Processar edição dos dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_dados'])) {
    $tecnico_id = $_SESSION['tecnico_id'];
    $usuario_id = $_SESSION['tecnico_usuario_id'];
    
    // Dados do usuário
    $nome = $conn->real_escape_string($_POST['nome']);
    $sexo = $conn->real_escape_string($_POST['sexo']);
    $apelido = $conn->real_escape_string($_POST['apelido']);
    $email = $conn->real_escape_string($_POST['email']);
    $endereco = $conn->real_escape_string($_POST['endereco']);
    $cep = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cep']));
    $numero = $conn->real_escape_string($_POST['numero']);
    
    // Dados do técnico
    $anos_experiencia = intval($_POST['anos_experiencia']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    $possui_local_proprio = $conn->real_escape_string($_POST['possui_local_proprio']);
    
    // Dados de local próprio (se aplicável)
    $logradouro = '';
    $numero_local = '';
    $cep_local = '';
    $informacao_localizacao = '';
    
    if ($possui_local_proprio === 'sim') {
        $logradouro = $conn->real_escape_string($_POST['logradouro']);
        $numero_local = $conn->real_escape_string($_POST['numero_local']);
        $cep_local = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cep_local']));
        $informacao_localizacao = $conn->real_escape_string($_POST['informacao_localizacao']);
        
        // Formatar CEP do local
        if (!empty($cep_local)) {
            $cep_local = substr($cep_local, 0, 5) . '-' . substr($cep_local, 5, 3);
        }
    }
    
    // Formatar CEP do usuário
    if (!empty($cep)) {
        $cep = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }
    
    // Iniciar transação
    $conn->begin_transaction();
    
    try {
        // Atualizar dados do usuário
        $sql_usuario = "UPDATE usuarios SET 
                        nome = '$nome',
                        sexo = '$sexo',
                        apelido = '$apelido',
                        email = '$email',
                        endereco = '$endereco',
                        cep = '$cep',
                        numero = '$numero'
                        WHERE id = $usuario_id";
        
        $conn->query($sql_usuario);
        
        // Atualizar dados do técnico
        $sql_tecnico = "UPDATE tecnicos SET 
                        anos_experiencia = '$anos_experiencia',
                        descricao = '$descricao',
                        possui_local_proprio = '$possui_local_proprio',
                        logradouro = '$logradouro',
                        numero = '$numero_local',
                        cep = '$cep_local',
                        informacao_localizacao = '$informacao_localizacao'
                        WHERE id = $tecnico_id";
        
        $conn->query($sql_tecnico);
        
        $conn->commit();
        $_SESSION['usuario_nome'] = $nome;
        $mensagem_sucesso = "Dados atualizados com sucesso!";
    } catch (Exception $e) {
        $conn->rollback();
        $mensagem_erro = "Erro ao atualizar dados: " . $e->getMessage();
    }
    
    $conn->close();
}

// Buscar dados do técnico e usuário
$conn = getDBConnection();
$tecnico_id = $_SESSION['tecnico_id'];

$sql = "SELECT u.*, t.anos_experiencia, t.descricao as descricao_tecnico, t.cpf as cpf_tecnico,
               t.possui_local_proprio, t.status as status_tecnico, t.data_cadastro as data_cadastro_tecnico,
               t.logradouro, t.numero as numero_local, t.cep as cep_local, t.informacao_localizacao
        FROM usuarios u
        INNER JOIN tecnicos t ON u.id = t.usuario_id
        WHERE t.id = $tecnico_id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $dados = $result->fetch_assoc();
    $_SESSION['usuario_foto'] = $dados['foto_perfil'];
} else {
    die("Técnico não encontrado");
}

// Buscar especialidades do técnico
$sql_especialidades = "SELECT e.nome, e.icone 
                       FROM tecnico_especialidades te 
                       INNER JOIN especialidades e ON te.especialidade_id = e.id 
                       WHERE te.tecnico_id = $tecnico_id";
$result_especialidades = $conn->query($sql_especialidades);
$especialidades = [];
if ($result_especialidades->num_rows > 0) {
    while($row = $result_especialidades->fetch_assoc()) {
        $especialidades[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta Técnico - TechAjuda</title>
    <link rel="stylesheet" href="../../visualscript/css/style.css">
    <style>
        body {
            background-color: #2a2a2a;
            color: #e0e0e0;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
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
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-left: 10px;
            overflow: hidden;
        }

        /* Estilos para o conteúdo da conta */
        .conteudo-conta {
            padding: 160px 20px 80px;
            max-width: 900px;
            margin: 0 auto;
        }

        .titulo-conta {
            color: #ff4444;
            font-size: 2.5em;
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #ff4444;
            padding-bottom: 15px;
        }

        .info-container {
            background-color: #1a1a1a;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            margin-bottom: 30px;
        }

        .info-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }

        .info-section:last-child {
            border-bottom: none;
        }

        .section-title {
            color: #ff4444;
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #444;
        }

        .info-item {
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-label {
            color: #08ebf3;
            font-weight: bold;
            font-size: 1.1em;
            flex: 1;
        }

        .info-value {
            font-size: 1.2em;
            flex: 2;
            text-align: right;
        }

        .nome-usuario {
            font-size: 2em;
            color: #ffffff;
            text-align: center;
            margin-bottom: 30px;
        }

        /* Estilos para a seção de foto de perfil */
        .foto-perfil-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
            text-align: center;
        }

        .foto-perfil-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .foto-perfil {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #ff4444;
        }

        .foto-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background-color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #ff4444;
            color: #888;
            font-size: 1.2em;
        }

        .upload-form {
            text-align: center;
        }

        .botao-upload {
            display: inline-block;
            padding: 10px 20px;
            background-color: #ff4444;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
        }

        .botao-upload:hover {
            background-color: #cc0000;
        }

        .upload-form small {
            display: block;
            margin-top: 5px;
            color: #888;
            font-size: 0.8em;
        }

        /* Estilos para o formulário de edição */
        .form-editar {
            background-color: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #08ebf3;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #444;
            border-radius: 5px;
            background-color: #1a1a1a;
            color: white;
            font-size: 1em;
        }

        .form-group textarea {
            height: 100px;
            resize: vertical;
        }

        .form-group input:read-only {
            background-color: #333;
            color: #888;
        }

        .local-proprio-fields {
            display: none;
            margin-top: 15px;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            border-left: 3px solid #ff4444;
        }

        .botao-editar {
            background-color: #ff4444;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }

        .botao-salvar {
            background-color: #00cc00;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            margin-right: 10px;
        }

        .botao-cancelar {
            background-color: #666;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .mensagem-feedback {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }

        .mensagem-feedback.sucesso {
            background-color: #eeffee;
            color: #00cc00;
            border: 1px solid #00cc00;
        }

        .mensagem-feedback.erro {
            background-color: #ffeeee;
            color: #cc0000;
            border: 1px solid #ff4444;
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
            gap: 10px;
            margin-top: 10px;
        }

        .especialidade-item {
            background-color: #2a2a2a;
            padding: 8px 15px;
            border-radius: 20px;
            border: 1px solid #ff4444;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* Rodapé */
        .rodape-logado {
            background-color: #1a1a1a;
            text-align: center;
            padding: 20px;
            font-size: 0.9em;
            color: #a0a0a0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .rodape-logado a {
            color: #08ebf3;
            text-decoration: none;
            font-weight: bold;
            margin: 0 10px;
        }

        .rodape-logado a:hover {
            text-decoration: underline;
        }

        .editar-botao-container {
            text-align: center;
            margin-top: 20px;
        }
    </style>
    <script>
        function toggleEdicao() {
            const formEditar = document.getElementById('formEditar');
            const btnEditar = document.getElementById('btnEditar');
            
            if (formEditar.style.display === 'none') {
                formEditar.style.display = 'block';
                btnEditar.style.display = 'none';
            } else {
                formEditar.style.display = 'none';
                btnEditar.style.display = 'block';
            }
        }

        function cancelarEdicao() {
            document.getElementById('formEditar').style.display = 'none';
            document.getElementById('btnEditar').style.display = 'block';
        }

        // Função para mostrar/ocultar campos de local próprio
        function toggleLocalProprio() {
            const possuiLocal = document.querySelector('select[name="possui_local_proprio"]').value;
            const localFields = document.querySelector('.local-proprio-fields');
           
            if (possuiLocal === 'sim') {
                localFields.style.display = 'block';
            } else {
                localFields.style.display = 'none';
            }
        }

        // Função para formatar CEP
        function formatarCEP(cep) {
            cep = cep.replace(/\D/g, '');
            cep = cep.replace(/^(\d{5})(\d)/, '$1-$2');
            return cep;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const cepInput = document.querySelector('input[name="cep"]');
            const cepLocalInput = document.querySelector('input[name="cep_local"]');
            
            if (cepInput) {
                cepInput.addEventListener('input', function() {
                    this.value = formatarCEP(this.value);
                    if (this.value.length > 9) {
                        this.value = this.value.slice(0, 9);
                    }
                });
            }
            
            if (cepLocalInput) {
                cepLocalInput.addEventListener('input', function() {
                    this.value = formatarCEP(this.value);
                    if (this.value.length > 9) {
                        this.value = this.value.slice(0, 9);
                    }
                });
            }

            // Inicializar estado dos campos de local próprio
            const selectLocal = document.querySelector('select[name="possui_local_proprio"]');
            if (selectLocal) {
                selectLocal.addEventListener('change', toggleLocalProprio);
                toggleLocalProprio(); // Chamar uma vez para inicializar
            }
        });
    </script>
</head>
<body>
    <!-- Header -->
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
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #666; display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-size: 18px;">👤</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>
   
    <!-- Conteúdo da Conta -->
    <main class="conteudo-conta">
        <h1 class="titulo-conta">Minha Conta - Técnico</h1>
       
        <!-- Mensagens de Feedback -->
        <?php if (isset($mensagem_sucesso)): ?>
            <div class="mensagem-feedback sucesso">
                <?php echo $mensagem_sucesso; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($mensagem_erro)): ?>
            <div class="mensagem-feedback erro">
                <?php echo $mensagem_erro; ?>
            </div>
        <?php endif; ?>

        <div class="info-container">
            <!-- Foto de Perfil -->
            <div class="foto-perfil-section">
                <div class="section-title">Foto de Perfil</div>
                <div class="foto-perfil-container">
                    <?php if (!empty($dados['foto_perfil'])): ?>
                        <img src="../../<?php echo htmlspecialchars($dados['foto_perfil']); ?>" 
                             alt="Foto de Perfil" class="foto-perfil">
                    <?php else: ?>
                        <div class="foto-placeholder">
                            <span>Sem foto</span>
                        </div>
                    <?php endif; ?>
                   
                    <form action="../upload_foto.php" method="post" enctype="multipart/form-data" class="upload-form">
                        <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*"
                               style="display: none;" onchange="this.form.submit()">
                        <label for="foto_perfil" class="botao-upload">Alterar Foto</label>
                        <small>Apenas JPG, PNG ou GIF. Máx: 5MB</small>
                    </form>
                </div>
            </div>

            <div class="nome-usuario"><?php echo htmlspecialchars($dados['nome']); ?></div>

            <!-- Seção: Dados Pessoais -->
            <div class="info-section">
                <div class="section-title">Dados Pessoais</div>
                
                <div class="info-item">
                    <div class="info-label">Idade:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['idade']); ?> anos</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">CPF:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['cpf']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Sexo:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['sexo']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Apelido:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['apelido']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">E-mail:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['email']); ?></div>
                </div>
            </div>

            <!-- Seção: Dados do Técnico -->
            <div class="info-section">
                <div class="section-title">Dados do Técnico</div>
                
                <div class="info-item">
                    <div class="info-label">CPF Cadastrado:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['cpf_tecnico']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Anos de Experiência:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['anos_experiencia']); ?> anos</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Possui Local Próprio:</div>
                    <div class="info-value"><?php echo $dados['possui_local_proprio'] == 'sim' ? 'Sim' : 'Não'; ?></div>
                </div>
                
                <?php if ($dados['possui_local_proprio'] == 'sim' && !empty($dados['logradouro'])): ?>
                <div class="info-item">
                    <div class="info-label">Local de Atendimento:</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($dados['logradouro']); ?>, 
                        <?php echo htmlspecialchars($dados['numero_local']); ?> - 
                        CEP: <?php echo htmlspecialchars($dados['cep_local']); ?>
                        <?php if (!empty($dados['informacao_localizacao'])): ?>
                            <br><small><?php echo htmlspecialchars($dados['informacao_localizacao']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">Status:</div>
                    <div class="info-value <?php echo 'status-' . $dados['status_tecnico']; ?>">
                        <?php 
                        $status_text = [
                            'aprovado' => 'Aprovado',
                            'pendente' => 'Pendente',
                            'rejeitado' => 'Rejeitado'
                        ];
                        echo $status_text[$dados['status_tecnico']] ?? $dados['status_tecnico'];
                        ?>
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Data de Cadastro:</div>
                    <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($dados['data_cadastro_tecnico'])); ?></div>
                </div>

                <?php if (!empty($especialidades)): ?>
                <div class="info-item">
                    <div class="info-label">Especialidades:</div>
                    <div class="info-value">
                        <div class="especialidades-list">
                            <?php foreach ($especialidades as $especialidade): ?>
                                <div class="especialidade-item">
                                    <span><?php echo $especialidade['icone']; ?></span>
                                    <span><?php echo htmlspecialchars($especialidade['nome']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="info-item">
                    <div class="info-label">Descrição:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($dados['descricao_tecnico'])); ?></div>
                </div>
            </div>

            <!-- Seção: Endereço -->
            <div class="info-section">
                <div class="section-title">Endereço Residencial</div>
                
                <div class="info-item">
                    <div class="info-label">Endereço:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['endereco']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Número:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['numero']); ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">CEP:</div>
                    <div class="info-value"><?php echo htmlspecialchars($dados['cep']); ?></div>
                </div>
            </div>

            <!-- Botão para editar -->
            <div class="editar-botao-container">
                <button id="btnEditar" class="botao-editar" onclick="toggleEdicao()">✏️ Editar Dados</button>
            </div>

            <!-- Formulário de Edição -->
            <form id="formEditar" method="POST" style="display: none;" class="form-editar">
                <input type="hidden" name="editar_dados" value="1">
                
                <div class="section-title">Editar Dados</div>

                <!-- Dados do Usuário -->
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($dados['nome']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="sexo">Sexo:</label>
                    <select id="sexo" name="sexo" required>
                        <option value="M" <?php echo $dados['sexo'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                        <option value="F" <?php echo $dados['sexo'] == 'F' ? 'selected' : ''; ?>>Feminino</option>
                        <option value="Outro" <?php echo $dados['sexo'] == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="apelido">Apelido:</label>
                    <input type="text" id="apelido" name="apelido" value="<?php echo htmlspecialchars($dados['apelido']); ?>">
                </div>

                <div class="form-group">
                    <label for="email">E-mail:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($dados['email']); ?>" required>
                </div>

                <!-- Dados do Técnico -->
                <div class="form-group">
                    <label for="anos_experiencia">Anos de Experiência:</label>
                    <input type="number" id="anos_experiencia" name="anos_experiencia" 
                           value="<?php echo htmlspecialchars($dados['anos_experiencia']); ?>" min="0" max="50" required>
                </div>

                <div class="form-group">
                    <label for="possui_local_proprio">Possui Local Próprio para Atendimento?</label>
                    <select id="possui_local_proprio" name="possui_local_proprio" required onchange="toggleLocalProprio()">
                        <option value="sim" <?php echo $dados['possui_local_proprio'] == 'sim' ? 'selected' : ''; ?>>Sim</option>
                        <option value="nao" <?php echo $dados['possui_local_proprio'] == 'nao' ? 'selected' : ''; ?>>Não</option>
                    </select>
                </div>

                <!-- Campos de Local Próprio -->
                <div class="local-proprio-fields" style="<?php echo $dados['possui_local_proprio'] == 'sim' ? 'display: block;' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label for="logradouro">Logradouro (Rua, Avenida, etc.):</label>
                        <input type="text" id="logradouro" name="logradouro" value="<?php echo htmlspecialchars($dados['logradouro']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="numero_local">Número:</label>
                        <input type="text" id="numero_local" name="numero_local" value="<?php echo htmlspecialchars($dados['numero_local']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="cep_local">CEP:</label>
                        <input type="text" id="cep_local" name="cep_local" value="<?php echo htmlspecialchars($dados['cep_local']); ?>">
                    </div>

                    <div class="form-group">
                        <label for="informacao_localizacao">Informações Adicionais de Localização:</label>
                        <textarea id="informacao_localizacao" name="informacao_localizacao"><?php echo htmlspecialchars($dados['informacao_localizacao']); ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="descricao">Descrição Profissional:</label>
                    <textarea id="descricao" name="descricao" required><?php echo htmlspecialchars($dados['descricao_tecnico']); ?></textarea>
                </div>

                <!-- Endereço Residencial -->
                <div class="form-group">
                    <label for="endereco">Endereço Residencial:</label>
                    <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($dados['endereco']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="cep">CEP Residencial:</label>
                    <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($dados['cep']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="numero">Número Residencial:</label>
                    <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($dados['numero']); ?>" required>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" class="botao-salvar">💾 Salvar Alterações</button>
                    <button type="button" class="botao-cancelar" onclick="cancelarEdicao()">❌ Cancelar</button>
                </div>
            </form>
        </div>
    </main>
   
    <!-- Footer -->
    <footer class="rodape-logado">
        <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
        <p>
            <a href="../suporte2.php">Suporte</a> |
            <a href="../suporte2.php#termos">Termos de Uso</a> |
            <a href="../suporte2.php#politica">Política de Privacidade</a>
        </p>
    </footer>
</body>
</html>