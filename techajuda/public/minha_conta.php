<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

// Verificar se o usuário é técnico
$conn = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];

$sql = "SELECT u.*, t.id as tecnico_id, t.status as status_tecnico, t.anos_experiencia, 
               t.descricao as descricao_tecnico, t.possui_local_proprio, t.logradouro, 
               t.numero as numero_local, t.cep as cep_local, t.informacao_localizacao
        FROM usuarios u 
        LEFT JOIN tecnicos t ON u.id = t.usuario_id 
        WHERE u.id = $usuario_id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $usuario = $result->fetch_assoc();
    $is_tecnico = !empty($usuario['tecnico_id']) && $usuario['status_tecnico'] === 'aprovado';
    $_SESSION['usuario_foto'] = $usuario['foto_perfil'];
} else {
    die("Usuário não encontrado");
}

// Processar edição dos dados
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_dados'])) {
    $nome = $conn->real_escape_string($_POST['nome']);
    $sexo = $conn->real_escape_string($_POST['sexo']);
    $apelido = $conn->real_escape_string($_POST['apelido']);
    $email = $conn->real_escape_string($_POST['email']);
    $celular = $conn->real_escape_string($_POST['celular']);
    $endereco = $conn->real_escape_string($_POST['endereco']);
    $cep = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cep']));
    $numero = $conn->real_escape_string($_POST['numero']);
    $cidade = $conn->real_escape_string($_POST['cidade']);
    $estado = $conn->real_escape_string($_POST['estado']);
    
    // Formatar CEP
    if (!empty($cep)) {
        $cep = substr($cep, 0, 5) . '-' . substr($cep, 5, 3);
    }
    
    $sql_update = "UPDATE usuarios SET 
            nome = '$nome',
            sexo = '$sexo',
            apelido = '$apelido',
            email = '$email',
            celular = '$celular',
            endereco = '$endereco',
            cep = '$cep',
            numero = '$numero',
            cidade = '$cidade',
            estado = '$estado'
            WHERE id = $usuario_id";
    
    if ($conn->query($sql_update)) {
        $_SESSION['usuario_nome'] = $nome;
        $mensagem_sucesso = "Dados atualizados com sucesso!";
    } else {
        $mensagem_erro = "Erro ao atualizar dados: " . $conn->error;
    }
}

// Formatar celular para exibição
$celular_formatado = $usuario['celular'];
if (!empty($celular_formatado)) {
    $celular_formatado = preg_replace('/[^0-9]/', '', $celular_formatado);
    if (strlen($celular_formatado) === 11) {
        $celular_formatado = '(' . substr($celular_formatado, 0, 2) . ') ' . substr($celular_formatado, 2, 5) . '-' . substr($celular_formatado, 7);
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minha Conta - TechAjuda</title>
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

        /* Header */
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

        .conteudo-conta {
            padding: 30px 20px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .titulo-conta {
            color: #08ebf3;
            font-size: 2em;
            text-align: center;
            margin-bottom: 30px;
            font-weight: 700;
            text-shadow: 0 2px 8px rgba(8, 235, 243, 0.3);
        }

        /* Layout Principal */
        .perfil-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        @media (max-width: 768px) {
            .perfil-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }

        /* Coluna da Foto */
        .foto-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
            height: fit-content;
        }

        .foto-perfil {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #08ebf3;
            margin-bottom: 15px;
            box-shadow: 0 0 20px rgba(8, 235, 243, 0.3);
        }

        .foto-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #08ebf3, #007acc);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 3px solid #08ebf3;
            color: white;
            font-size: 2em;
        }

        .nome-usuario {
            font-size: 1.3em;
            color: #ffffff;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .botao-upload {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            width: 100%;
            margin-bottom: 10px;
            font-size: 0.9em;
        }

        .botao-upload:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
        }

        .upload-info {
            color: #888;
            font-size: 0.75em;
            margin-bottom: 20px;
            line-height: 1.3;
        }

        /* Seção WhatsApp */
        .whatsapp-section {
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #25D366, #128C7E);
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.3);
            border: 2px solid #0daa5e;
        }

        .whatsapp-label {
            color: white;
            font-weight: 700;
            font-size: 0.9em;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .whatsapp-number {
            color: white;
            font-size: 1.2em;
            font-weight: 800;
            margin-bottom: 12px;
            background: rgba(0, 0, 0, 0.2);
            padding: 8px 12px;
            border-radius: 8px;
            display: inline-block;
        }

        .botao-whatsapp {
            display: inline-block;
            background: white;
            color: #25D366;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 700;
            font-size: 0.9em;
            transition: all 0.3s ease;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            border: 2px solid white;
        }

        .botao-whatsapp:hover {
            background: #25D366;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.3);
        }

        /* Seção de Informações */
        .info-section {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
        }

        .section-title {
            color: #08ebf3;
            font-size: 1.3em;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #08ebf3;
        }

        /* Grid de Informações */
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .info-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #08ebf3;
            transition: all 0.3s ease;
        }

        .info-item:hover {
            background: rgba(255, 255, 255, 0.08);
            transform: translateY(-2px);
        }

        .info-label {
            color: #08ebf3;
            font-weight: 600;
            font-size: 0.85em;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1em;
            color: #ffffff;
            font-weight: 400;
        }

        /* Seção do Técnico */
        .tecnico-badge {
            background: linear-gradient(135deg, #ff6b35, #f7931e);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8em;
            display: inline-block;
            margin-bottom: 15px;
            box-shadow: 0 3px 10px rgba(255, 107, 53, 0.3);
        }

        .tecnico-info {
            background: rgba(255, 107, 53, 0.1);
            border: 1px solid #ff6b35;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Botões */
        .botoes-container {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 25px;
            flex-wrap: wrap;
        }

        .botao-editar {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.95em;
        }

        .botao-editar:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(8, 235, 243, 0.4);
        }

        .botao-salvar {
            background: linear-gradient(135deg, #00cc00, #00aa00);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.95em;
        }

        .botao-salvar:hover {
            background: linear-gradient(135deg, #00aa00, #008800);
            transform: translateY(-2px);
        }

        .botao-cancelar {
            background: linear-gradient(135deg, #666, #555);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            font-size: 0.95em;
        }

        .botao-cancelar:hover {
            background: linear-gradient(135deg, #555, #444);
            transform: translateY(-2px);
        }

        /* Formulário */
        .form-editar {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
            display: none;
            border: 1px solid #333;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #08ebf3;
            margin-bottom: 5px;
            font-weight: 600;
            font-size: 0.85em;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #444;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 1em;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #08ebf3;
            outline: none;
            box-shadow: 0 0 12px rgba(8, 235, 243, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        /* Mensagens */
        .mensagem-feedback {
            text-align: center;
            margin: 15px 0;
            padding: 15px;
            border-radius: 8px;
            font-weight: 600;
        }

        .mensagem-feedback.sucesso {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: 2px solid #1e7e34;
        }

        .mensagem-feedback.erro {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 2px solid #a71e2a;
        }

        /* Footer */
        .rodape-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            padding: 20px;
            font-size: 0.85em;
            color: #888;
            margin-top: 40px;
            border-top: 1px solid #333;
        }

        .rodape-logado a {
            color: #08ebf3;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
            transition: all 0.3s ease;
        }

        .rodape-logado a:hover {
            color: #00bcd4;
        }

        @media (max-width: 768px) {
            .topo-logado {
                height: 65px;
                padding: 0 10px;
            }
            
            .logo img {
                height: 120px;
            }
            
            .nav-right {
                gap: 8px;
            }
            
            .nav-link {
                font-size: 0.85em;
                padding: 6px 10px;
            }
            
            main {
                padding-top: 80px;
            }
            
            .titulo-conta {
                font-size: 1.7em;
            }
            
            .botoes-container {
                flex-direction: column;
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
            <?php if (!$is_tecnico): ?>
                <a href="tecnico/tornar_tecnico.php" class="nav-link">Tornar Técnico</a>
            <?php else: ?>
                <a href="tecnico/area_tecnico.php" class="nav-link">Área do Técnico</a>
            <?php endif; ?>
            <a href="minha_conta.php" class="nav-link">Minha Conta</a>
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

    <!-- Conteúdo Principal -->
    <main>
        <div class="conteudo-conta">
            <h1 class="titulo-conta">Minha Conta</h1>

            <!-- Mensagens -->
            <?php if (isset($mensagem_sucesso)): ?>
                <div class="mensagem-feedback sucesso">
                    ✅ <?php echo $mensagem_sucesso; ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($mensagem_erro)): ?>
                <div class="mensagem-feedback erro">
                    ❌ <?php echo $mensagem_erro; ?>
                </div>
            <?php endif; ?>

            <div class="perfil-container">
                <!-- Coluna da Foto -->
                <div class="foto-section">
                    <?php if (!empty($usuario['foto_perfil'])): ?>
                        <img src="../<?php echo htmlspecialchars($usuario['foto_perfil']); ?>" 
                             alt="Foto de Perfil" class="foto-perfil">
                    <?php else: ?>
                        <div class="foto-placeholder">
                            👤
                        </div>
                    <?php endif; ?>
                   
                    <div class="nome-usuario"><?php echo htmlspecialchars($usuario['nome']); ?></div>
                   
                    <!-- Badge de Técnico -->
                    <?php if ($is_tecnico): ?>
                        <div class="tecnico-badge">✅ TÉCNICO VERIFICADO</div>
                    <?php endif; ?>

                    <!-- Botão Alterar Foto -->
                    <form action="upload_foto.php" method="post" enctype="multipart/form-data">
                        <input type="file" name="foto_perfil" id="foto_perfil" accept="image/*"
                               style="display: none;" onchange="this.form.submit()">
                        <label for="foto_perfil" class="botao-upload">📷 Alterar Foto</label>
                        <div class="upload-info">JPG, PNG ou GIF<br>Máx: 8MB</div>
                    </form>

                    <!-- Seção WhatsApp -->
                    <div class="whatsapp-section">
                        <div class="whatsapp-label">📱 Entre em Contato</div>
                        <div class="whatsapp-number">
                            <?php echo !empty($celular_formatado) ? htmlspecialchars($celular_formatado) : 'Não informado'; ?>
                        </div>
                        <?php if (!empty($usuario['celular']) && $usuario['celular'] != 'Não informado'): ?>
                            <a href="https://wa.me/55<?php echo preg_replace('/[^0-9]/', '', $usuario['celular']); ?>" 
                               target="_blank" class="botao-whatsapp">
                                💬 Enviar Mensagem
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Coluna das Informações -->
                <div class="info-section">
                    <!-- Dados Pessoais -->
                    <h3 class="section-title">📋 Dados Pessoais</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Idade</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['idade']); ?> anos</div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">CPF</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['cpf']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Sexo</div>
                            <div class="info-value">
                                <?php 
                                $sexo_text = [
                                    'M' => 'Masculino',
                                    'F' => 'Feminino',
                                    'Outro' => 'Outro'
                                ];
                                echo $sexo_text[$usuario['sexo']] ?? $usuario['sexo'];
                                ?>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Apelido</div>
                            <div class="info-value"><?php echo !empty($usuario['apelido']) ? htmlspecialchars($usuario['apelido']) : 'Não informado'; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">E-mail</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['email']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Data de Cadastro</div>
                            <div class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['data_cadastro'])); ?></div>
                        </div>
                    </div>

                    <!-- Dados do Técnico (se for técnico) -->
                    <?php if ($is_tecnico): ?>
                    <h3 class="section-title">🔧 Dados do Técnico</h3>
                    
                    <div class="tecnico-info">
                        <div class="info-grid">
                            <div class="info-item">
                                <div class="info-label">Experiência</div>
                                <div class="info-value"><?php echo htmlspecialchars($usuario['anos_experiencia']); ?> anos</div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Local de Trabalho</div>
                                <div class="info-value">
                                    <?php echo $usuario['possui_local_proprio'] == 'sim' ? 'Local Próprio' : 'Vai até o cliente'; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($usuario['possui_local_proprio'] == 'sim' && !empty($usuario['logradouro'])): ?>
                        <div class="info-item">
                            <div class="info-label">Endereço do Local</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($usuario['logradouro']); ?>, 
                                <?php echo htmlspecialchars($usuario['numero_local']); ?><br>
                                CEP: <?php echo htmlspecialchars($usuario['cep_local']); ?>
                                <?php if (!empty($usuario['informacao_localizacao'])): ?>
                                    <br><small><?php echo htmlspecialchars($usuario['informacao_localizacao']); ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($usuario['descricao_tecnico'])): ?>
                        <div class="info-item">
                            <div class="info-label">Sobre</div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($usuario['descricao_tecnico'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Endereço -->
                    <h3 class="section-title">🏠 Endereço</h3>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">Endereço</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['endereco']); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">Número</div>
                            <div class="info-value"><?php echo htmlspecialchars($usuario['numero']); ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Cidade</div>
                            <div class="info-value"><?php echo !empty($usuario['cidade']) ? htmlspecialchars($usuario['cidade']) : 'Não informada'; ?></div>
                        </div>

                        <div class="info-item">
                            <div class="info-label">Estado</div>
                            <div class="info-value"><?php echo !empty($usuario['estado']) ? htmlspecialchars($usuario['estado']) : 'Não informado'; ?></div>
                        </div>
                    </div>

                    <!-- Botão Editar -->
                    <div class="botoes-container">
                        <button id="btnEditar" class="botao-editar" onclick="toggleEdicao()">✏️ Editar Dados</button>
                    </div>

                    <!-- Formulário de Edição -->
                    <form id="formEditar" method="POST" style="display: none;" class="form-editar">
                        <input type="hidden" name="editar_dados" value="1">
                        
                        <h3 class="section-title">✏️ Editar Dados</h3>

                        <div class="form-grid">
                            <div class="form-group">
                                <label for="nome">Nome</label>
                                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="sexo">Sexo</label>
                                <select id="sexo" name="sexo" required>
                                    <option value="M" <?php echo $usuario['sexo'] == 'M' ? 'selected' : ''; ?>>Masculino</option>
                                    <option value="F" <?php echo $usuario['sexo'] == 'F' ? 'selected' : ''; ?>>Feminino</option>
                                    <option value="Outro" <?php echo $usuario['sexo'] == 'Outro' ? 'selected' : ''; ?>>Outro</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="apelido">Apelido</label>
                                <input type="text" id="apelido" name="apelido" value="<?php echo htmlspecialchars($usuario['apelido']); ?>">
                            </div>

                            <div class="form-group">
                                <label for="email">E-mail</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="celular">Celular/WhatsApp</label>
                                <input type="text" id="celular" name="celular" 
                                       value="<?php echo !empty($celular_formatado) ? htmlspecialchars($celular_formatado) : ''; ?>" 
                                       placeholder="(00) 00000-0000" required>
                            </div>

                            <div class="form-group">
                                <label for="endereco">Endereço</label>
                                <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($usuario['endereco']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="cep">CEP</label>
                                <input type="text" id="cep" name="cep" value="<?php echo htmlspecialchars($usuario['cep']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="numero">Número</label>
                                <input type="text" id="numero" name="numero" value="<?php echo htmlspecialchars($usuario['numero']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="cidade">Cidade</label>
                                <input type="text" id="cidade" name="cidade" value="<?php echo htmlspecialchars($usuario['cidade']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado" required>
                                    <option value="">Selecione o estado</option>
                                    <?php
                                    $estados = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];
                                    foreach ($estados as $estado_opt): ?>
                                        <option value="<?php echo $estado_opt; ?>" <?php echo $usuario['estado'] == $estado_opt ? 'selected' : ''; ?>>
                                            <?php echo $estado_opt; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="botoes-container">
                            <button type="submit" class="botao-salvar">💾 Salvar</button>
                            <button type="button" class="botao-cancelar" onclick="cancelarEdicao()">❌ Cancelar</button>
                        </div>
                    </form>
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

        function formatarCEP(cep) {
            cep = cep.replace(/\D/g, '');
            cep = cep.replace(/^(\d{5})(\d)/, '$1-$2');
            return cep;
        }

        function formatarCelular(celular) {
            celular = celular.replace(/\D/g, '');
            
            if (celular.length === 11) {
                celular = '(' + celular.substring(0, 2) + ') ' + celular.substring(2, 7) + '-' + celular.substring(7);
            } else if (celular.length === 10) {
                celular = '(' + celular.substring(0, 2) + ') ' + celular.substring(2, 6) + '-' + celular.substring(6);
            }
            
            return celular;
        }

        // Buscar endereço via API do ViaCEP
        function buscarEnderecoPorCEP() {
            const cep = document.querySelector('input[name="cep"]').value.replace(/\D/g, '');
            
            if (cep.length === 8) {
                fetch(`https://viacep.com.br/ws/${cep}/json/`)
                    .then(response => response.json())
                    .then(data => {
                        if (!data.erro) {
                            document.querySelector('input[name="endereco"]').value = data.logradouro || '';
                            document.querySelector('input[name="cidade"]').value = data.localidade || '';
                            document.querySelector('select[name="estado"]').value = data.uf || '';
                        }
                    })
                    .catch(error => {
                        console.log('Erro ao buscar CEP:', error);
                    });
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const cepInput = document.querySelector('input[name="cep"]');
            if (cepInput) {
                cepInput.addEventListener('input', function() {
                    this.value = formatarCEP(this.value);
                    if (this.value.length > 9) {
                        this.value = this.value.slice(0, 9);
                    }
                    
                    // Buscar endereço quando CEP estiver completo
                    if (this.value.length === 9) {
                        buscarEnderecoPorCEP();
                    }
                });
            }

            const celularInput = document.querySelector('input[name="celular"]');
            if (celularInput) {
                celularInput.addEventListener('input', function() {
                    this.value = formatarCelular(this.value);
                    if (this.value.length > 15) {
                        this.value = this.value.slice(0, 15);
                    }
                });
            }
        });
    </script>
</body>
</html>