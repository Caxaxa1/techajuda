<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../entrar.php");
    exit();
}

if (!isset($_GET['tecnico_id']) || empty($_GET['tecnico_id'])) {
    header("Location: ../area_logada.php");
    exit();
}

$tecnico_id = intval($_GET['tecnico_id']);
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
    header("Location: ../area_logada.php");
    exit();
}

$tecnico = $result->fetch_assoc();
$stmt->close();

// Buscar avaliações do técnico (se a tabela existir)
$avaliacoes = [];
if ($conn->query("SHOW TABLES LIKE 'avaliacoes'")->num_rows > 0) {
    $sql_avaliacoes = "SELECT 
                        av.*,
                        u.nome as cliente_nome,
                        u.foto_perfil as cliente_foto
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
}

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
            max-width: 1200px;
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

        .perfil-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 40px;
            border: 2px solid #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .perfil-header {
            display: flex;
            align-items: center;
            margin-bottom: 40px;
            padding-bottom: 30px;
            border-bottom: 2px solid #333;
        }

        .foto-perfil {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 30px;
            border: 4px solid #08ebf3;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.3);
        }

        .foto-placeholder {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, #08ebf3, #007acc);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 30px;
            border: 4px solid #08ebf3;
            color: white;
            font-size: 3em;
            font-weight: bold;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.3);
        }

        .info-principal {
            flex: 1;
        }

        .nome-tecnico {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5em;
            font-weight: 800;
            margin-bottom: 10px;
        }

        .apelido-tecnico {
            color: #a0a0a0;
            font-style: italic;
            font-size: 1.3em;
            margin-bottom: 15px;
        }

        .localizacao {
            color: #888;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .estatisticas {
            display: flex;
            gap: 30px;
            margin-top: 20px;
        }

        .estatistica {
            text-align: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid #08ebf3;
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

        .secao {
            margin-bottom: 40px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            border-left: 4px solid #ff4444;
        }

        .titulo-secao {
            color: #ff4444;
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .descricao {
            color: #ccc;
            font-size: 1.1em;
            line-height: 1.7;
            margin-bottom: 25px;
        }

        .especialidades {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 15px;
        }

        .especialidade-badge {
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }

        .info-contato {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid #08ebf3;
        }

        .info-icon {
            font-size: 1.3em;
            color: #08ebf3;
        }

        .avaliacao-item {
            padding: 20px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #ffd700;
        }

        .avaliacao-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 10px;
        }

        .cliente-foto {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
        }

        .cliente-foto-placeholder {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #08ebf3, #007acc);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2em;
        }

        .estrelas {
            color: #ffd700;
            font-size: 1.1em;
        }

        .comentario {
            color: #ccc;
            font-style: italic;
            margin-top: 10px;
            line-height: 1.5;
        }

        .botao-contato {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            padding: 18px 30px;
            border-radius: 12px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1.2em;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.3);
            width: 100%;
            margin-top: 20px;
        }

        .botao-contato:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(8, 235, 243, 0.4);
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
            .perfil-header {
                flex-direction: column;
                text-align: center;
            }
            
            .foto-perfil, .foto-placeholder {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .estatisticas {
                flex-direction: column;
                gap: 15px;
            }
            
            .info-contato {
                grid-template-columns: 1fr;
            }
            
            .conteudo-principal {
                padding: 20px 15px;
            }
            
            .nome-tecnico {
                font-size: 2em;
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
            <a href="../area_logada.php" class="nav-link">🏠 Menu Principal</a>
            
            <!-- MOSTRAR APENAS SE NÃO FOR TÉCNICO -->
            <?php if (!$is_tecnico): ?>
                <a href="tornar_tecnico.php" class="nav-link">🔧 Tornar Técnico</a>
            <?php endif; ?>
            
            <a href="../minha_conta.php" class="nav-link">👤 Minha Conta</a>
            
            <?php if ($_SESSION['usuario_id'] == 1): ?>
                <a href="../admin/dashboard_admin.php" class="nav-link">⚙️ Área Admin</a>
            <?php endif; ?>
            
            <a href="../logout.php" class="nav-link">🚪 Sair</a>
            
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
            <a href="javascript:history.back()" class="voltar-link">← Voltar aos Resultados</a>
            
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
                        <h1 class="nome-tecnico"><?php echo htmlspecialchars($tecnico['nome']); ?></h1>
                        <?php if (!empty($tecnico['apelido'])): ?>
                            <div class="apelido-tecnico">"<?php echo htmlspecialchars($tecnico['apelido']); ?>"</div>
                        <?php endif; ?>
                        
                        <div class="localizacao">
                            📍 <?php echo htmlspecialchars($tecnico['cidade']); ?>, <?php echo htmlspecialchars($tecnico['estado']); ?>
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
                                <div class="estatistica-label">Avaliações</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sobre o Técnico -->
                <div class="secao">
                    <h2 class="titulo-secao">ℹ️ Sobre</h2>
                    <div class="descricao">
                        <?php echo nl2br(htmlspecialchars($tecnico['descricao'])); ?>
                    </div>
                </div>

                <!-- Especialidades -->
                <?php if (!empty($tecnico['especialidades'])): ?>
                <div class="secao">
                    <h2 class="titulo-secao">🛠️ Especialidades</h2>
                    <div class="especialidades">
                        <?php 
                        $especialidades_array = explode(', ', $tecnico['especialidades']);
                        foreach ($especialidades_array as $especialidade): 
                        ?>
                            <span class="especialidade-badge"><?php echo htmlspecialchars(trim($especialidade)); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Informações de Contato -->
                <div class="secao">
                    <h2 class="titulo-secao">📞 Contato</h2>
                    <div class="info-contato">
                        <?php if (!empty($tecnico['celular'])): ?>
                        <div class="info-item">
                            <span class="info-icon">📱</span>
                            <div>
                                <strong>Celular:</strong><br>
                                <?php echo htmlspecialchars($tecnico['celular']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($tecnico['email'])): ?>
                        <div class="info-item">
                            <span class="info-icon">📧</span>
                            <div>
                                <strong>E-mail:</strong><br>
                                <?php echo htmlspecialchars($tecnico['email']); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Local de Atendimento -->
                <div class="secao">
                    <h2 class="titulo-secao">📍 Local de Atendimento</h2>
                    <?php if ($tecnico['possui_local_proprio'] === 'sim' && !empty($tecnico['logradouro'])): ?>
                        <div class="info-item">
                            <span class="info-icon">🏢</span>
                            <div>
                                <strong>Atendimento no local do técnico:</strong><br>
                                <?php echo htmlspecialchars($tecnico['logradouro']); ?>, 
                                <?php echo htmlspecialchars($tecnico['numero_local']); ?><br>
                                <?php if (!empty($tecnico['informacao_localizacao'])): ?>
                                    <em><?php echo htmlspecialchars($tecnico['informacao_localizacao']); ?></em>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="info-item">
                            <span class="info-icon">🚗</span>
                            <div>
                                <strong>Atendimento no local do cliente</strong><br>
                                O técnico se desloca até você para realizar o serviço.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Avaliações -->
                <?php if (!empty($avaliacoes)): ?>
                <div class="secao">
                    <h2 class="titulo-secao">⭐ Avaliações (<?php echo count($avaliacoes); ?>)</h2>
                    <?php foreach ($avaliacoes as $avaliacao): ?>
                        <div class="avaliacao-item">
                            <div class="avaliacao-header">
                                <?php if (!empty($avaliacao['cliente_foto'])): ?>
                                    <img src="../<?php echo htmlspecialchars($avaliacao['cliente_foto']); ?>" 
                                         alt="Foto de <?php echo htmlspecialchars($avaliacao['cliente_nome']); ?>" 
                                         class="cliente-foto">
                                <?php else: ?>
                                    <div class="cliente-foto-placeholder">
                                        👤
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <strong><?php echo htmlspecialchars($avaliacao['cliente_nome']); ?></strong>
                                    <?php if (isset($avaliacao['nota'])): ?>
                                        <div class="estrelas">
                                            <?php echo str_repeat('★', $avaliacao['nota']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($avaliacao['comentario'])): ?>
                                <div class="comentario">
                                    "<?php echo htmlspecialchars($avaliacao['comentario']); ?>"
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Botão de Contato -->
                <button class="botao-contato" onclick="entrarEmContato()">
                    📞 Entrar em Contato
                </button>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="rodape-logado">
        <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
        <p>
            <a href="../suporte2.php">🎯 Suporte</a> |
            <a href="../suporte2.php#termos">📄 Termos de Uso</a> |
            <a href="../suporte2.php#politica">🔒 Política de Privacidade</a>
        </p>
    </footer>

    <script>
        function entrarEmContato() {
            alert('📞 Funcionalidade de contato em desenvolvimento!\n\nEm breve você poderá entrar em contato diretamente com o técnico.');
        }

        // Efeitos visuais
        document.addEventListener('DOMContentLoaded', function() {
            const sections = document.querySelectorAll('.secao');
            sections.forEach(section => {
                section.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 30px rgba(0, 0, 0, 0.4)';
                });
                
                section.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>