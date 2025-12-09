<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechAjuda - Conectando Você aos Melhores Técnicos</title>
    <link rel="stylesheet" href="../visualscript/css/style.css">
    <style>
        /* Reset completo para sobrescrever o CSS externo */
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
            flex-direction: column;
        }

        /* Header Moderno - Sobrescrevendo completamente */
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
            max-height: none !important;
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
            transform: none !important;
            box-shadow: none !important;
        }

        .topo nav a:hover::after {
            display: none !important;
        }

        .botao-assine {
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            color: #001a33 !important;
            font-weight: 700 !important;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3) !important;
            border: none !important;
            padding: 10px 20px !important;
            border-radius: 8px !important;
            margin-left: 0 !important;
        }

        .botao-assine:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7) !important;
            transform: translateY(-2px) !important;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.4) !important;
            color: #001a33 !important;
        }

        /* Conteúdo Principal */
        .conteudo {
            margin-top: 80px !important;
            flex: 1 !important;
            padding: 0 !important;
            text-align: left !important;
        }

        /* Hero Section com Luz */
        .mural {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%) !important;
            padding: 100px 20px !important;
            text-align: center !important;
            position: relative !important;
            overflow: hidden !important;
            min-height: 80vh !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            align-items: center !important;
            margin-top: 0 !important;
        }

        .mural::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(8, 235, 243, 0.1),
                rgba(8, 235, 243, 0.2),
                rgba(8, 235, 243, 0.1),
                transparent
            ) !important;
            animation: luzHorizontal 8s linear infinite !important;
        }

        @keyframes luzHorizontal {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .mural h1 {
            font-size: 3.5em !important;
            font-weight: 800 !important;
            margin-bottom: 30px !important;
            background: linear-gradient(135deg, #ffffff, #08ebf3) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            text-shadow: 0 4px 15px rgba(8, 235, 243, 0.3) !important;
            position: relative !important;
            z-index: 2 !important;
            color: transparent !important;
        }

        .mural p {
            font-size: 1.3em !important;
            max-width: 700px !important;
            margin: 0 auto 50px !important;
            color: #b0b0b0 !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .blocos-beneficios {
            display: flex !important;
            justify-content: center !important;
            gap: 30px !important;
            max-width: 800px !important;
            margin: 0 auto 50px !important;
            position: relative !important;
            z-index: 2 !important;
            flex-wrap: wrap !important;
            margin-top: 0 !important;
        }

        .bloco {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%) !important;
            padding: 35px 25px !important;
            border-radius: 15px !important;
            text-align: center !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5) !important;
            border: 1px solid #333 !important;
            transition: all 0.4s ease !important;
            position: relative !important;
            overflow: hidden !important;
            flex: 1 !important;
            max-width: 350px !important;
            min-height: 200px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            width: auto !important;
        }

        .bloco::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: -100% !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.1), transparent) !important;
            transition: left 0.6s !important;
        }

        .bloco:hover::before {
            left: 100% !important;
        }

        .bloco:hover {
            transform: translateY(-10px) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6) !important;
            border-color: #08ebf3 !important;
        }

        .bloco h2 {
            color: #08ebf3 !important;
            font-size: 1.5em !important;
            margin-bottom: 15px !important;
            font-weight: 700 !important;
        }

        .bloco p {
            color: #b0b0b0 !important;
            font-size: 1em !important;
            margin: 0 !important;
            line-height: 1.5 !important;
        }

        .botao-principal {
            display: inline-block !important;
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            color: #001a33 !important;
            text-decoration: none !important;
            padding: 16px 35px !important;
            border-radius: 12px !important;
            font-weight: 700 !important;
            font-size: 1.1em !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.3) !important;
            position: relative !important;
            z-index: 2 !important;
            margin-top: 0 !important;
        }

        .botao-principal:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7) !important;
            transform: translateY(-3px) !important;
            box-shadow: 0 8px 25px rgba(8, 235, 243, 0.4) !important;
            color: #001a33 !important;
        }

        /* Seção Como Funciona - Sem Luz */
        .como-funciona {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%) !important;
            padding: 100px 20px !important;
            text-align: center !important;
            width: 100% !important;
            box-sizing: border-box !important;
            margin-top: 0 !important;
        }

        .como-funciona h2 {
            font-size: 3.5em !important;
            margin-bottom: 60px !important;
            background: linear-gradient(135deg, #ffffff, #08ebf3) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-weight: 800 !important;
            text-shadow: 0 2px 8px rgba(8, 235, 243, 0.3) !important;
            color: transparent !important;
        }

        .etapas {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 30px !important;
            max-width: 1200px !important;
            margin: 0 auto !important;
            flex-wrap: wrap !important;
            justify-content: center !important;
        }

        .etapa {
            background: rgba(255, 255, 255, 0.05) !important;
            padding: 40px 25px !important;
            border-radius: 15px !important;
            text-align: center !important;
            border: 1px solid #333 !important;
            transition: all 0.4s ease !important;
            position: relative !important;
            overflow: hidden !important;
            min-height: 200px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
            width: auto !important;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.3) !important;
        }

        .etapa::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 4px !important;
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            transform: scaleX(0) !important;
            transition: transform 0.4s ease !important;
        }

        .etapa:hover::before {
            transform: scaleX(1) !important;
        }

        .etapa:hover {
            transform: translateY(-8px) !important;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4) !important;
            background: rgba(255, 255, 255, 0.08) !important;
        }

        .etapa h3 {
            color: #08ebf3 !important;
            font-size: 1.4em !important;
            margin-bottom: 15px !important;
            font-weight: 600 !important;
        }

        .etapa p {
            color: #b0b0b0 !important;
            font-size: 1em !important;
            line-height: 1.5 !important;
        }

        /* Números das etapas */
        .etapa:nth-child(1)::after {
            content: '1' !important;
            position: absolute !important;
            top: 15px !important;
            right: 15px !important;
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            color: #001a33 !important;
            width: 35px !important;
            height: 35px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 700 !important;
            font-size: 1em !important;
        }

        .etapa:nth-child(2)::after {
            content: '2' !important;
            position: absolute !important;
            top: 15px !important;
            right: 15px !important;
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            color: #001a33 !important;
            width: 35px !important;
            height: 35px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 700 !important;
            font-size: 1em !important;
        }

        .etapa:nth-child(3)::after {
            content: '3' !important;
            position: absolute !important;
            top: 15px !important;
            right: 15px !important;
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            color: #001a33 !important;
            width: 35px !important;
            height: 35px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 700 !important;
            font-size: 1em !important;
        }

        .etapa:nth-child(4)::after {
            content: '4' !important;
            position: absolute !important;
            top: 15px !important;
            right: 15px !important;
            background: linear-gradient(135deg, #08ebf3, #00bcd4) !important;
            color: #001a33 !important;
            width: 35px !important;
            height: 35px !important;
            border-radius: 50% !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-weight: 700 !important;
            font-size: 1em !important;
        }

        /* Seção Para Técnicos com Luz */
        .para-tecnicos {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%) !important;
            padding: 100px 20px !important;
            text-align: center !important;
            position: relative !important;
            overflow: hidden !important;
        }

        .para-tecnicos::before {
            content: '' !important;
            position: absolute !important;
            top: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            background: linear-gradient(
                90deg,
                transparent,
                rgba(8, 235, 243, 0.1),
                rgba(8, 235, 243, 0.2),
                rgba(8, 235, 243, 0.1),
                transparent
            ) !important;
            animation: luzHorizontal 8s linear infinite !important;
            animation-delay: 2s !important;
        }

        .para-tecnicos h2 {
            font-size: 3.5em !important;
            margin-bottom: 20px !important;
            background: linear-gradient(135deg, #ffffff, #08ebf3) !important;
            -webkit-background-clip: text !important;
            -webkit-text-fill-color: transparent !important;
            background-clip: text !important;
            font-weight: 800 !important;
            text-shadow: 0 2px 8px rgba(8, 235, 243, 0.3) !important;
            position: relative !important;
            z-index: 2 !important;
            color: transparent !important;
        }

        .para-tecnicos > p {
            font-size: 1.2em !important;
            color: #b0b0b0 !important;
            max-width: 700px !important;
            margin: 0 auto 50px !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .vantagens {
            display: grid !important;
            grid-template-columns: repeat(2, 1fr) !important;
            gap: 30px !important;
            max-width: 1100px !important;
            margin: 0 auto !important;
            position: relative !important;
            z-index: 2 !important;
        }

        .vantagem {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%) !important;
            padding: 35px 25px !important;
            border-radius: 15px !important;
            text-align: center !important;
            border: 1px solid #333 !important;
            transition: all 0.3s ease !important;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.5) !important;
            min-height: 200px !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: center !important;
        }

        .vantagem:hover {
            transform: translateY(-8px) !important;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.6) !important;
            border-color: #08ebf3 !important;
        }

        .vantagem h3 {
            color: #08ebf3 !important;
            font-size: 1.4em !important;
            margin-bottom: 15px !important;
            font-weight: 600 !important;
        }

        .vantagem p {
            color: #b0b0b0 !important;
            font-size: 1em !important;
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
            text-decoration: none !important;
        }

        /* Animações de Scroll */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.8s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Responsividade */
        @media (max-width: 1024px) {
            .etapas,
            .vantagens {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }

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
            
            .conteudo {
                margin-top: 70px !important;
            }
            
            .mural {
                padding: 80px 20px !important;
            }
            
            .mural h1 {
                font-size: 2.5em !important;
            }
            
            .mural p {
                font-size: 1.1em !important;
            }
            
            .blocos-beneficios {
                flex-direction: column !important;
                align-items: center !important;
                gap: 25px !important;
            }
            
            .bloco {
                max-width: 100% !important;
            }
            
            .como-funciona h2,
            .para-tecnicos h2 {
                font-size: 2.8em !important;
            }
            
            .etapas,
            .vantagens {
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
            
            .mural {
                padding: 60px 15px !important;
            }
            
            .mural h1 {
                font-size: 2em !important;
            }
            
            .botao-principal {
                padding: 14px 25px !important;
                font-size: 1em !important;
            }
            
            .como-funciona h2,
            .para-tecnicos h2 {
                font-size: 2.2em !important;
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
        <a href="#sobre">Sobre</a>
        <a href="#funciona">Como Funciona</a>
        <a href="#tecnicos">Para Técnicos</a>
        <a href="cadastro.php" class="botao-assine">Criar Conta</a>
        <a href="entrar.php">Entrar</a>
    </nav>
</header>

<main class="conteudo">

    <!-- Hero Section -->
    <section id="sobre" class="mural">
        <h1 class="fade-in">CONECTANDO QUEM PRECISA COM QUEM ENTENDE DE TECNOLOGIA</h1>
        <p class="fade-in">Encontre os melhores técnicos especializados da sua região. Soluções rápidas, seguras e com avaliações reais de outros clientes.</p>

        <div class="blocos-beneficios">
            <div class="bloco fade-in">
                <h2>Sou Usuário</h2>
                <p>Encontre técnicos qualificados perto de você para resolver problemas de computadores, celulares, redes e muito mais.</p>
            </div>
            <div class="bloco fade-in">
                <h2>Sou Técnico</h2>
                <p>Cadastre-se e comece a receber solicitações de serviços. Gerencie sua agenda e aumente sua renda.</p>
            </div>
        </div>

        <a href="cadastro.php" class="botao-principal fade-in">Comece Agora - É Grátis</a>
    </section>

    <!-- Como Funciona -->
    <section id="funciona" class="como-funciona">
        <h2 class="fade-in">COMO FUNCIONA</h2>
        <div class="etapas">
            <div class="etapa fade-in">
                <h3>Cadastro Rápido</h3>
                <p>Crie sua conta em menos de 2 minutos como usuário ou técnico. É simples, gratuito e seguro.</p>
            </div>
            <div class="etapa fade-in">
                <h3>Encontre ou Seja Encontrado</h3>
                <p>Usuários buscam técnicos por especialidade e localização. Técnicos aparecem para clientes próximos.</p>
            </div>
            <div class="etapa fade-in">
                <h3>Contato Direto</h3>
                <p>Entre em contato via WhatsApp ou telefone. Combine todos os detalhes do serviço diretamente.</p>
            </div>
            <div class="etapa fade-in">
                <h3>Agendamento e Serviço</h3>
                <p>Técnicos gerenciam agenda no sistema. Usuários acompanham a disponibilidade em tempo real.</p>
            </div>
        </div>
    </section>

    <!-- Seção Para Técnicos -->
    <section id="tecnicos" class="para-tecnicos">
        <h2 class="fade-in">PARA TÉCNICOS</h2>
        <p class="fade-in">Aumente sua renda e otimize seu tempo com nossa plataforma</p>
        
        <div class="vantagens">
            <div class="vantagem fade-in">
                <h3>Mais Clientes</h3>
                <p>Apareça para usuários da sua região que precisam dos seus serviços específicos.</p>
            </div>
            <div class="vantagem fade-in">
                <h3>Agenda Organizada</h3>
                <p>Gerencie seus horários de atendimento e evite conflitos na sua agenda.</p>
            </div>
            <div class="vantagem fade-in">
                <h3>Avaliações</h3>
                <p>Receba feedback dos clientes e construa uma reputação sólida.</p>
            </div>
            <div class="vantagem fade-in">
                <h3>Independência</h3>
                <p>Você define seus valores, horários e combina pagamentos diretamente com os clientes.</p>
            </div>
        </div>
    </section>

</main>

<footer class="rodape">
    <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
    <p>
        <a href="suporte.php">Suporte</a> | 
        <a href="admin/area_admin.php">Administrador</a>
    </p>
</footer>

<script>
    // Animação de scroll
    document.addEventListener('DOMContentLoaded', function() {
        const fadeElements = document.querySelectorAll('.fade-in');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        fadeElements.forEach(element => {
            observer.observe(element);
        });
    });

    // Smooth scroll para links internos
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>

</body>
</html>