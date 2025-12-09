<?php
session_start();
require_once "../src/config.php";

$erro = false;
$sucesso = false;

// Verificar se tem sessão de recuperação
if (!isset($_SESSION['email_recuperacao']) || !isset($_SESSION['token_valido'])) {
    header("Location: esqueci_senha.php");
    exit();
}

$email = $_SESSION['email_recuperacao'];
$token = $_SESSION['token_valido'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
    $nova_senha = password_hash($_POST['nova_senha'], PASSWORD_DEFAULT);
    
    // Buscar token válido
    $sql = "SELECT t.usuario_id 
            FROM tokens_recuperacao t
            JOIN usuarios u ON t.usuario_id = u.id
            WHERE u.email = '$email' 
            AND t.token = '$token'
            AND t.expirado = 0
            AND t.data_criacao > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $token_data = $result->fetch_assoc();
        $usuario_id = $token_data['usuario_id'];
        
        // Atualizar senha do usuário
        $sql_update = "UPDATE usuarios SET senha = '$nova_senha' WHERE id = $usuario_id";
        
        if ($conn->query($sql_update)) {
            // Marcar token como expirado
            $sql_expire = "UPDATE tokens_recuperacao SET expirado = 1 WHERE token = '$token'";
            $conn->query($sql_expire);
            
            $sucesso = true;
            unset($_SESSION['token_valido']);
            unset($_SESSION['email_recuperacao']);
        } else {
            $erro = "Erro ao atualizar senha!";
        }
    } else {
        $erro = "Sessão expirada! Por favor, solicite novamente.";
        unset($_SESSION['token_valido']);
        unset($_SESSION['email_recuperacao']);
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Senha - TechAjuda</title>
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

        .form-container input[type="password"] {
            width: 100%;
            padding: 15px 20px;
            margin: 15px 0;
            border: 2px solid #333;
            border-radius: 10px;
            font-size: 1.1em;
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
            transition: all 0.3s ease;
            text-align: center;
        }

        .form-container input[type="password"]:focus {
            border-color: #08ebf3;
            outline: none;
            box-shadow: 0 0 15px rgba(8, 235, 243, 0.3);
            background: rgba(255, 255, 255, 0.15);
        }

        .form-container input::placeholder {
            color: #888;
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

        .sucesso {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: 2px solid #1e7e34;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .password-strength {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            font-weight: 600;
        }
        
        .strength-weak {
            background: rgba(220, 53, 69, 0.2);
            color: #dc3545;
            border: 1px solid #dc3545;
        }
        
        .strength-medium {
            background: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border: 1px solid #ffc107;
        }
        
        .strength-strong {
            background: rgba(40, 167, 69, 0.2);
            color: #28a745;
            border: 1px solid #28a745;
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
        <h2>🔑 Nova Senha</h2>
        
        <?php if ($sucesso): ?>
            <div class="sucesso">
                ✅ Senha alterada com sucesso!
            </div>
            <div class="links">
                <a href="entrar.php">🚀 Fazer Login</a>
            </div>
        <?php else: ?>
            <?php if ($erro): ?>
                <div class="mensagem-feedback erro">
                    ❌ <?php echo $erro; ?>
                </div>
                <div class="links">
                    <a href="esqueci_senha.php">↩️ Tentar Novamente</a>
                </div>
            <?php else: ?>
                <p style="text-align: center; color: #b0b0b0; margin-bottom: 20px;">
                    Crie uma nova senha para sua conta
                </p>

                <form method="POST" id="formNovaSenha">
                    <input type="password" name="nova_senha" id="novaSenha" 
                           placeholder="🔒 Nova senha" required 
                           minlength="6">
                    <div id="passwordStrength" class="password-strength" style="display: none;"></div>
                    
                    <input type="password" name="confirmar_senha" id="confirmarSenha" 
                           placeholder="🔒 Confirmar nova senha" required>
                    <div id="passwordMatch" style="text-align: center; margin: 10px 0;"></div>
                    
                    <button type="submit" id="btnSubmit">🚀 Alterar Senha</button>
                    
                    <div class="links">
                        <a href="entrar.php">🚪 Cancelar</a>
                    </div>
                </form>

                <script>
                    const novaSenha = document.getElementById('novaSenha');
                    const confirmarSenha = document.getElementById('confirmarSenha');
                    const strengthDiv = document.getElementById('passwordStrength');
                    const matchDiv = document.getElementById('passwordMatch');
                    const btnSubmit = document.getElementById('btnSubmit');

                    function checkPasswordStrength(password) {
                        let strength = 0;
                        
                        if (password.length >= 6) strength++;
                        if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
                        if (password.match(/\d/)) strength++;
                        if (password.match(/[^a-zA-Z\d]/)) strength++;
                        
                        return strength;
                    }

                    function updatePasswordStrength() {
                        const password = novaSenha.value;
                        const strength = checkPasswordStrength(password);
                        
                        if (password.length === 0) {
                            strengthDiv.style.display = 'none';
                            return;
                        }
                        
                        strengthDiv.style.display = 'block';
                        
                        if (strength < 2) {
                            strengthDiv.className = 'password-strength strength-weak';
                            strengthDiv.innerHTML = '❌ Senha fraca';
                        } else if (strength < 4) {
                            strengthDiv.className = 'password-strength strength-medium';
                            strengthDiv.innerHTML = '⚠️ Senha média';
                        } else {
                            strengthDiv.className = 'password-strength strength-strong';
                            strengthDiv.innerHTML = '✅ Senha forte';
                        }
                    }

                    function checkPasswordMatch() {
                        if (confirmarSenha.value === '') {
                            matchDiv.innerHTML = '';
                            return;
                        }
                        
                        if (novaSenha.value === confirmarSenha.value) {
                            matchDiv.innerHTML = '✅ Senhas coincidem';
                            matchDiv.style.color = '#28a745';
                        } else {
                            matchDiv.innerHTML = '❌ Senhas não coincidem';
                            matchDiv.style.color = '#dc3545';
                        }
                    }

                    novaSenha.addEventListener('input', updatePasswordStrength);
                    confirmarSenha.addEventListener('input', checkPasswordMatch);

                    document.getElementById('formNovaSenha').addEventListener('submit', function(e) {
                        if (novaSenha.value !== confirmarSenha.value) {
                            e.preventDefault();
                            alert('❌ As senhas não coincidem!');
                            return;
                        }
                        
                        if (novaSenha.value.length < 6) {
                            e.preventDefault();
                            alert('❌ A senha deve ter pelo menos 6 caracteres!');
                            return;
                        }
                    });
                </script>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>