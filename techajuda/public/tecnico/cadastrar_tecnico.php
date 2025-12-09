<?php
session_start();
require_once "../../src/config.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../entrar.php");
    exit();
}

// Verificar se já é técnico ou tem pedido pendente
$conn = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];
$sql_verifica_tecnico = "SELECT is_tecnico, pedido_tecnico_pendente FROM usuarios WHERE id = '$usuario_id'";
$result_verifica = $conn->query($sql_verifica_tecnico);

if ($result_verifica->num_rows > 0) {
    $usuario = $result_verifica->fetch_assoc();
    if ($usuario['is_tecnico']) {
        header("Location: minha_conta_tecnico.php");
        exit();
    } elseif ($usuario['pedido_tecnico_pendente']) {
        $_SESSION['mensagem_info'] = "Seu cadastro de técnico está em análise. Aguarde a aprovação.";
        header("Location: ../area_logada.php");
        exit();
    }
}

$erro_cadastro = false;
$sucesso_cadastro = false;
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coletar dados do formulário
    $cpf = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cpf']));
    $anos_experiencia = intval($_POST['anos_experiencia']);
    $possui_local_proprio = $conn->real_escape_string($_POST['possui_local_proprio']);
    $descricao = $conn->real_escape_string($_POST['descricao']);
    
    // Verificar se foram selecionadas especialidades
    if (!isset($_POST['especialidades']) || empty($_POST['especialidades'])) {
        $erro_cadastro = true;
        $mensagem_erro = "Selecione pelo menos uma especialidade!";
    } else {
        // Remover possíveis duplicatas dos IDs das especialidades
        $especialidades_selecionadas = array_unique($_POST['especialidades']);
       
        // Formatar CPF
        $cpf_formatado = substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
       
        // Dados opcionais (apenas se possui local próprio)
        $logradouro = '';
        $numero = '';
        $cep = '';
        $informacao_localizacao = '';
       
        if ($possui_local_proprio === 'sim') {
            $logradouro = $conn->real_escape_string($_POST['logradouro']);
            $numero = $conn->real_escape_string($_POST['numero']);
            $cep = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cep']));
            $informacao_localizacao = $conn->real_escape_string($_POST['informacao_localizacao']);
           
            // Formatar CEP
            if (!empty($cep)) {
                $cep = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
            }
        }
       
        // Iniciar transação
        $conn->begin_transaction();
        
        try {
            // Verificar se já existe técnico APROVADO ou PENDENTE com o mesmo CPF
            $sql_verifica_cpf = "SELECT id FROM tecnicos WHERE cpf = '$cpf_formatado' AND status IN ('aprovado', 'pendente')";
            $result_verifica_cpf = $conn->query($sql_verifica_cpf);

            if ($result_verifica_cpf->num_rows > 0) {
                $erro_cadastro = true;
                $mensagem_erro = "Já existe um cadastro ativo ou em análise com este CPF!";
                $conn->rollback();
            } else {
                // Se existe um técnico REJEITADO com este CPF, vamos atualizá-lo em vez de criar novo
                $sql_verifica_rejeitado = "SELECT id FROM tecnicos WHERE cpf = '$cpf_formatado' AND status = 'rejeitado'";
                $result_rejeitado = $conn->query($sql_verifica_rejeitado);
                
                if ($result_rejeitado->num_rows > 0) {
                    // Atualizar cadastro rejeitado existente
                    $tecnico_rejeitado = $result_rejeitado->fetch_assoc();
                    $tecnico_id = $tecnico_rejeitado['id'];
                    
                    $sql_update_tecnico = "UPDATE tecnicos SET 
                        anos_experiencia = '$anos_experiencia',
                        possui_local_proprio = '$possui_local_proprio',
                        logradouro = '$logradouro',
                        numero = '$numero',
                        cep = '$cep',
                        informacao_localizacao = '$informacao_localizacao',
                        descricao = '$descricao',
                        status = 'pendente'
                        WHERE id = '$tecnico_id'";
                        
                    if ($conn->query($sql_update_tecnico)) {
                        // Deletar especialidades antigas
                        $sql_delete_especialidades = "DELETE FROM especialidades_tecnico WHERE tecnico_id = '$tecnico_id'";
                        $conn->query($sql_delete_especialidades);
                        
                        // Inserir novas especialidades
                        foreach ($especialidades_selecionadas as $categoria_id) {
                            $categoria_id = intval($categoria_id);
                            $sql_especialidade = "INSERT INTO especialidades_tecnico (tecnico_id, categoria_id) 
                                                  VALUES ('$tecnico_id', '$categoria_id')";
                            $conn->query($sql_especialidade);
                        }
                        
                        // Marcar pedido como pendente
                        $sql_update_usuario = "UPDATE usuarios SET pedido_tecnico_pendente = TRUE WHERE id = '$usuario_id'";
                        $conn->query($sql_update_usuario);
                        
                        $conn->commit();
                        
                        $_SESSION['mensagem_sucesso'] = "Cadastro reenviado para análise! Aguarde a aprovação de nossa equipe.";
                        header("Location: ../area_logada.php");
                        exit();
                    } else {
                        throw new Exception("Erro ao atualizar cadastro: " . $conn->error);
                    }
                } else {
                    // Criar novo cadastro
                    $sql_tecnico = "INSERT INTO tecnicos (usuario_id, cpf, anos_experiencia, possui_local_proprio, logradouro, numero, cep, informacao_localizacao, descricao, status)
                            VALUES ('$usuario_id', '$cpf_formatado', '$anos_experiencia', '$possui_local_proprio', '$logradouro', '$numero', '$cep', '$informacao_localizacao', '$descricao', 'pendente')";
                   
                    if ($conn->query($sql_tecnico)) {
                        $tecnico_id = $conn->insert_id;
                        
                        foreach ($especialidades_selecionadas as $categoria_id) {
                            $categoria_id = intval($categoria_id);
                            $sql_especialidade = "INSERT INTO especialidades_tecnico (tecnico_id, categoria_id) 
                                                  VALUES ('$tecnico_id', '$categoria_id')";
                            $conn->query($sql_especialidade);
                        }
                        
                        $sql_update_usuario = "UPDATE usuarios SET pedido_tecnico_pendente = TRUE WHERE id = '$usuario_id'";
                        $conn->query($sql_update_usuario);
                        
                        $conn->commit();
                        
                        $_SESSION['mensagem_sucesso'] = "Cadastro enviado para análise! Aguarde a aprovação de nossa equipe.";
                        header("Location: ../area_logada.php");
                        exit();
                    } else {
                        throw new Exception("Erro ao cadastrar técnico: " . $conn->error);
                    }
                }
            }
        } catch (Exception $e) {
            $conn->rollback();
            $erro_cadastro = true;
            $mensagem_erro = "Erro no cadastro: " . $e->getMessage();
        }
    }
}

// Buscar categorias de serviço disponíveis
$sql_especialidades = "SELECT id, nome, descricao FROM categorias_servico ORDER BY nome";
$result_especialidades = $conn->query($sql_especialidades);
$especialidades = [];

if ($result_especialidades->num_rows > 0) {
    while($row = $result_especialidades->fetch_assoc()) {
        // Definir ícones baseados no nome da categoria
        $icone = '🔧';
        $nome_lower = strtolower($row['nome']);
        
        if (strpos($nome_lower, 'computador') !== false) $icone = '💻';
        elseif (strpos($nome_lower, 'rede') !== false || strpos($nome_lower, 'internet') !== false) $icone = '🌐';
        elseif (strpos($nome_lower, 'smartphone') !== false || strpos($nome_lower, 'tablet') !== false) $icone = '📱';
        elseif (strpos($nome_lower, 'sistema') !== false) $icone = '💻';
        elseif (strpos($nome_lower, 'programação') !== false || strpos($nome_lower, 'desenvolvimento') !== false) $icone = '👨‍💻';
        elseif (strpos($nome_lower, 'segurança') !== false) $icone = '🔒';
        elseif (strpos($nome_lower, 'backup') !== false) $icone = '💾';
        
        $especialidades[] = [
            'id' => $row['id'],
            'nome' => $row['nome'],
            'icone' => $icone
        ];
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Técnico - TechAjuda</title>
    <link rel="stylesheet" href="../../visualscript/css/style.css">
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

        .main-content {
            flex: 1;
            padding: 90px 20px 60px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
        }

        .form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 30px;
            width: 100%;
            max-width: 600px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo-com-aureola {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: radial-gradient(circle, #08ebf3 0%, transparent 70%);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            margin-bottom: 15px;
        }

        .logo-com-aureola img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .form-title {
            text-align: center;
            color: #08ebf3;
            margin-bottom: 25px;
            font-size: 1.7em;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #08ebf3;
            font-weight: 500;
            font-size: 0.95em;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #444;
            border-radius: 8px;
            color: #e0e0e0;
            font-size: 0.95em;
            transition: all 0.3s;
        }

        /* Estilo específico para o select de local próprio - CORRIGIDO */
        .form-select {
            background: rgba(255, 255, 255, 0.05);
            color: #e0e0e0;
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 4 5'><path fill='%2308ebf3' d='M2 0L0 2h4zm0 5L0 3h4z'/></svg>");
            background-repeat: no-repeat;
            background-position: right 12px center;
            background-size: 10px;
            padding-right: 35px;
        }

        .form-select option {
            background: #2a2a2a;
            color: #e0e0e0;
            padding: 8px;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: #08ebf3;
            box-shadow: 0 0 12px rgba(8, 235, 243, 0.3);
            background: rgba(255, 255, 255, 0.08);
        }

        .form-textarea {
            resize: vertical;
            min-height: 100px;
            line-height: 1.5;
        }

        .especialidades-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(130px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }

        .especialidade-option {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid #444;
            border-radius: 8px;
            padding: 15px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .especialidade-option:hover {
            border-color: #08ebf3;
            transform: translateY(-2px);
            background: rgba(255, 255, 255, 0.08);
        }

        .especialidade-option.selected {
            background: #08ebf3;
            border-color: #08ebf3;
            color: #000;
        }

        .especialidade-option input {
            display: none;
        }

        .especialidade-icone {
            font-size: 1.4em;
            margin-bottom: 5px;
            display: block;
        }

        .especialidade-nome {
            font-size: 0.75em;
            font-weight: 500;
            line-height: 1.3;
        }

        .contador-especialidades {
            text-align: center;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
            margin: 10px 0;
            font-weight: 500;
            border: 1px solid #444;
            font-size: 0.9em;
        }

        .local-proprio-fields {
            display: none;
            background: rgba(255, 255, 255, 0.03);
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
            border-left: 3px solid #08ebf3;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(8, 235, 243, 0.3);
        }

        .submit-btn:disabled {
            background: linear-gradient(135deg, #666, #555);
            color: #999;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .submit-btn:hover:not(:disabled) {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(8, 235, 243, 0.4);
        }

        .back-link {
            display: block;
            text-align: center;
            color: #08ebf3;
            text-decoration: none;
            margin-top: 20px;
            font-weight: 500;
            transition: color 0.3s;
            font-size: 0.95em;
        }

        .back-link:hover {
            color: #00bcd4;
            text-decoration: underline;
        }

        .mensagem-feedback {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
            animation: slideDown 0.3s ease-out;
            font-size: 0.95em;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .erro {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 1px solid #c82333;
        }

        .sucesso {
            background: linear-gradient(135deg, #28a745, #1e7e34);
            color: white;
            border: 1px solid #1e7e34;
        }

        /* Header Menor - Consistente com area_logada */
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

        /* Footer Menor */
        .rodape-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            padding: 20px;
            font-size: 0.85em;
            color: #888;
            margin-top: auto;
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
            .main-content {
                padding: 80px 15px 50px;
            }

            .form-container {
                padding: 20px;
            }

            .especialidades-grid {
                grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
                gap: 8px;
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

            .form-title {
                font-size: 1.5em;
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
            
            .form-container {
                padding: 15px;
            }
            
            .account-circle {
                width: 30px;
                height: 30px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Menor - Consistente com area_logada -->
    <header class="topo-logado">
        <div class="logo">
            <img src="../../visualscript/imagem/logotcc.png" alt="TechAjuda">
        </div>
        <div class="nav-right">
            <a href="../area_logada.php" class="nav-link">Menu Principal</a>
            <a href="../minha_conta.php" class="nav-link">Minha Conta</a>
            <a href="../logout.php" class="nav-link">Sair</a>
            <div class="account-circle">
                <?php if (!empty($_SESSION['usuario_foto'])): ?>
                    <img src="../../<?php echo $_SESSION['usuario_foto']; ?>" alt="Foto Perfil"
                         style="width: 100%; height: 100%; object-fit: cover;">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #666, #888); display: flex; align-items: center; justify-content: center;">
                        <span style="color: white; font-size: 16px;">👤</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <div class="main-content">
        <div class="form-container">
            <div class="logo-section">
                <div class="logo-com-aureola">
                    <?php if (!empty($_SESSION['usuario_foto'])): ?>
                        <img src="../../<?php echo $_SESSION['usuario_foto']; ?>" alt="Foto Perfil">
                    <?php else: ?>
                        <div style="width: 60px; height: 60px; background: #444; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #888; font-size: 1.3em;">
                            👤
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <h1 class="form-title">Cadastro de Técnico</h1>
           
            <?php if ($erro_cadastro): ?>
                <div class="mensagem-feedback erro">
                    <?php echo $mensagem_erro; ?>
                </div>
            <?php endif; ?>
           
            <form method="post">
                <!-- CPF -->
                <div class="form-group">
                    <label class="form-label" for="cpf">CPF</label>
                    <input type="text" id="cpf" name="cpf" class="form-input" 
                           placeholder="000.000.000-00" required maxlength="14"
                           pattern="\d{3}\.\d{3}\.\d{3}-\d{2}"
                           title="Digite o CPF no formato 000.000.000-00">
                </div>

                <!-- Anos de Experiência -->
                <div class="form-group">
                    <label class="form-label" for="anos_experiencia">Anos de Experiência</label>
                    <input type="number" id="anos_experiencia" name="anos_experiencia" 
                           class="form-input" min="0" max="50" required
                           placeholder="Quantos anos de experiência você possui?">
                </div>

                <!-- Especialidades -->
                <div class="form-group">
                    <label class="form-label">Especialidades *</label>
                    <div style="color: #b0b0b0; font-size: 0.85em; margin-bottom: 10px;">
                        Selecione pelo menos uma especialidade
                    </div>
                    <div class="especialidades-grid">
                        <?php foreach ($especialidades as $especialidade): ?>
                            <div class="especialidade-option" onclick="toggleEspecialidade(this)">
                                <input type="checkbox" name="especialidades[]" value="<?php echo $especialidade['id']; ?>">
                                <span class="especialidade-icone"><?php echo $especialidade['icone']; ?></span>
                                <span class="especialidade-nome"><?php echo htmlspecialchars($especialidade['nome']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="contador-especialidades" id="contador-especialidades">
                        0 especialidade(s) selecionada(s)
                    </div>
                </div>

                <!-- Local Próprio -->
                <div class="form-group">
                    <label class="form-label" for="possui_local_proprio">Possui local próprio para atendimento?</label>
                    <select id="possui_local_proprio" name="possui_local_proprio" class="form-select" onchange="toggleLocalProprio()" required>
                        <option value="">Selecione...</option>
                        <option value="sim">Sim</option>
                        <option value="nao">Não</option>
                    </select>
                </div>

                <!-- Campos de Local Próprio -->
                <div class="local-proprio-fields" id="localProprioFields">
                    <div class="form-group">
                        <label class="form-label" for="logradouro">Logradouro</label>
                        <input type="text" id="logradouro" name="logradouro" class="form-input" placeholder="Rua, Avenida, etc.">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="numero">Número</label>
                        <input type="text" id="numero" name="numero" class="form-input" placeholder="Número do estabelecimento">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="cep">CEP</label>
                        <input type="text" id="cep" name="cep" class="form-input" placeholder="00000-000" maxlength="9">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="informacao_localizacao">Informações Adicionais</label>
                        <textarea id="informacao_localizacao" name="informacao_localizacao" class="form-textarea" placeholder="Ponto de referência, andar, sala, etc."></textarea>
                    </div>
                </div>

                <!-- Descrição -->
                <div class="form-group">
                    <label class="form-label" for="descricao">Descrição Profissional</label>
                    <textarea id="descricao" name="descricao" class="form-textarea" 
                              placeholder="Descreva sua experiência profissional, formação, certificações e habilidades técnicas..." 
                              required></textarea>
                </div>

                <button type="submit" class="submit-btn" id="botao-enviar" disabled>
                    Enviar Cadastro para Análise
                </button>

                <a href="tornar_tecnico.php" class="back-link">← Voltar</a>
            </form>
        </div>
    </div>

    <!-- Footer Menor -->
    <footer class="rodape-logado">
        <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
        <p>
            <a href="../suporte2.php">Suporte</a> |
            <a href="../suporte2.php#termos">Termos de Uso</a> |
            <a href="../suporte2.php#politica">Política de Privacidade</a>
        </p>
    </footer>

    <script>
        // Formatar CPF
        function formatarCPF(cpf) {
            cpf = cpf.replace(/\D/g, '');
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
            cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
            return cpf;
        }

        // Formatar CEP
        function formatarCEP(cep) {
            cep = cep.replace(/\D/g, '');
            cep = cep.replace(/^(\d{5})(\d)/, '$1-$2');
            return cep;
        }

        // Mostrar/ocultar campos de local próprio
        function toggleLocalProprio() {
            const possuiLocal = document.getElementById('possui_local_proprio').value;
            const localFields = document.getElementById('localProprioFields');
            localFields.style.display = possuiLocal === 'sim' ? 'block' : 'none';
        }

        // Contador de especialidades
        function atualizarContadorEspecialidades() {
            const especialidadesSelecionadas = document.querySelectorAll('input[name="especialidades[]"]:checked').length;
            const contador = document.getElementById('contador-especialidades');
            const botaoEnviar = document.getElementById('botao-enviar');
            
            contador.textContent = `${especialidadesSelecionadas} especialidade(s) selecionada(s)`;
            botaoEnviar.disabled = especialidadesSelecionadas === 0;
            
            // Mudar cor do contador baseado na seleção
            if (especialidadesSelecionadas > 0) {
                contador.style.background = 'rgba(8, 235, 243, 0.1)';
                contador.style.borderColor = '#08ebf3';
                contador.style.color = '#08ebf3';
            } else {
                contador.style.background = 'rgba(255, 255, 255, 0.05)';
                contador.style.borderColor = '#444';
                contador.style.color = '#e0e0e0';
            }
        }

        // Selecionar especialidade
        function toggleEspecialidade(element) {
            const checkbox = element.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
            element.classList.toggle('selected', checkbox.checked);
            atualizarContadorEspecialidades();
        }

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            // Máscaras
            const cpfInput = document.getElementById('cpf');
            cpfInput.addEventListener('input', function() {
                this.value = formatarCPF(this.value);
            });

            const cepInput = document.getElementById('cep');
            if (cepInput) {
                cepInput.addEventListener('input', function() {
                    this.value = formatarCEP(this.value);
                });
            }

            // Inicializar estado
            toggleLocalProprio();
            atualizarContadorEspecialidades();
        });
    </script>
</body>
</html>