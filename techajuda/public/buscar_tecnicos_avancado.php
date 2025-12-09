<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

// Coletar filtros
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
$especialidade = isset($_GET['especialidade']) ? intval($_GET['especialidade']) : 0;
$cidade = isset($_GET['cidade']) ? trim($_GET['cidade']) : '';
$estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';
$avaliacao = isset($_GET['avaliacao']) ? floatval($_GET['avaliacao']) : 0;
$experiencia = isset($_GET['experiencia']) ? intval($_GET['experiencia']) : 0;
$local_proprio = isset($_GET['local_proprio']) ? $_GET['local_proprio'] : '';
$atende_cliente = isset($_GET['atende_cliente']) ? $_GET['atende_cliente'] : '';

$conn = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];

// Buscar favoritos do usuário para marcar os cards
$favoritos_usuario = [];
$sql_favoritos = "SELECT tecnico_id FROM favoritos WHERE usuario_id = ?";
$stmt_favoritos = $conn->prepare($sql_favoritos);
$stmt_favoritos->bind_param('i', $usuario_id);
$stmt_favoritos->execute();
$result_favoritos = $stmt_favoritos->get_result();
while ($row = $result_favoritos->fetch_assoc()) {
    $favoritos_usuario[] = $row['tecnico_id'];
}
$stmt_favoritos->close();

// CONSTRUIR QUERY CORRETA USANDO A TABELA CERTA
$sql = "SELECT DISTINCT 
            t.id as tecnico_id,
            u.nome,
            u.foto_perfil,
            u.apelido,
            u.cidade,
            u.estado,
            t.anos_experiencia,
            t.avaliacao_media,
            t.descricao,
            t.possui_local_proprio,
            t.logradouro,
            t.numero as numero_local,
            t.cep as cep_local,
            t.informacao_localizacao,
            GROUP_CONCAT(DISTINCT cs.nome SEPARATOR ', ') as especialidades
         FROM tecnicos t
         INNER JOIN usuarios u ON t.usuario_id = u.id
         LEFT JOIN especialidades_tecnico et ON t.id = et.tecnico_id
         LEFT JOIN categorias_servico cs ON et.categoria_id = cs.id
         WHERE t.status = 'aprovado'";

$conditions = [];
$params = [];
$types = '';

// Busca
if (!empty($busca)) {
    $conditions[] = "(u.nome LIKE ? OR u.apelido LIKE ? OR t.descricao LIKE ?)";
    $search_term = "%$busca%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

// Especialidade
if ($especialidade > 0) {
    $conditions[] = "et.categoria_id = ?";
    $params[] = $especialidade;
    $types .= 'i';
}

// Cidade
if (!empty($cidade)) {
    $conditions[] = "u.cidade LIKE ?";
    $params[] = "%$cidade%";
    $types .= 's';
}

// Estado
if (!empty($estado)) {
    $conditions[] = "u.estado = ?";
    $params[] = $estado;
    $types .= 's';
}

// Avaliação
if ($avaliacao > 0) {
    $conditions[] = "t.avaliacao_media >= ?";
    $params[] = $avaliacao;
    $types .= 'd';
}

// Experiência
if ($experiencia > 0) {
    $conditions[] = "t.anos_experiencia >= ?";
    $params[] = $experiencia;
    $types .= 'i';
}

// Tipo de atendimento - LÓGICA CORRIGIDA
if ($local_proprio === 'sim' && $atende_cliente === 'sim') {
    // Ambos selecionados = mostra todos
    // Não adiciona condição
} elseif ($local_proprio === 'sim') {
    $conditions[] = "t.possui_local_proprio = 'sim'";
} elseif ($atende_cliente === 'sim') {
    $conditions[] = "t.possui_local_proprio = 'nao'";
}

// Adicionar condições
if (!empty($conditions)) {
    $sql .= " AND " . implode(" AND ", $conditions);
}

$sql .= " GROUP BY t.id ORDER BY t.avaliacao_media DESC, u.nome ASC";

// Executar a query
$tecnicos = [];
if ($stmt = $conn->prepare($sql)) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        
        while($row = $result->fetch_assoc()) {
            $tecnicos[] = $row;
        }
    }
    $stmt->close();
}

// Buscar nome da especialidade filtrada
$categoria_filtro = null;
if ($especialidade > 0) {
    $sql_categoria = "SELECT nome FROM categorias_servico WHERE id = ?";
    $stmt_categoria = $conn->prepare($sql_categoria);
    $stmt_categoria->bind_param('i', $especialidade);
    $stmt_categoria->execute();
    $result_categoria = $stmt_categoria->get_result();
    if ($result_categoria->num_rows > 0) {
        $categoria_filtro = $result_categoria->fetch_assoc()['nome'];
    }
    $stmt_categoria->close();
}

$conn->close();

// Calcular estatísticas dos resultados
$total_tecnicos = count($tecnicos);
$filtros_ativos = [];

if (!empty($busca)) $filtros_ativos[] = "Busca: \"$busca\"";
if ($especialidade > 0) $filtros_ativos[] = "Especialidade: " . ($categoria_filtro ?? '');
if (!empty($cidade)) $filtros_ativos[] = "Cidade: $cidade";
if (!empty($estado)) $filtros_ativos[] = "Estado: $estado";
if ($avaliacao > 0) $filtros_ativos[] = "Avaliação: {$avaliacao}+ estrelas";
if ($experiencia > 0) $filtros_ativos[] = "Experiência: {$experiencia}+ anos";
if ($local_proprio === 'sim') $filtros_ativos[] = "Local próprio";
if ($atende_cliente === 'sim') $filtros_ativos[] = "Vai até você";

// Se não encontrou técnicos, mostrar página de "Sem Técnicos"
if (empty($tecnicos)) {
    $_SESSION['mensagem_busca'] = "Nenhum técnico encontrado com os filtros aplicados.";
    header("Location: sem_tecnicos.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados da Busca - TechAjuda</title>
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
            font-size: 3em;
            margin-bottom: 10px;
            text-align: center;
            font-weight: 800;
        }

        .subtitulo-pagina {
            color: #a0a0a0;
            text-align: center;
            margin-bottom: 40px;
            font-size: 1.2em;
        }

        /* Estatísticas e Filtros Ativos */
        .info-busca {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 40px;
            border: 2px solid #333;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .contador-resultados {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 1.8em;
            font-weight: bold;
            margin-bottom: 20px;
            text-align: center;
        }

        .filtros-ativos {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            margin: 20px 0;
        }

        .filtro-badge {
            background: linear-gradient(135deg, #ff4444, #cc0000);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
            box-shadow: 0 3px 10px rgba(255, 68, 68, 0.3);
        }

        .nova-busca {
            text-align: center;
            margin-top: 25px;
        }

        .botao-nova-busca {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3);
        }

        .botao-nova-busca:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.4);
        }

        /* Grid de Técnicos */
        .tecnicos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .tecnico-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 20px;
            padding: 30px;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            position: relative;
            overflow: hidden;
        }

        .tecnico-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.1), transparent);
            transition: left 0.6s;
        }

        .tecnico-card:hover::before {
            left: 100%;
        }

        .tecnico-card:hover {
            border-color: #08ebf3;
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .badge-favorito {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff69b4;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(255, 105, 180, 0.3);
        }

        .tecnico-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .tecnico-foto {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid #08ebf3;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3);
        }

        .foto-placeholder {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #08ebf3, #007acc);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 20px;
            border: 3px solid #08ebf3;
            color: white;
            font-size: 2.2em;
            font-weight: bold;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3);
        }

        .tecnico-info {
            flex: 1;
        }

        .tecnico-nome {
            color: #08ebf3;
            font-size: 1.6em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .tecnico-apelido {
            color: #a0a0a0;
            font-style: italic;
            font-size: 1.1em;
            margin-bottom: 8px;
        }

        .tecnico-localizacao {
            color: #888;
            font-size: 1em;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tecnico-experiencia {
            color: #ff4444;
            font-weight: bold;
            margin: 20px 0;
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255, 68, 68, 0.1);
            border-radius: 8px;
            border-left: 4px solid #ff4444;
        }

        .tecnico-avaliacao {
            color: #ffd700;
            font-weight: bold;
            margin: 15px 0;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(255, 215, 0, 0.1);
            border-radius: 8px;
            border-left: 4px solid #ffd700;
        }

        .tecnico-descricao {
            color: #ccc;
            font-size: 1em;
            line-height: 1.6;
            margin-bottom: 25px;
            min-height: 80px;
        }

        .tecnico-especialidades {
            background: rgba(255, 255, 255, 0.05);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #ff4444;
        }

        .especialidades-label {
            color: #ff4444;
            font-weight: bold;
            font-size: 1em;
            margin-bottom: 8px;
        }

        .especialidades-list {
            color: #a0a0a0;
            font-size: 0.9em;
            line-height: 1.5;
        }

        .tecnico-atendimento {
            color: #888;
            font-size: 0.9em;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #333;
        }

        .info-local {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 12px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .botao-perfil {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            padding: 15px 25px;
            border-radius: 10px;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s ease;
            font-size: 1.1em;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3);
        }

        .botao-perfil:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.4);
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
            .tecnicos-grid {
                grid-template-columns: 1fr;
                gap: 25px;
            }
            
            .tecnico-header {
                flex-direction: column;
                text-align: center;
            }
            
            .tecnico-foto, .foto-placeholder {
                margin-right: 0;
                margin-bottom: 20px;
            }
            
            .filtros-ativos {
                flex-direction: column;
                align-items: center;
            }
            
            .conteudo-principal {
                padding: 20px 15px;
            }
            
            .titulo-pagina {
                font-size: 2.2em;
            }
        }

        @media (max-width: 480px) {
            .topo-logado {
                padding: 0 10px;
            }
            
            .nav-right {
                gap: 8px;
            }
            
            .nav-link {
                font-size: 0.8em;
                padding: 8px 12px;
            }
            
            .logo img {
                height: 120px;
            }
            
            .conteudo-principal {
                padding: 15px 10px;
            }
            
            .titulo-pagina {
                font-size: 1.8em;
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
            <a href="tecnico/tornar_tecnico.php" class="nav-link">Tornar Técnico</a>
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
            <a href="area_logada.php" class="voltar-link">← Voltar ao Menu Principal</a>
            
            <h1 class="titulo-pagina">🔍 Resultados da Busca</h1>
            <p class="subtitulo-pagina">Encontre o técnico perfeito para suas necessidades</p>

            <!-- Informações da Busca -->
            <div class="info-busca">
                <div class="contador-resultados">
                    📊 <?php echo $total_tecnicos; ?> técnico(s) encontrado(s)
                </div>
                
                <?php if (!empty($filtros_ativos)): ?>
                    <div style="text-align: center; margin-bottom: 20px;">
                        <strong style="color: #08ebf3;">Filtros aplicados:</strong>
                    </div>
                    <div class="filtros-ativos">
                        <?php foreach ($filtros_ativos as $filtro): ?>
                            <span class="filtro-badge"><?php echo htmlspecialchars($filtro); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div class="nova-busca">
                    <a href="area_logada.php" class="botao-nova-busca">🔄 Fazer Nova Busca</a>
                </div>
            </div>

            <div class="tecnicos-grid">
                <?php foreach ($tecnicos as $tecnico): ?>
                    <div class="tecnico-card" onclick="verPerfil(<?php echo $tecnico['tecnico_id']; ?>)">
                        <?php if (in_array($tecnico['tecnico_id'], $favoritos_usuario)): ?>
                            <div class="badge-favorito">❤️ Favorito</div>
                        <?php endif; ?>
                        
                        <div class="tecnico-header">
                            <?php if (!empty($tecnico['foto_perfil'])): ?>
                                <img src="../<?php echo htmlspecialchars($tecnico['foto_perfil']); ?>" 
                                     alt="Foto de <?php echo htmlspecialchars($tecnico['nome']); ?>" 
                                     class="tecnico-foto">
                            <?php else: ?>
                                <div class="foto-placeholder">
                                    👨‍🔧
                                </div>
                            <?php endif; ?>
                            
                            <div class="tecnico-info">
                                <div class="tecnico-nome"><?php echo htmlspecialchars($tecnico['nome']); ?></div>
                                <?php if (!empty($tecnico['apelido'])): ?>
                                    <div class="tecnico-apelido">"<?php echo htmlspecialchars($tecnico['apelido']); ?>"</div>
                                <?php endif; ?>
                                <?php if (!empty($tecnico['cidade'])): ?>
                                    <div class="tecnico-localizacao">📍 <?php echo htmlspecialchars($tecnico['cidade']); ?>, <?php echo htmlspecialchars($tecnico['estado']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="tecnico-experiencia">
                            ⭐ <?php echo $tecnico['anos_experiencia']; ?> anos de experiência
                        </div>
                        
                        <?php if ($tecnico['avaliacao_media'] > 0): ?>
                            <div class="tecnico-avaliacao">
                                ★ <?php echo number_format($tecnico['avaliacao_media'], 1); ?> de avaliação
                            </div>
                        <?php endif; ?>
                        
                        <div class="tecnico-descricao">
                            <?php 
                            $descricao = $tecnico['descricao'];
                            if (strlen($descricao) > 120) {
                                $descricao = substr($descricao, 0, 120) . '...';
                            }
                            echo htmlspecialchars($descricao);
                            ?>
                        </div>

                        <?php if (!empty($tecnico['especialidades'])): ?>
                            <div class="tecnico-especialidades">
                                <div class="especialidades-label">🛠️ Especialidades:</div>
                                <div class="especialidades-list"><?php echo htmlspecialchars($tecnico['especialidades']); ?></div>
                            </div>
                        <?php endif; ?>

                        <div class="tecnico-atendimento">
                            <?php if ($tecnico['possui_local_proprio'] === 'sim' && !empty($tecnico['logradouro'])): ?>
                                <div class="info-local">
                                    📍 Atende em: <?php echo htmlspecialchars($tecnico['logradouro']); ?>, <?php echo htmlspecialchars($tecnico['numero_local']); ?>
                                </div>
                            <?php else: ?>
                                <div class="info-local">
                                    🏠 Atendimento no local do cliente
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button class="botao-perfil" onclick="event.stopPropagation(); verPerfil(<?php echo $tecnico['tecnico_id']; ?>)">
                            👀 Ver Perfil Completo
                        </button>
                    </div>
                <?php endforeach; ?>
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
        function verPerfil(tecnicoId) {
            window.location.href = 'perfil_tecnico.php?tecnico_id=' + tecnicoId;
        }

        // Adicionar efeitos de loading
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.tecnico-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>