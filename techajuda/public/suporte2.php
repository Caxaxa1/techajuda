<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suporte - TechAjuda</title>
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

        .conteudo-pagina {
            padding: 120px 40px 80px;
            color: #e0e0e0;
            max-width: 1000px;
            margin: 0 auto;
            text-align: left;
        }

        .conteudo-pagina h1 {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 3em;
            margin-bottom: 40px;
            text-align: center;
            font-weight: 800;
        }

        .conteudo-pagina h2 {
            color: #08ebf3;
            font-size: 1.8em;
            margin: 40px 0 20px;
            border-left: 4px solid #08ebf3;
            padding-left: 15px;
        }

        .conteudo-pagina p {
            margin-bottom: 25px;
            line-height: 1.7;
            font-size: 1.1em;
        }

        .conteudo-pagina a {
            color: #08ebf3;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 5px 10px;
            border-radius: 5px;
        }

        .conteudo-pagina a:hover {
            color: #00bcd4;
            background: rgba(8, 235, 243, 0.1);
        }

        .info-box {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            border: 2px solid #333;
            border-radius: 15px;
            padding: 25px;
            margin: 25px 0;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
        }

        .info-box:hover {
            border-color: #08ebf3;
            box-shadow: 0 12px 35px rgba(8, 235, 243, 0.2);
            transform: translateY(-5px);
        }

        .contact-item {
            display: flex;
            align-items: center;
            margin: 15px 0;
            padding: 15px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border-left: 4px solid #08ebf3;
        }

        .contact-item i {
            font-size: 1.5em;
            margin-right: 15px;
            color: #08ebf3;
        }

        .rodape-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            padding: 25px 20px;
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

        .rodape-logado p {
            margin: 10px 0;
        }

        @media (max-width: 768px) {
            .topo-logado {
                padding: 0 15px;
                height: 70px;
            }
            
            .logo img {
                height: 120px;
            }
            
            .nav-right {
                gap: 8px;
            }
            
            .nav-link {
                font-size: 0.9em;
                padding: 8px 12px;
            }
            
            .conteudo-pagina {
                padding: 100px 25px 60px;
            }
            
            .conteudo-pagina h1 {
                font-size: 2.2em;
            }
            
            .conteudo-pagina h2 {
                font-size: 1.5em;
            }
        }

        @media (max-width: 480px) {
            .topo-logado {
                padding: 0 10px;
            }
            
            .nav-right {
                gap: 5px;
            }
            
            .nav-link {
                font-size: 0.8em;
                padding: 6px 8px;
            }
            
            .logo img {
                height: 100px;
            }
            
            .conteudo-pagina {
                padding: 90px 20px 50px;
            }
            
            .conteudo-pagina h1 {
                font-size: 1.8em;
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
            <a href="tecnico/tornar_tecnico.php" class="nav-link">Tornar Técnico</a>
            <a href="minha_conta.php" class="nav-link">Minha Conta</a>
            <a href="logout.php" class="nav-link">Sair</a>
        </div>
    </header>

    <main class="conteudo-pagina">
        <h1>🎯 Suporte TechAjuda</h1>
        
        <div class="info-box">
            <p>Precisa de ajuda? Estamos aqui para você! Entre em contato conosco através dos seguintes canais:</p>
        </div>
        
        <h2>📧 E-mail de Suporte</h2>
        <div class="contact-item">
            <span style="font-size: 1.5em; margin-right: 15px;">📨</span>
            <div>
                <p><strong>Envie suas dúvidas para:</strong></p>
                <a href="mailto:suporte@techajuda.com">suporte@techajuda.com</a>
            </div>
        </div>
        
        <h2>🕒 Horário de Atendimento</h2>
        <div class="info-box">
            <p><strong>Segunda a sexta-feira:</strong> 9h às 18h</p>
            <p><strong>Sábados:</strong> 9h às 12h</p>
            <p style="color: #08ebf3; font-weight: 600;">⏰ Respondemos em até 24h úteis</p>
        </div>
        
        <h2>❓ Perguntas Frequentes</h2>
        <div class="info-box">
            <p>Em breve disponibilizaremos uma seção completa de FAQ para ajudar com as dúvidas mais comuns.</p>
            <p style="margin-top: 15px; color: #08ebf3;">✨ Estamos trabalhando para melhorar sua experiência!</p>
        </div>

        <h2 id="termos">📄 Termos de Uso</h2>
        <div class="info-box">
            <p>Nossos Termos de Uso estabelecem as regras e diretrizes para o uso da plataforma TechAjuda.</p>
            <p><strong>Principais pontos:</strong></p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Uso responsável da plataforma</li>
                <li>Respeito à propriedade intelectual</li>
                <li>Conduta apropriada entre usuários</li>
                <li>Limitações de responsabilidade</li>
            </ul>
        </div>

        <h2 id="politica">🔒 Política de Privacidade</h2>
        <div class="info-box">
            <p>Nos comprometemos a proteger sua privacidade e dados pessoais.</p>
            <p><strong>Seus direitos:</strong></p>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li>Controle sobre seus dados pessoais</li>
                <li>Transparência no uso das informações</li>
                <li>Segurança no armazenamento</li>
                <li>Opção de exclusão de conta</li>
            </ul>
        </div>
    </main>

    <footer class="rodape-logado">
        <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
        <p>
            <a href="suporte2.php">Suporte</a> | 
            <a href="suporte2.php#termos">Termos de Uso</a> | 
            <a href="suporte2.php#politica">Política de Privacidade</a>
        </p>
    </footer>
</body>
</html>