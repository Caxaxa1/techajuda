<?php
session_start();
require_once "../../src/config.php";

// Verificar se o técnico está logado E se está aprovado
if (!isset($_SESSION['tecnico_id'])) {
    header("Location: tornar_tecnico.php");
    exit();
}

// Verificar se o técnico está aprovado
$conn = getDBConnection();
$tecnico_id = $_SESSION['tecnico_id'];

$sql = "SELECT status FROM tecnicos WHERE id = $tecnico_id";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $tecnico = $result->fetch_assoc();
   
    // Se o técnico não estiver aprovado, redirecionar com mensagem
    if ($tecnico['status'] !== 'aprovado') {
        $conn->close();
        header("Location: tornar_tecnico.php?erro=nao_aprovado");
        exit();
    }
} else {
    // Técnico não encontrado
    $conn->close();
    header("Location: tornar_tecnico.php");
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área do Técnico - TechAjuda</title>
    <link rel="stylesheet" href="../../visualscript/css/style.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: url('../../visualscript/imagem/background1.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .mensagem-centralizada {
            text-align: center;
            color: white;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            max-width: 600px;
            margin: 150px auto 50px;
        }

        .mensagem-centralizada h1 {
            font-size: 2.5em;
            color: #ffffff;
            margin-bottom: 30px;
        }

        .botoes-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            margin-top: 30px;
        }

        .botao-grande {
            display: inline-block;
            padding: 20px 40px;
            background: linear-gradient(135deg, #4dabf7 0%, #2196f3 100%);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: bold;
            font-size: 1.2em;
            transition: all 0.3s;
            text-align: center;
        }

        .botao-grande:hover {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .botao-voltar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 25px;
            background: linear-gradient(135deg, #4dabf7 0%, #2196f3 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s;
        }

        .botao-voltar:hover {
            background: linear-gradient(135deg, #2196f3 0%, #1976d2 100%);
            transform: translateY(-2px);
        }

        /* Estilos do header da área logada */
        .topo-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            padding: 0 5px;
            height: 90px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 100;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
        }

        .nav-link:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }

        .account-circle {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #4dabf7 0%, #2196f3 100%);
            border-radius: 50%;
            margin-left: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Rodapé da área logada */
        .rodape-logado {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            padding: 20px;
            font-size: 0.9em;
            color: #a0a0a0;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .rodape-logado a {
            color: #4dabf7;
            text-decoration: none;
            font-weight: bold;
            margin: 0 10px;
        }

        .rodape-logado a:hover {
            text-decoration: underline;
            color: #a5d8ff;
        }
    </style>
</head>
<body>
    <!-- Header da Área Logada -->
    <header class="topo-logado">
        <div class="logo">
            <img src="../../visualscript/imagem/logotcc.png" alt="TechAjuda">
        </div>
        <div class="nav-right">
            <a href="../area_logada.php" class="nav-link">Menu Principal</a>
            <a href="tornar_tecnico.php" class="nav-link">Tornar Técnico</a>
            <a href="minha_conta_tecnico.php" class="nav-link">Minha Conta</a>
            <a href="../logout.php" class="nav-link">Sair</a>
            <div class="account-circle">
                <?php if (!empty($_SESSION['usuario_foto'])): ?>
                    <img src="../../<?php echo $_SESSION['usuario_foto']; ?>" alt="Foto Perfil"
                         style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                <?php else: ?>
                    <span style="color: white; font-size: 20px;">👤</span>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Conteúdo Principal -->
    <div class="mensagem-centralizada">
        <h1>Área do Técnico</h1>
        <p>Bem-vindo à sua área técnica! Gerencie seus serviços e disponibilidade.</p>
        
        <div class="botoes-container">
            <a href="minha_agenda.php" class="botao-grande">📅 Minha Agenda</a>
            <a href="minha_conta_tecnico.php" class="botao-grande">👤 Minha Conta</a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="rodape-logado">
        <p>&copy; <?php echo date("Y"); ?> TechAjuda - Todos os direitos reservados</p>
        <p>
            <a href="../suporte2.php">Suporte</a> |
            <a href="../suporte2.php#termos">Termos de Uso</a> |
            <a href="../suporte2.php#politica">Política de Privacidade</a>
        </p>
    </footer>

    <!-- Botão Voltar para Login -->
    <a href="tornar_tecnico.php" class="botao-voltar">← Voltar ao Login</a>
</body>
</html>