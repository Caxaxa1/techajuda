<?php
require_once "../src/config.php";

$erro_idade = false;
$erro_duplicado = false;
$mensagem_erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = getDBConnection();
   
    // Coletar dados do formulário
    $nome = $conn->real_escape_string($_POST['nome']);
    $idade = intval($_POST['idade']);
    $cpf = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cpf']));
    $sexo = $conn->real_escape_string($_POST['sexo']);
    $apelido = $conn->real_escape_string($_POST['apelido']);
    $email = $conn->real_escape_string($_POST['email']);
    $celular = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['celular']));
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $endereco = $conn->real_escape_string($_POST['endereco']);
    $cep = $conn->real_escape_string(preg_replace('/[^0-9]/', '', $_POST['cep']));
    $numero = $conn->real_escape_string($_POST['numero']);
    $cidade = $conn->real_escape_string($_POST['cidade']);
    $estado = $conn->real_escape_string($_POST['estado']);
   
    // Verificar idade
    if ($idade < 18) {
        $erro_idade = true;
    } else {
        // Verificar se já existe usuário com o mesmo email ou CPF
        $sql_verifica = "SELECT id FROM usuarios WHERE email = '$email' OR cpf = '$cpf'";
        $result_verifica = $conn->query($sql_verifica);
       
        if ($result_verifica->num_rows > 0) {
            $erro_duplicado = true;
            
            // Verificar qual campo está duplicado para dar mensagem específica
            $usuario_duplicado = $result_verifica->fetch_assoc();
            $sql_detalhes = "SELECT email, cpf FROM usuarios WHERE id = " . $usuario_duplicado['id'];
            $result_detalhes = $conn->query($sql_detalhes);
            $detalhes = $result_detalhes->fetch_assoc();
            
            if ($detalhes['email'] === $email && $detalhes['cpf'] === $cpf) {
                $mensagem_erro = "Já existe um usuário cadastrado com este E-mail e CPF!";
            } elseif ($detalhes['email'] === $email) {
                $mensagem_erro = "Já existe um usuário cadastrado com este E-mail!";
            } elseif ($detalhes['cpf'] === $cpf) {
                $mensagem_erro = "Já existe um usuário cadastrado com este CPF!";
            }
        } else {
            // Inserir no banco de dados - ATUALIZADO COM CIDADE E ESTADO
            $sql = "INSERT INTO usuarios (nome, idade, cpf, sexo, apelido, email, celular, senha, endereco, cep, numero, cidade, estado)
                    VALUES ('$nome', $idade, '$cpf', '$sexo', '$apelido', '$email', '$celular', '$senha', '$endereco', '$cep', '$numero', '$cidade', '$estado')";
           
            if ($conn->query($sql)) {
                header("Location: entrar.php?cadastro=sucesso");
                exit();
            } else {
                die("Erro ao cadastrar: " . $conn->error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - TechAjuda</title>
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

        /* Header */
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

        /* Form Container */
        .form-container {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            color: #e0e0e0;
            width: 90%;
            max-width: 600px;
            padding: 50px 40px;
            border-radius: 20px;
            margin: 120px auto 60px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            border: 2px solid #333;
            position: relative;
            overflow: hidden;
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
            text-align: center;
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: 800;
        }

        .form-container input,
        .form-container select {
            width: 100%;
            padding: 15px 20px;
            margin: 10px 0;
            border: 2px solid #333;
            border-radius: 10px;
            font-size: 1em;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.1);
            color: #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-container input:focus,
        .form-container select:focus {
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
            margin-top: 30px;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 6px 20px rgba(8, 235, 243, 0.3);
        }

        .form-container button:hover {
            background: linear-gradient(135deg, #00bcd4, #0097a7);
            color: #001a33;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(8, 235, 243, 0.4);
        }

        .row {
            display: flex;
            gap: 20px;
        }

        .col {
            flex: 1;
        }

        .section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }

        .section-title {
            background: linear-gradient(135deg, #ffffff, #08ebf3);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 20px;
            font-size: 1.4em;
            font-weight: 700;
        }

        .address-fields {
            display: flex;
            gap: 15px;
        }

        .address-fields input:first-child {
            flex: 2;
        }

        .address-fields input:last-child {
            flex: 1;
        }

        .location-fields {
            display: flex;
            gap: 15px;
        }

        .location-fields input:first-child {
            flex: 2;
        }

        .location-fields select:last-child {
            flex: 1;
        }

        .cep-info {
            font-size: 0.85em;
            color: #08ebf3;
            margin-top: 8px;
            text-align: center;
            font-weight: 600;
        }

        .mensagem-erro {
            color: #ff6b6b;
            font-size: 0.9em;
            margin-top: 5px;
            display: none;
        }

        .erro-duplicado, .erro-idade {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: 2px solid #ff4444;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
            font-weight: 600;
            text-align: center;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
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
            
            .form-container {
                margin: 100px auto 40px;
                padding: 40px 25px;
            }
            
            .row, .address-fields, .location-fields {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-container h2 {
                font-size: 2em;
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
            
            .form-container {
                padding: 30px 20px;
            }
            
            .form-container h2 {
                font-size: 1.8em;
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
    <h2>📝 Cadastro</h2>
    
    <?php if ($erro_duplicado): ?>
        <div class="erro-duplicado">
            ❌ <strong>Dados já cadastrados:</strong> <?php echo $mensagem_erro; ?><br>
            Utilize dados diferentes ou recupere sua conta.
        </div>
    <?php endif; ?>
    
    <?php if ($erro_idade): ?>
        <div class="erro-idade">
            ⚠️ <strong>Idade insuficiente:</strong> É necessário ter 18 anos ou mais para se cadastrar.
        </div>
    <?php endif; ?>
    
    <form action="cadastro.php" method="post">
        <!-- Seção 1: Dados Pessoais -->
        <div class="section">
            <div class="section-title">👤 Dados Pessoais</div>
           
            <div class="row">
                <div class="col">
                    <input type="text" name="nome" placeholder="Nome completo" required>
                    <input type="number" name="idade" placeholder="Idade" min="1" max="120" required>
                </div>
                <div class="col">
                    <input type="text" name="cpf" placeholder="CPF (000.000.000-00)" maxlength="14" required>
                    <select name="sexo" required>
                        <option value="">Sexo</option>
                        <option value="M">Masculino</option>
                        <option value="F">Feminino</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Seção 2: Dados de Acesso -->
        <div class="section">
            <div class="section-title">🔐 Dados de Acesso</div>
           
            <input type="text" name="apelido" placeholder="Apelido (opcional)">
            <input type="email" name="email" placeholder="E-mail" required>
            <input type="text" name="celular" placeholder="Celular (00) 00000-0000" maxlength="15" required>
            <input type="password" name="senha" placeholder="Senha" required>
            <input type="password" name="confirmar_senha" placeholder="Confirmar senha" required>
        </div>

        <!-- Seção 3: Endereço -->
        <div class="section">
            <div class="section-title">🏠 Endereço</div>
           
            <input type="text" name="endereco" placeholder="Endereço (Rua, Av, etc.)" required>
           
            <div class="address-fields">
                <input type="text" name="cep" placeholder="CEP (00000-000)" maxlength="9" required>
                <input type="text" name="numero" placeholder="Número" required>
            </div>
            <div class="cep-info">🔍 O endereço será preenchido automaticamente ao digitar o CEP</div>

            <div class="location-fields">
                <input type="text" name="cidade" placeholder="Cidade" required>
                <select name="estado" required>
                    <option value="">Estado</option>
                    <!-- Estados serão preenchidos via JavaScript -->
                </select>
            </div>
        </div>

        <button type="submit">🚀 Confirmar Cadastro</button>
    </form>
</div>

<script>
    // Função para formatar CPF
    function formatarCPF(cpf) {
        cpf = cpf.replace(/\D/g, '');
        cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
        cpf = cpf.replace(/(\d{3})(\d)/, '$1.$2');
        cpf = cpf.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        return cpf;
    }

    // Função para formatar Celular
    function formatarCelular(celular) {
        celular = celular.replace(/\D/g, '');
        celular = celular.replace(/^(\d{2})(\d)/g, '($1) $2');
        celular = celular.replace(/(\d)(\d{4})$/, '$1-$2');
        return celular;
    }

    // Função para formatar CEP
    function formatarCEP(cep) {
        cep = cep.replace(/\D/g, '');
        cep = cep.replace(/^(\d{5})(\d)/, '$1-$2');
        return cep;
    }

    // Buscar endereço via API do ViaCEP
    function buscarEnderecoPorCEP() {
        const cep = document.querySelector('input[name="cep"]').value.replace(/\D/g, '');
        
        if (cep.length === 8) {
            fetch(`https://viacep.com.br/ws/${cep}/json/`)
                .then(response => response.json())
                .then(data => {
                    if (!data.erro) {
                        document.querySelector('input[name="endereco"]').value = data.logradouro || '';
                        document.querySelector('input[name="cidade"]').value = data.localidade || '';
                        document.querySelector('select[name="estado"]').value = data.uf || '';
                        
                        // Focar no número se o endereço foi preenchido
                        if (data.logradouro) {
                            document.querySelector('input[name="numero"]').focus();
                        }
                    }
                })
                .catch(error => {
                    console.log('Erro ao buscar CEP:', error);
                });
        }
    }

    // Aplicar máscaras nos campos
    document.addEventListener('DOMContentLoaded', function() {
        const cpfInput = document.querySelector('input[name="cpf"]');
        const celularInput = document.querySelector('input[name="celular"]');
        const cepInput = document.querySelector('input[name="cep"]');

        cpfInput.addEventListener('input', function() {
            this.value = formatarCPF(this.value);
            if (this.value.length > 14) {
                this.value = this.value.slice(0, 14);
            }
        });

        celularInput.addEventListener('input', function() {
            this.value = formatarCelular(this.value);
            if (this.value.length > 15) {
                this.value = this.value.slice(0, 15);
            }
        });

        cepInput.addEventListener('input', function() {
            this.value = formatarCEP(this.value);
            if (this.value.length > 9) {
                this.value = this.value.slice(0, 9);
            }
            
            // Buscar endereço quando CEP estiver completo
            if (this.value.length === 9) {
                buscarEnderecoPorCEP();
            }
        });

        // Lista de estados brasileiros
        const estados = [
            'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA', 
            'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN', 
            'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
        ];
        
        const estadoSelect = document.querySelector('select[name="estado"]');
        estados.forEach(estado => {
            const option = document.createElement('option');
            option.value = estado;
            option.textContent = estado;
            estadoSelect.appendChild(option);
        });
    });
</script>

</body>
</html>