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
            display: block !important;
        }

        /* Header Moderno */
        .topo {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
            padding: 0 20px !important;
            height: 80px !important;
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            position: fixed !important;
            width: 100% !important;
            top: 0 !important;
            z-index: 1000 !important;
            box-shadow: 0 3px 20px rgba(0, 0, 0, 0.5) !important;
            border-bottom: 1px solid #333 !important;
        }

        .topo .logo {
            display: flex !important;
            align-items: center !important;
            height: 100% !important;
        }

        .topo .logo img {
            height: 150px !important;
            width: auto !important;
            filter: brightness(0) invert(1) !important;
            transition: transform 0.3s ease !important;
            margin-left: 0 !important;
        }

        .topo .logo img:hover {
            transform: scale(1.05) !important;
        }

        .topo nav {
            display: flex !important;
            align-items: center !important;
            gap: 15px !important;
            height: auto !important;
        }

        .topo nav a {
            color: #e0e0e0 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            font-size: 1em !important;
            transition: all 0.3s ease !important;
            padding: 10px 18px !important;
            border-radius: 8px !important;
            position: relative !important;
            overflow: hidden !important;
            white-space: nowrap !important;
            margin-right: 0 !important;
            height: auto !important;
            display: block !important;
        }

        .topo nav a::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: -100% !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.2), transparent) !important;
            transition: left 0.5s !important;
        }

        .topo nav a:hover::before {
            left: 100% !important;
        }

        .topo nav a:hover {
            color: #08ebf3 !important;
            background: rgba(255, 255, 255, 0.05) !important;
        }

        .botao-assine {
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            color: #001a33 !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3) !important;
            border: none !important;
            padding: 10px 20px !important;
            border-radius: 8px !important;
        }

        .botao-assine:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.4) !important;
            color: #001a33 !important;
        }

        /* Conteúdo Principal */
        .conteudo-pagina {
            padding: 120px 20px 60px !important;
            color: #e0e0e0 !important;
            max-width: 1000px !important;
            margin: 0 auto !important;
            text-align: left !important;
        }

        .conteudo-pagina h1 {
            color: #08ebf3 !important;
            font-size: 3em !important;
            margin-bottom: 40px !important;
            text-align: center !important;
            background: linear-gradient(135deg, #ffffff, #08ebf3) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-weight: 800 !important;
        }

        .conteudo-pagina h2 {
            color: #08ebf3 !important;
            font-size: 1.8em !important;
            margin: 40px 0 20px 0 !important;
            border-left: 4px solid #08ebf3 !important;
            padding-left: 15px !important;
        }

        .conteudo-pagina p {
            margin-bottom: 20px !important;
            line-height: 1.6 !important;
            color: #b0b0b0 !important;
        }

        .conteudo-pagina a {
            color: #08ebf3 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
        }

        .conteudo-pagina a:hover {
            color: #00bcd4 !important;
            text-decoration: underline !important;
        }

        /* Seções de Suporte */
        .suporte-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)) !important;
            gap: 30px !important;
            margin: 40px 0 !important;
        }

        .suporte-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%) !important;
            padding: 30px 25px !important;
            border-radius: 15px !important;
            border: 1px solid #333 !important;
            transition: all 0.3s ease !important;
            text-align: center !important;
        }

        .suporte-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.4) !important;
            border-color: #08ebf3 !important;
        }

        .suporte-card h3 {
            color: #08ebf3 !important;
            font-size: 1.4em !important;
            margin-bottom: 15px !important;
            font-weight: 600 !important;
        }

        .suporte-card p {
            color: #b0b0b0 !important;
            margin-bottom: 0 !important;
        }

        .icone-suporte {
            font-size: 2.5em !important;
            margin-bottom: 15px !important;
            display: block !important;
        }

        /* Lista de Contatos */
        .lista-contatos {
            list-style: none !important;
            margin: 20px 0 !important;
        }

        .lista-contatos li {
            margin-bottom: 15px !important;
            padding-left: 25px !important;
            position: relative !important;
        }

        .lista-contatos li::before {
            content: '▶' !important;
            position: absolute !important;
            left: 0 !important;
            color: #08ebf3 !important;
            font-size: 0.8em !important;
        }

        /* FAQ Section */
        .faq-item {
            background: rgba(255, 255, 255, 0.05) !important;
            padding: 20px !important;
            border-radius: 10px !important;
            margin-bottom: 15px !important;
            border-left: 3px solid #08ebf3 !important;
        }

        .faq-pergunta {
            color: #08ebf3 !important;
            font-weight: 600 !important;
            margin-bottom: 10px !important;
            font-size: 1.1em !important;
        }

        .faq-resposta {
            color: #b0b0b0 !important;
            line-height: 1.5 !important;
        }

        /* Footer */
        .rodape {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
            text-align: center !important;
            padding: 40px 20px !important;
            border-top: 1px solid #333 !important;
        }

        .rodape p {
            margin: 10px 0 !important;
            color: #888 !important;
        }

        .rodape a {
            color: #08ebf3 !important;
            text-decoration: none !important;
            margin: 0 12px !important;
            font-weight: 600 !important;
            transition: all 0.3s ease !important;
            position: relative !important;
        }

        .rodape a::after {
            content: '' !important;
            position: absolute !important;
            bottom: -2px !important;
            left: 0 !important;
            width: 0 !important;
            height: 2px !important;
            background: #08ebf3 !important;
            transition: width 0.3s ease !important;
        }

        .rodape a:hover::after {
            width: 100% !important;
        }

        .rodape a:hover {
            color: #00bcd4 !important;
        }

        /* Responsividade */
        @media (max-width: 768px) {
            .topo {
                padding: 0 15px !important;
                height: 70px !important;
            }
            
            .topo .logo img {
                height: 120px !important;
            }
            
            .topo nav {
                gap: 8px !important;
            }
            
            .topo nav a {
                font-size: 0.9em !important;
                padding: 8px 12px !important;
            }
            
            .conteudo-pagina {
                padding: 100px 20px 40px !important;
            }
            
            .conteudo-pagina h1 {
                font-size: 2.5em !important;
            }
            
            .suporte-grid {
                grid-template-columns: 1fr !important;
                gap: 20px !important;
            }
        }

        @media (max-width: 480px) {
            .topo {
                padding: 0 10px !important;
            }
            
            .topo nav {
                gap: 5px !important;
            }
            
            .topo nav a {
                font-size: 0.8em !important;
                padding: 6px 8px !important;
            }
            
            .topo .logo img {
                height: 100px !important;
            }
            
            .conteudo-pagina h1 {
                font-size: 2em !important;
            }
            
            .conteudo-pagina h2 {
                font-size: 1.5em !important;
            }
        }
    </style>
</head>
<body>

<header class="topo">
    <div class="logo">
        <img src="../visualscript/imagem/logotcc.png" alt="TechAjuda">
    </div>
    <nav>
        <a href="index.php#sobre">Sobre</a>
        <a href="index.php#funciona">Como Funciona</a>
        <a href="index.php#tecnicos">Para Técnicos</a>
        <a href="cadastro.php" class="botao-assine">Criar Conta</a>
        <a href="entrar.php">Entrar</a>
    </nav>
</header>

<main class="conteudo-pagina">
    <h1>Central de Suporte</h1>
    <p>Estamos aqui para ajudar você! Encontre abaixo todas as formas de entrar em contato conosco e resolver suas dúvidas.</p>

    <div class="suporte-grid">
        <div class="suporte-card">
            <span class="icone-suporte">📧</span>
            <h3>E-mail de Suporte</h3>
            <p>Envie suas dúvidas e solicitações para nosso time especializado</p>
            <p><strong><a href="mailto:suporte@techajuda.com">suporte@techajuda.com</a></strong></p>
        </div>

        <div class="suporte-card">
            <span class="icone-suporte">🕒</span>
            <h3>Atendimento</h3>
            <p>Suporte disponível 24/7 para emergências</p>
            <p><strong>Resposta em até 24h</strong></p>
        </div>

        <div class="suporte-card">
            <span class="icone-suporte">🔧</span>
            <h3>Problemas Técnicos</h3>
            <p>Relate problemas com a plataforma ou sugestões de melhorias</p>
            <p><strong>Atendimento prioritário</strong></p>
        </div>
    </div>

    <h2 id="termos">📋 Termos de Uso</h2>
    <p>Ao utilizar a plataforma TechAjuda, você concorda com nossos termos de serviço:</p>
    
    <div class="faq-item">
        <div class="faq-pergunta">Uso da Plataforma</div>
        <div class="faq-resposta">A TechAjuda é uma plataforma de conexão entre usuários e técnicos. Não nos responsabilizamos pelos serviços prestados pelos técnicos cadastrados.</div>
    </div>

    <div class="faq-item">
        <div class="faq-pergunta">Responsabilidades</div>
        <div class="faq-resposta">Os técnicos são profissionais independentes. Combine valores, prazos e formas de pagamento diretamente com o prestador de serviços.</div>
    </div>

    <div class="faq-item">
        <div class="faq-pergunta">Conduta na Plataforma</div>
        <div class="faq-resposta">Mantenha sempre respeito e profissionalismo em todas as interações. Comportamentos inadequados podem resultar em suspensão da conta.</div>
    </div>

    <h2 id="politica">🔒 Política de Privacidade</h2>
    <p>Nosso compromisso com a segurança dos seus dados:</p>

    <div class="faq-item">
        <div class="faq-pergunta">Coleta de Dados</div>
        <div class="faq-resposta">Coletamos apenas informações necessárias para o funcionamento da plataforma: nome, e-mail, telefone e localização.</div>
    </div>

    <div class="faq-item">
        <div class="faq-pergunta">Uso das Informações</div>
        <div class="faq-resposta">Seus dados são utilizados exclusivamente para conectar você com técnicos e melhorar sua experiência na plataforma.</div>
    </div>

    <div class="faq-item">
        <div class="faq-pergunta">Segurança</div>
        <div class="faq-resposta">Implementamos medidas de segurança avançadas para proteger suas informações contra acessos não autorizados.</div>
    </div>

    <h2>❓ Dúvidas Frequentes</h2>

    <div class="faq-item">
        <div class="faq-pergunta">Como me cadastrar como técnico?</div>
        <div class="faq-resposta">Acesse "Criar Conta" e selecione a opção "Sou Técnico". Preencha seus dados profissionais e aguarde a aprovação.</div>
    </div>

    <div class="faq-item">
        <div class="faq-pergunta">A plataforma é gratuita?</div>
        <div class="faq-resposta">Sim! O cadastro e uso básico da plataforma são totalmente gratuitos para usuários e técnicos.</div>
    </div>

    <div class="faq-item">
        <div class="faq-pergunta">Como funcionam os pagamentos?</div>
        <div class="faq-resposta">Os valores e formas de pagamento são combinados diretamente entre você e o técnico. A TechAjuda não interfere nesse processo.</div>
    </div>

    <div class="faq-item">
        <div class="faq-pergunta">Posso cancelar minha conta?</div>
        <div class="faq-resposta">Sim, entre em contato pelo e-mail de suporte solicitando o cancelamento da sua conta.</div>
    </div>

    <h2>📞 Precisa de Ajuda Imediata?</h2>
    <p>Se você está enfrentando problemas urgentes com a plataforma, entre em contato conosco imediatamente:</p>
    
    <ul class="lista-contatos">
        <li><strong>E-mail Prioritário:</strong> <a href="mailto:urgente@techajuda.com">urgente@techajuda.com</a></li>
        <li><strong>Assunto:</strong> Inclua "URGENTE" no assunto do e-mail para atendimento prioritário</li>
        <li><strong>Informações:</strong> Descreva detalhadamente o problema que está enfrentando</li>
    </ul>
</main>

<footer class="rodape">
    <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
    <p>
        <a href="suporte.php">Suporte</a> | 
        <a href="suporte.php#termos">Termos de Uso</a> | 
        <a href="suporte.php#politica">Política de Privacidade</a>
    </p>
</footer>

</body>
</html>