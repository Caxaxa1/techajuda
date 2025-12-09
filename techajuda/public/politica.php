<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Privacidade - TechAjuda</title>
    <link rel="stylesheet" href="../visualscript/css/style.css">
    <style>
        .conteudo-pagina {
            padding: 160px 90px 60px;
            color: #fff;
            max-width: 1000px;
            margin: 0 auto;
            text-align: left;
        }
        .conteudo-pagina h1 {
            color: #08ebf3;
            font-size: 2.5em;
            margin-bottom: 30px;
        }
        .conteudo-pagina h2 {
            color: #08ebf3;
            margin-top: 30px;
        }
        .conteudo-pagina p, .conteudo-pagina ul {
            margin-bottom: 20px;
            line-height: 1.6;
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
        <a href="cadastro.php" class="botao-assine">Criar Conta</a>
        <a href="entrar.php">Entrar</a>
    </nav>
</header>

<main class="conteudo-pagina">
    <h1>Política de Privacidade</h1>
    <p>Última atualização: <?php echo date("d/m/Y"); ?></p>
    
    <h2>1. Informações Coletadas</h2>
    <p>Coletamos informações que você nos fornece diretamente, incluindo:</p>
    <ul>
        <li>Dados de cadastro (nome, e-mail, CPF)</li>
        <li>Informações de contato</li>
        <li>Dados de transações (quando aplicável)</li>
    </ul>
    
    <h2>2. Uso das Informações</h2>
    <p>Utilizamos seus dados para:</p>
    <ul>
        <li>Fornecer e melhorar nossos serviços</li>
        <li>Comunicar-se com você</li>
        <li>Garantir a segurança da plataforma</li>
    </ul>
    
    <h2>3. Compartilhamento de Dados</h2>
    <p>Não vendemos seus dados. Podemos compartilhar informações apenas com:</p>
    <ul>
        <li>Técnicos contratados para prestação de serviços</li>
        <li>Autoridades legais quando exigido por lei</li>
    </ul>
</main>

<footer class="rodape">
    <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
    <p><a href="suporte.php">Suporte</a> | <a href="termos.php">Termos de Uso</a> | <a href="politica.php">Política de Privacidade</a></p>
</footer>

</body>
</html>