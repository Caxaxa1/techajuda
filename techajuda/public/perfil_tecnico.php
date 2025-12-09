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

$tecnico_id = intval($_GET['tecnico_id']);
$usuario_id = $_SESSION['usuario_id'];
$conn = getDBConnection();

// Verificar se o usuário atual é técnico
$is_tecnico = $_SESSION['is_tecnico'] ?? false;

// Buscar dados completos do técnico
$sql = "SELECT 
            t.id as tecnico_id,
            u.nome,
            u.foto_perfil,
            u.apelido,
            u.cidade,
            u.estado,
            u.celular,
            u.email,
            t.anos_experiencia,
            t.avaliacao_media,
            t.descricao,
            t.possui_local_proprio,
            t.logradouro,
            t.numero as numero_local,
            t.cep as cep_local,
            t.informacao_localizacao,
            GROUP_CONCAT(DISTINCT cs.nome SEPARATOR ', ') as especialidades,
            (SELECT COUNT(*) FROM avaliacoes WHERE tecnico_id = t.id) as total_avaliacoes
         FROM tecnicos t
         INNER JOIN usuarios u ON t.usuario_id = u.id
         LEFT JOIN especialidades_tecnico et ON t.id = et.tecnico_id
         LEFT JOIN categorias_servico cs ON et.categoria_id = cs.id
         WHERE t.id = ? AND t.status = 'aprovado'
         GROUP BY t.id";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $tecnico_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: area_logada.php");
    exit();
}

$tecnico = $result->fetch_assoc();
$stmt->close();

// Verificar se usuário já favoritou este técnico
$is_favorito = false;
$sql_favorito = "SELECT id FROM favoritos WHERE usuario_id = ? AND tecnico_id = ?";
$stmt_favorito = $conn->prepare($sql_favorito);
$stmt_favorito->bind_param('ii', $usuario_id, $tecnico_id);
$stmt_favorito->execute();
$result_favorito = $stmt_favorito->get_result();
if ($result_favorito->num_rows > 0) {
    $is_favorito = true;
}
$stmt_favorito->close();

// Verificar se usuário já avaliou este técnico
$minha_avaliacao = null;
$sql_minha_avaliacao = "SELECT nota, comentario FROM avaliacoes WHERE tecnico_id = ? AND cliente_id = ?";
$stmt_avaliacao = $conn->prepare($sql_minha_avaliacao);
$stmt_avaliacao->bind_param('ii', $tecnico_id, $usuario_id);
$stmt_avaliacao->execute();
$result_avaliacao = $stmt_avaliacao->get_result();
if ($result_avaliacao->num_rows > 0) {
    $minha_avaliacao = $result_avaliacao->fetch_assoc();
}
$stmt_avaliacao->close();

// Buscar últimas avaliações (10 mais recentes)
$avaliacoes = [];
$sql_avaliacoes = "SELECT 
                    av.nota,
                    av.comentario,
                    av.data_avaliacao,
                    u.nome as cliente_nome
                   FROM avaliacoes av
                   INNER JOIN usuarios u ON av.cliente_id = u.id
                   WHERE av.tecnico_id = ?
                   ORDER BY av.data_avaliacao DESC
                   LIMIT 10";

$stmt_avaliacoes = $conn->prepare($sql_avaliacoes);
$stmt_avaliacoes->bind_param('i', $tecnico_id);
$stmt_avaliacoes->execute();
$avaliacoes = $stmt_avaliacoes->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_avaliacoes->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil do Técnico - TechAjuda</title>
    <link rel="stylesheet" href="../visualscript/css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #0a0a0a !important;
            color: #e0e0e0;
            line-height: 1.6;
            min-height: 100vh;
        }

        .topo-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 0 20px;
            height: 90px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.3);
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
            height: 200px;
            width: auto;
            margin-left: 20px;
            filter: brightness(0) invert(1);
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
            border-radius: 6px;
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
            padding-top: 120px;
            padding-bottom: 60px;
        }

        .conteudo-principal {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .voltar-link {
            display: inline-block;
            margin-bottom: 30px;
            padding: 12px 24px;
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3);
        }

        .voltar-link:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.4);
        }

        .perfil-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 2px solid #333;
            position: relative;
            overflow: hidden;
        }

        .perfil-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.1), transparent);
            transition: left 0.6s;
        }

        .perfil-container:hover::before {
            left: 100%;
        }

        .perfil-header {
            display: flex;
            align-items: flex-start;
            gap: 30px;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #333;
            position: relative;
            z-index: 2;
        }

        .foto-perfil {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #08ebf3;
            box-shadow: 0 8px 25px rgba(8, 235, 243, 0.3);
        }

        .foto-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #666, #888);
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid #08ebf3;
            color: white;
            font-size: 3em;
            box-shadow: 0 8px 25px rgba(8, 235, 243, 0.3);
        }

        .info-principal {
            flex: 1;
        }

        .info-principal h1 {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 800;
        }

        .info-principal .apelido {
            color: #a0a0a0;
            font-style: italic;
            font-size: 1.3em;
            margin-bottom: 15px;
        }

        .info-principal .localizacao {
            color: #888;
            font-size: 1.1em;
            margin-bottom: 20px;
        }

        .acoes-header {
            display: flex;
            gap: 20px;
            align-items: center;
        }

        .botao-agenda {
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
            border: 2px solid #08ebf3;
            padding: 15px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            width: 80px;
            height: 80px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .botao-agenda:hover {
            background: #08ebf3;
            color: #001a33;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(8, 235, 243, 0.4);
        }

        .emoji-agenda {
            font-size: 1.8em;
            margin-bottom: 5px;
        }

        .texto-agenda {
            font-size: 0.8em;
        }

        .botao-favorito {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid #ff4444;
            color: #ff4444;
            padding: 10px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
        }

        .botao-favorito.ativo {
            background: #ff4444;
            color: white;
        }

        .botao-favorito:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(255, 68, 68, 0.4);
        }

        .estatisticas {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .estatistica {
            text-align: center;
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            border-left: 4px solid #08ebf3;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .estatistica-numero {
            color: #08ebf3;
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .estatistica-label {
            color: #a0a0a0;
            font-size: 0.9em;
        }

        /* Grid de Informações */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
            position: relative;
            z-index: 2;
        }

        .info-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 25px;
            border-radius: 12px;
            border-left: 4px solid #08ebf3;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .info-card h3 {
            color: #08ebf3;
            font-size: 1.3em;
            margin-bottom: 15px;
        }

        .descricao {
            color: #ccc;
            line-height: 1.6;
        }

        .especialidades-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .especialidade {
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(255, 68, 68, 0.3);
        }

        .contato-info {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .contato-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }

        .contato-item strong {
            color: #08ebf3;
            min-width: 80px;
        }

        /* Botão WhatsApp */
        .botao-whatsapp {
            background: linear-gradient(135deg, #25D366, #128C7E);
            color: white;
            border: none;
            padding: 18px 25px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin: 30px 0;
            transition: all 0.3s;
            font-size: 1.1em;
            box-shadow: 0 6px 20px rgba(37, 211, 102, 0.3);
            position: relative;
            z-index: 2;
        }

        .botao-whatsapp:hover {
            background: linear-gradient(135deg, #128C7E, #075E54);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.4);
        }

        /* Avaliações */
        .avaliacoes-section {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #333;
            position: relative;
            z-index: 2;
        }

        .avaliacoes-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .avaliacoes-header h3 {
            color: #08ebf3;
            font-size: 1.5em;
        }

        .botao-avaliar {
            background: linear-gradient(135deg, #ffd700, #ffa500);
            color: #001a66;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 215, 0, 0.3);
        }

        .botao-avaliar:hover {
            background: linear-gradient(135deg, #ffa500, #ff8c00);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 215, 0, 0.4);
        }

        .botao-remover-avaliacao {
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 68, 68, 0.3);
        }

        .botao-remover-avaliacao:hover {
            background: linear-gradient(135deg, #cc0000, #990000);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 68, 68, 0.4);
        }

        .avaliacoes-lista {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .avaliacao-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #ffd700;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
        }

        .minha-avaliacao {
            background: rgba(8, 235, 243, 0.1);
            border-left: 4px solid #08ebf3;
        }

        .avaliacao-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .avaliacao-cliente {
            color: #08ebf3;
            font-weight: bold;
        }

        .avaliacao-data {
            color: #888;
            font-size: 0.9em;
        }

        .estrelas-exibicao {
            color: #ffd700;
            font-size: 1.2em;
            margin-bottom: 10px;
        }

        .avaliacao-comentario {
            color: #ccc;
            font-style: italic;
            line-height: 1.5;
        }

        .sem-avaliacoes {
            color: #888;
            text-align: center;
            padding: 40px 0;
            font-style: italic;
            font-size: 1.1em;
        }

        /* Modal de Avaliação */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal-conteudo {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 20px;
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
            font-size: 1.4em;
        }

        .fechar-modal {
            background: none;
            border: none;
            color: #e0e0e0;
            font-size: 1.5em;
            cursor: pointer;
        }

        .estrelas-avaliacao {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin: 25px 0;
            font-size: 2.5em;
        }

        .estrela {
            cursor: pointer;
            color: #666;
            transition: color 0.3s ease;
        }

        .estrela.ativa {
            color: #ffd700;
        }

        .estrela:hover {
            color: #ffd700;
        }

        .nota-selecionada {
            text-align: center;
            color: #08ebf3;
            font-weight: bold;
            margin: 15px 0;
            font-size: 1.1em;
        }

        .comentario-avaliacao {
            width: 100%;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 12px;
            color: #e0e0e0;
            resize: vertical;
            margin-bottom: 20px;
            font-family: inherit;
        }

        .botoes-avaliacao {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }

        .botao-cancelar {
            background: #666;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .botao-cancelar:hover {
            background: #555;
        }

        .botao-enviar {
            background: #08ebf3;
            color: #001a66;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
        }

        .botao-enviar:hover {
            background: #007acc;
            color: white;
        }

        .botao-enviar:disabled {
            background: #666;
            color: #999;
            cursor: not-allowed;
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .perfil-header {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .acoes-header {
                justify-content: center;
                width: 100%;
            }
            
            .estatisticas {
                flex-direction: column;
                gap: 15px;
            }
            
            .topo-logado {
                padding: 0 10px;
            }
            
            .nav-right {
                gap: 10px;
            }
            
            .nav-link {
                font-size: 1em;
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
                        <span style="color: white; font-size: 18px;">👤</span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main>
        <div class="conteudo-principal">
            <a href="javascript:history.back()" class="voltar-link">← Voltar</a>

            <div class="perfil-container">
                <!-- Cabeçalho do Perfil -->
                <div class="perfil-header">
                    <?php if (!empty($tecnico['foto_perfil'])): ?>
                        <img src="../<?php echo htmlspecialchars($tecnico['foto_perfil']); ?>" 
                             alt="Foto de <?php echo htmlspecialchars($tecnico['nome']); ?>" 
                             class="foto-perfil">
                    <?php else: ?>
                        <div class="foto-placeholder">
                            👨‍🔧
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-principal">
                        <h1><?php echo htmlspecialchars($tecnico['nome']); ?></h1>
                        <?php if (!empty($tecnico['apelido'])): ?>
                            <div class="apelido">"<?php echo htmlspecialchars($tecnico['apelido']); ?>"</div>
                        <?php endif; ?>
                        <div class="localizacao">
                            <?php echo htmlspecialchars($tecnico['cidade']); ?>, <?php echo htmlspecialchars($tecnico['estado']); ?>
                        </div>
                        
                        <div class="estatisticas">
                            <div class="estatistica">
                                <div class="estatistica-numero"><?php echo $tecnico['anos_experiencia']; ?>+</div>
                                <div class="estatistica-label">Anos de Experiência</div>
                            </div>
                            <div class="estatistica">
                                <div class="estatistica-numero"><?php echo number_format($tecnico['avaliacao_media'], 1); ?></div>
                                <div class="estatistica-label">Avaliação Média</div>
                            </div>
                            <div class="estatistica">
                                <div class="estatistica-numero"><?php echo $tecnico['total_avaliacoes']; ?></div>
                                <div class="estatistica-label">Total de Avaliações</div>
                            </div>
                        </div>
                    </div>

                    <div class="acoes-header">
                        <button class="botao-agenda" onclick="verAgendaTecnico()">
                            <div class="emoji-agenda">📅</div>
                            <div class="texto-agenda">Agenda</div>
                        </button>
                        <button class="botao-favorito <?php echo $is_favorito ? 'ativo' : ''; ?>" 
                                onclick="toggleFavorito()">
                            <?php echo $is_favorito ? '❤️' : '🤍'; ?>
                        </button>
                    </div>
                </div>

                <!-- Resto do código permanece igual... -->
                <!-- Grid de Informações -->
                <div class="info-grid">
                    <!-- Sobre -->
                    <div class="info-card">
                        <h3>Sobre</h3>
                        <div class="descricao">
                            <?php echo nl2br(htmlspecialchars($tecnico['descricao'])); ?>
                        </div>
                    </div>

                    <!-- Especialidades -->
                    <div class="info-card">
                        <h3>Especialidades</h3>
                        <div class="especialidades-lista">
                            <?php if (!empty($tecnico['especialidades'])): ?>
                                <?php 
                                $especialidades_array = explode(', ', $tecnico['especialidades']);
                                foreach ($especialidades_array as $especialidade): 
                                ?>
                                    <span class="especialidade"><?php echo htmlspecialchars(trim($especialidade)); ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span style="color: #888;">Nenhuma especialidade cadastrada</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Contato -->
                    <div class="info-card">
                        <h3>Contato</h3>
                        <div class="contato-info">
                            <?php if (!empty($tecnico['celular'])): ?>
                                <div class="contato-item">
                                    <strong>Celular:</strong>
                                    <span><?php echo htmlspecialchars($tecnico['celular']); ?></span>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($tecnico['email'])): ?>
                                <div class="contato-item">
                                    <strong>E-mail:</strong>
                                    <span><?php echo htmlspecialchars($tecnico['email']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Local de Atendimento -->
                    <div class="info-card">
                        <h3>Local de Atendimento</h3>
                        <div class="descricao">
                            <?php if ($tecnico['possui_local_proprio'] === 'sim' && !empty($tecnico['logradouro'])): ?>
                                Atende em: <?php echo htmlspecialchars($tecnico['logradouro']); ?>, 
                                <?php echo htmlspecialchars($tecnico['numero_local']); ?>
                                <?php if (!empty($tecnico['informacao_localizacao'])): ?>
                                    <br><em><?php echo htmlspecialchars($tecnico['informacao_localizacao']); ?></em>
                                <?php endif; ?>
                            <?php else: ?>
                                Atendimento no local do cliente
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Botão WhatsApp -->
                <?php if (!empty($tecnico['celular'])): ?>
                    <button class="botao-whatsapp" onclick="abrirWhatsApp('<?php echo htmlspecialchars($tecnico['celular']); ?>')">
                        📞 Entrar em Contato via WhatsApp
                    </button>
                <?php endif; ?>

                <!-- Avaliações -->
                <div class="avaliacoes-section">
                    <div class="avaliacoes-header">
                        <h3>Avaliações (<?php echo $tecnico['total_avaliacoes']; ?>)</h3>
                        <?php if (!$minha_avaliacao): ?>
                            <button class="botao-avaliar" onclick="abrirModalAvaliacao()">⭐ Avaliar</button>
                        <?php else: ?>
                            <button class="botao-remover-avaliacao" onclick="removerAvaliacao()">🗑️ Remover Minha Avaliação</button>
                        <?php endif; ?>
                    </div>

                    <div class="avaliacoes-lista">
                        <?php if (!empty($avaliacoes)): ?>
                            <?php foreach ($avaliacoes as $avaliacao): ?>
                                <div class="avaliacao-item <?php echo ($avaliacao['cliente_nome'] == $_SESSION['usuario_nome']) ? 'minha-avaliacao' : ''; ?>">
                                    <div class="avaliacao-header">
                                        <div class="avaliacao-cliente"><?php echo htmlspecialchars($avaliacao['cliente_nome']); ?></div>
                                        <div class="avaliacao-data"><?php echo date('d/m/Y', strtotime($avaliacao['data_avaliacao'])); ?></div>
                                    </div>
                                    <div class="estrelas-exibicao">
                                        <?php echo str_repeat('★', intval($avaliacao['nota'])); ?>
                                        <span style="color: #888; margin-left: 10px;">(<?php echo number_format($avaliacao['nota'], 1); ?>)</span>
                                    </div>
                                    <?php if (!empty($avaliacao['comentario'])): ?>
                                        <div class="avaliacao-comentario">
                                            "<?php echo htmlspecialchars($avaliacao['comentario']); ?>"
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="sem-avaliacoes">Nenhuma avaliação ainda</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Avaliação -->
    <div id="modalAvaliacao" class="modal">
        <div class="modal-conteudo">
            <div class="modal-header">
                <h3>Avaliar <?php echo htmlspecialchars($tecnico['nome']); ?></h3>
                <button class="fechar-modal" onclick="fecharModalAvaliacao()">×</button>
            </div>
            <form id="formAvaliacao" onsubmit="enviarAvaliacao(event)">
                <input type="hidden" name="tecnico_id" value="<?php echo $tecnico_id; ?>">
                
                <div style="text-align: center; margin-bottom: 15px;">
                    <label style="color: #08ebf3; font-weight: bold;">Selecione sua nota:</label>
                </div>
                
                <div class="estrelas-avaliacao" id="estrelasAvaliacao">
                    <span class="estrela" data-nota="1">★</span>
                    <span class="estrela" data-nota="2">★</span>
                    <span class="estrela" data-nota="3">★</span>
                    <span class="estrela" data-nota="4">★</span>
                    <span class="estrela" data-nota="5">★</span>
                </div>
                
                <div class="nota-selecionada" id="notaSelecionada">
                    Nota selecionada: <span id="notaValor">0</span>/5
                </div>
                
                <input type="hidden" name="nota" id="notaAvaliacao" value="0">
                
                <textarea name="comentario" class="comentario-avaliacao" placeholder="Deixe um comentário (opcional)..."></textarea>
                
                <div class="botoes-avaliacao">
                    <button type="button" class="botao-cancelar" onclick="fecharModalAvaliacao()">Cancelar</button>
                    <button type="submit" class="botao-enviar" id="botaoEnviarAvaliacao" disabled>Enviar Avaliação</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirWhatsApp(celular) {
            const numero = celular.replace(/\D/g, '');
            const mensagem = 'Olá! Gostaria de mais informações sobre seus serviços.';
            const url = `https://wa.me/55${numero}?text=${encodeURIComponent(mensagem)}`;
            window.open(url, '_blank');
        }

        function verAgendaTecnico() {
            window.location.href = 'agenda_tecnico.php?tecnico_id=<?php echo $tecnico_id; ?>';
        }

        function toggleFavorito() {
            const botao = document.querySelector('.botao-favorito');
            const isAtivo = botao.classList.contains('ativo');
            
            fetch('favoritar_tecnico.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `tecnico_id=<?php echo $tecnico_id; ?>&acao=${isAtivo ? 'remover' : 'adicionar'}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (isAtivo) {
                        botao.classList.remove('ativo');
                        botao.innerHTML = '🤍';
                    } else {
                        botao.classList.add('ativo');
                        botao.innerHTML = '❤️';
                    }
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao favoritar técnico');
            });
        }

        function abrirModalAvaliacao() {
            document.getElementById('modalAvaliacao').style.display = 'flex';
        }

        function fecharModalAvaliacao() {
            document.getElementById('modalAvaliacao').style.display = 'none';
            resetarEstrelas();
        }

        function removerAvaliacao() {
            if (confirm('Tem certeza que deseja remover sua avaliação?')) {
                fetch('remover_avaliacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `tecnico_id=<?php echo $tecnico_id; ?>`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Erro: ' + data.message);
                    }
                });
            }
        }

        function enviarAvaliacao(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            fetch('avaliar_tecnico.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Avaliação enviada com sucesso!');
                    location.reload();
                } else {
                    alert('Erro: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao enviar avaliação');
            });
        }

        // Sistema de Estrelas
        function resetarEstrelas() {
            document.querySelectorAll('.estrela').forEach(estrela => {
                estrela.classList.remove('ativa');
            });
            document.getElementById('notaAvaliacao').value = '0';
            document.getElementById('notaValor').textContent = '0';
            document.getElementById('botaoEnviarAvaliacao').disabled = true;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const estrelas = document.querySelectorAll('.estrela');
            const notaInput = document.getElementById('notaAvaliacao');
            const notaValor = document.getElementById('notaValor');
            const botaoEnviar = document.getElementById('botaoEnviarAvaliacao');
            
            estrelas.forEach(estrela => {
                estrela.addEventListener('click', function() {
                    const nota = this.getAttribute('data-nota');
                    notaInput.value = nota;
                    notaValor.textContent = nota;
                    botaoEnviar.disabled = false;
                    
                    // Atualizar visual das estrelas
                    estrelas.forEach((e, index) => {
                        if (index < nota) {
                            e.classList.add('ativa');
                        } else {
                            e.classList.remove('ativa');
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>