<?php
session_start();
require_once "../src/config.php";

$erro = false;

// Se veio por link direto com token, redireciona para nova senha
if (isset($_GET['token'])) {
    $_SESSION['token_valido'] = $_GET['token'];
    header("Location: nova_senha.php");
    exit();
}

if (!isset($_SESSION['email_recuperacao'])) {
    header("Location: esqueci_senha.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $codigo = $conn->real_escape_string($_POST['codigo']);
    $email = $_SESSION['email_recuperacao'];
    
    // Buscar token válido (não expirado, criado há menos de 1 hora)
    $sql = "SELECT t.token 
            FROM tokens_recuperacao t
            JOIN usuarios u ON t.usuario_id = u.id
            WHERE u.email = '$email' 
            AND t.token LIKE '$codigo%'
            AND t.expirado = 0
            AND t.data_criacao > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $token_data = $result->fetch_assoc();
        $_SESSION['token_valido'] = $token_data['token'];
        header("Location: nova_senha.php");
        exit();
    } else {
        $erro = "Código inválido ou expirado!";
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Código - TechAjuda</title>
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

        .topo {
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

        .topo .logo {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .topo .logo img {
            height: 150px;
            width: auto;
            filter: brightness(0) invert(1);
            transition: transform 0.3s ease;
            margin-left: 0;
        }

        .topo .logo img:hover {
            transform: scale(1.05);
        }

        .topo nav {
            display: flex;
            align-items: center;
            gap: 15px;
            height: auto;
        }

        .topo nav a {
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

        .topo nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.2), transparent);
            transition: left 0.5s;
        }

        .topo nav a:hover::before {
            left: 100%;
        }

        .topo nav a:hover {
            color: #08ebf3;
            background: rgba(255, 255, 255, 0.05);
        }

        .botao-assine {
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(8, 235, 243, 0.3);
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
        }

        .botao-assine:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.4);
            color: #001a33;
        }

        .form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            color: #e0e0e0;
            width: 90%;
            max-width: 500px;
            padding: 50px 40px;
            border-radius: 20px;
            margin: 120px auto 60px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 2px solid #333;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(8, 235, 243, 0.1), transparent);
            transition: left 0.6s;
        }

        .form-container:hover::before {
            left: 100%;
        }

        .form-container h2 {
            margin-bottom: 30px;
            font-size: 2.5em;
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }

        .codigo-input {
            width: 100%;
            padding: 15px 20px;
            margin: 20px 0;
            border: 2px solid #333;
            border-radius: 10px;
            font-size: 1.8em;
            background: rgba(255, 255, 255, 0.1);
            color: #08ebf3;
            transition: all 0.3s ease;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
        }

        .codigo-input:focus {
            border-color: #08ebf3;
            outline: none;
            box-shadow: 0 0 15px rgba(8, 235, 243, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-container button {
            width: 100%;
            padding: 18px;
            background: linear-gradient(135deg, #08ebf3, #00bcd4);
            color: #001a33;
            font-weight: 700;
            border: none;
            border-radius: 12px;
            font-size: 1.2em;
            margin-top: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.3);
        }
        
        .form-container button:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(8, 235, 243, 0.4);
        }
        
        .links {
            text-align: center;
            margin-top: 25px;
        }
        
        .links a {
            color: #08ebf3;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            margin: 5px;
        }
        
        .links a:hover {
            color: #00bcd4;
            background: rgba(255, 255, 255, 0.05);
        }
        
        .mensagem-feedback {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px;
            border-radius: 12px;
            font-weight: 600;
            border: 2px solid;
        }
        
        .erro {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border-color: #ff4444;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .info-codigo {
            background: rgba(8, 235, 243, 0.1);
            border: 1px solid #08ebf3;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
            text-align: center;
        }

        @media (max-width: 768px) {
            .topo {
                padding: 0 15px;
                height: 70px;
            }
            
            .topo .logo img {
                height: 120px;
            }
            
            .topo nav {
                gap: 8px;
            }
            
            .topo nav a {
                font-size: 0.9em;
                padding: 8px 12px;
            }
            
            .form-container {
                margin: 100px auto 40px;
                padding: 40px 25px;
            }
            
            .form-container h2 {
                font-size: 2em;
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

    <div class="form-container">
        <h2>🔢 Verificar Código</h2>
        
        <div class="info-codigo">
            <p style="margin: 0; color: #08ebf3; font-weight: 600;">
                📧 Código enviado para:<br>
                <?php echo $_SESSION['email_recuperacao']; ?>
            </p>
        </div>
        
        <p style="text-align: center; color: #b0b0b0; margin-bottom: 20px;">
            Digite o código de 6 dígitos que você recebeu por email
        </p>
        
        <?php if ($erro): ?>
            <div class="mensagem-feedback erro">
                ❌ <?php echo $erro; ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="text" name="codigo" class="codigo-input" 
                   placeholder="••••••" maxlength="6" required 
                   pattern="[0-9a-fA-F]{6}" title="Digite os 6 dígitos do código">
            <button type="submit">✅ Verificar Código</button>
            
            <div class="links">
                <a href="esqueci_senha.php">↩️ Reenviar Código</a>
                <a href="entrar.php">🚪 Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>