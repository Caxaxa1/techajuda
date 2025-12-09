<?php
session_start();
require_once "../../src/config.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../entrar.php");
    exit();
}

// Verificar status do técnico
$conn = getDBConnection();
$usuario_id = $_SESSION['usuario_id'];
$sql_status = "SELECT is_tecnico, pedido_tecnico_pendente FROM usuarios WHERE id = '$usuario_id'";
$result_status = $conn->query($sql_status);
$status = $result_status->fetch_assoc();

// Redirecionamentos baseados no status
if ($status['is_tecnico']) {
    header("Location: minha_conta_tecnico.php");
    exit();
} elseif ($status['pedido_tecnico_pendente']) {
    // Mostrar página de "em análise"
    $pagina_analise = true;
} else {
    $pagina_analise = false;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pagina_analise ? 'Cadastro em Análise' : 'Tornar-se Técnico'; ?> - TechAjuda</title>
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
            align-items: center;
        }

        .info-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border-radius: 15px;
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            border: 1px solid #333;
        }

        .logo-section {
            margin-bottom: 30px;
        }

        .logo-com-aureola {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: radial-gradient(circle, #08ebf3 0%, transparent 70%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            padding: 8px;
        }

        .logo-com-aureola img {
            width: 84px;
            height: 84px;
            border-radius: 50%;
            object-fit: cover;
        }

        .info-title {
            color: #08ebf3;
            font-size: 1.8em;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .info-text {
            color: #b0b0b0;
            line-height: 1.6;
            margin-bottom: 25px;
            font-size: 1em;
        }

        .status-analise {
            background: rgba(255, 255, 255, 0.05);
            color: #e0e0e0;
            padding: 25px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #08ebf3;
            text-align: center;
        }

        .status-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #08ebf3;
        }

        .status-analise .info-title {
            color: #08ebf3;
            margin-bottom: 15px;
        }

        .status-analise .info-text {
            color: #b0b0b0;
            margin-bottom: 15px;
        }

        .benefits-list {
            text-align: left;
            margin: 25px 0;
            padding: 0 20px;
        }

        .benefits-list li {
            margin-bottom: 12px;
            color: #e0e0e0;
            display: flex;
            align-items: center;
        }

        .benefits-list li::before {
            content: "✓";
            color: #08ebf3;
            font-weight: bold;
            margin-right: 10px;
            font-size: 1.2em;
        }

        .start-btn {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            border: none;
            padding: 15px 40px;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 4px 12px rgba(8, 235, 243, 0.3);
        }

        .start-btn:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(8, 235, 243, 0.4);
        }

        .back-link {
            display: inline-block;
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

        /* Header Menor - Consistente */
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

            .info-container {
                padding: 30px 20px;
            }

            .info-title {
                font-size: 1.6em;
            }

            .nav-right {
                gap: 10px;
            }

            .nav-link {
                font-size: 0.85em;
                padding: 6px 10px;
            }

            .logo img {
                height: 120px;
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
            
            .info-container {
                padding: 25px 15px;
            }
            
            .account-circle {
                width: 30px;
                height: 30px;
            }
            
            .logo-com-aureola {
                width: 80px;
                height: 80px;
            }
            
            .logo-com-aureola img {
                width: 64px;
                height: 64px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Menor - Consistente -->
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
        <div class="info-container">
            <div class="logo-section">
                <div class="logo-com-aureola">
                    <?php if (!empty($_SESSION['usuario_foto'])): ?>
                        <img src="../../<?php echo $_SESSION['usuario_foto']; ?>" alt="Foto Perfil">
                    <?php else: ?>
                        <div style="width: 84px; height: 84px; background: #444; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #888; font-size: 1.8em;">
                            👤
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($pagina_analise): ?>
                <!-- PÁGINA DE "EM ANÁLISE" - CLEAN -->
                <div class="status-analise">
                    <div class="status-icon">⏳</div>
                    <h1 class="info-title">Cadastro em Análise</h1>
                    <p class="info-text">
                        Seu cadastro como técnico está sendo analisado pela nossa equipe.
                        Em breve você receberá uma resposta por e-mail.
                    </p>
                    <p style="color: #08ebf3; font-weight: 500; margin-top: 15px; font-size: 0.9em;">
                        Aguarde o contato da nossa equipe
                    </p>
                </div>
                
                <a href="../area_logada.php" class="back-link">← Voltar ao Menu Principal</a>

            <?php else: ?>
                <!-- PÁGINA NORMAL DE CADASTRO -->
                <h1 class="info-title">Torne-se um Técnico</h1>
                
                <p class="info-text">
                    Junte-se à nossa rede de profissionais e comece a oferecer seus serviços técnicos para milhares de clientes.
                </p>

                <ul class="benefits-list">
                    <li>Amplie sua base de clientes</li>
                    <li>Trabalhe no seu próprio ritmo</li>
                    <li>Receba pagamentos seguros</li>
                    <li>Suporte completo da plataforma</li>
                    <li>Avaliações e reputação</li>
                </ul>

                <a href="cadastrar_tecnico.php" class="start-btn">
                    Começar Cadastro
                </a>

                <br>
                <a href="../area_logada.php" class="back-link">← Voltar ao Menu Principal</a>
            <?php endif; ?>
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
</body>
</html>