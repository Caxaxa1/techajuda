<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Termos de Uso - TechAjuda</title>
    <link rel="stylesheet" href=".../visualscript/css/style.css">
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

<header class="topo-logado">
    <div class="logo">
        <img src="../visualscript/imagem/logotcc.png" alt="TechAjuda">
    </div>
    <div class="nav-right">
    <a href="area_logada.php" class="nav-link">Menu Principal</a>
    <a href="tecnico/tornar_tecnico.php" class="nav-link">Tornar Técnico</a>
    <a href="minha_conta.php" class="nav-link">Minha Conta</a>
    <a href="logout.php" class="nav-link">Sair</a>
    <div class="account-circle"></div>
</div>
</header>

<main class="conteudo-pagina">
    <h1>Termos de Uso</h1>
    <p>Última atualização: <?php echo date("d/m/Y"); ?></p>
    
    <h2>1. Aceitação dos Termos</h2>
    <p>Ao utilizar a plataforma TechAjuda, você concorda com estes Termos de Uso e com nossa Política de Privacidade.</p>
    
    <h2>2. Uso da Plataforma</h2>
    <p>A TechAjuda é uma plataforma que conecta usuários a técnicos em informática. Você concorda em:</p>
    <ul>
        <li>Fornecer informações verdadeiras no cadastro</li>
        <li>Utilizar o serviço apenas para fins legais</li>
        <li>Respeitar os profissionais e outros usuários</li>
    </ul>
    
    <h2>3. Responsabilidades</h2>
    <p>A TechAjuda atua como intermediária e não se responsabiliza por serviços prestados por terceiros.</p>
</main>

<footer class="rodape">
    <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
    <p><a href="suporte.php">Suporte</a> | <a href="termos.php">Termos de Uso</a> | <a href="politica.php">Política de Privacidade</a></p>
</footer>

</body>
</html>