<?php
session_start();


// Verificação de acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: area_admin.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TechAjuda</title>
    <link rel="stylesheet" href="../../visualscript/css/style.css">
    <style>
        body {
            background-color: #2a2a2a;
            color: #e0e0e0;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
       
        .admin-header {
            background-color: #1a1a1a;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #ff4444;
        }
       
        .admin-header h1 {
            color: #ff4444;
            margin: 0;
            font-size: 2.5em;
        }
       
        .admin-nav {
            background-color: #2a2a2a;
            padding: 15px;
            text-align: center;
            border-bottom: 1px solid #444;
        }
       
        .admin-nav a {
            color: #08ebf3;
            text-decoration: none;
            margin: 0 15px;
            font-weight: bold;
            padding: 8px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
       
        .admin-nav a:hover {
            background-color: #08ebf3;
            color: #2a2a2a;
        }
       
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
       
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }
       
        .dashboard-card {
            background: linear-gradient(135deg, #1a1a1a 0%, #2a2a2a 100%);
            padding: 40px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
            border: 2px solid #444;
            transition: all 0.3s;
            cursor: pointer;
        }
       
        .dashboard-card:hover {
            transform: translateY(-10px);
            border-color: #08ebf3;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }
       
        .card-icon {
            font-size: 4em;
            margin-bottom: 20px;
        }
       
        .card-title {
            color: #08ebf3;
            font-size: 1.8em;
            margin-bottom: 15px;
        }
       
        .card-description {
            color: #a0a0a0;
            font-size: 1.1em;
            line-height: 1.5;
        }
       
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
       
        .stat-card {
            background-color: #1a1a1a;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            border-left: 4px solid #08ebf3;
        }
       
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #08ebf3;
            margin: 10px 0;
        }
       
        .admin-welcome {
            text-align: center;
            margin-bottom: 40px;
        }
       
        .admin-welcome h2 {
            color: #08ebf3;
            font-size: 2em;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <h1>🔧 Dashboard Administrativo - TechAjuda</h1>
        <p>Gestão Completa do Sistema</p>
    </div>
   
    <div class="admin-nav">
        <a href="dashboard_admin.php">Dashboard</a>
        <a href="gerenciar_usuarios.php">Gerenciar Usuários</a>
        <a href="gerenciar_tecnicos.php">Gerenciar Técnicos</a>
        <a href="../minha_conta.php">← Voltar para Minha Conta</a>
        <a href="?sair=1">Sair do Admin</a>
    </div>
   
    <div class="container">
        <div class="admin-welcome">
            <h2>Bem-vindo, Administrador!</h2>
            <p>Selecione uma das opções abaixo para gerenciar o sistema</p>
        </div>
       
        <div class="stats">
            <?php
            require_once "../../src/config.php";
            $conn = getDBConnection();
           
            // Contar usuários
            $sql_usuarios = "SELECT COUNT(*) as total FROM usuarios";
            $result_usuarios = $conn->query($sql_usuarios);
            $total_usuarios = $result_usuarios->fetch_assoc()['total'];
           
            // Contar técnicos
            $sql_tecnicos = "SELECT COUNT(*) as total FROM tecnicos";
            $result_tecnicos = $conn->query($sql_tecnicos);
            $total_tecnicos = $result_tecnicos->fetch_assoc()['total'];
           
            // Contar técnicos aprovados
            $sql_aprovados = "SELECT COUNT(*) as total FROM tecnicos WHERE status = 'aprovado'";
            $result_aprovados = $conn->query($sql_aprovados);
            $total_aprovados = $result_aprovados->fetch_assoc()['total'];
           
            $conn->close();
            ?>
           
            <div class="stat-card">
                <div>Total de Usuários</div>
                <div class="stat-number"><?php echo $total_usuarios; ?></div>
            </div>
            <div class="stat-card">
                <div>Técnicos Cadastrados</div>
                <div class="stat-number"><?php echo $total_tecnicos; ?></div>
            </div>
            <div class="stat-card">
                <div>Técnicos Aprovados</div>
                <div class="stat-number"><?php echo $total_aprovados; ?></div>
            </div>
        </div>
       
        <div class="dashboard-cards">
            <a href="gerenciar_usuarios.php" style="text-decoration: none;">
                <div class="dashboard-card">
                    <div class="card-icon">👥</div>
                    <div class="card-title">Gerenciar Usuários</div>
                    <div class="card-description">
                        Visualize, edite e gerencie todos os usuários do sistema.
                        Acesso completo aos dados cadastrais e histórico.
                    </div>
                </div>
            </a>
           
            <a href="gerenciar_tecnicos.php" style="text-decoration: none;">
                <div class="dashboard-card">
                    <div class="card-icon">🔧</div>
                    <div class="card-title">Gerenciar Técnicos</div>
                    <div class="card-description">
                        Gerencie técnicos cadastrados, visualize dados completos,
                        edite informações e controle status de aprovação.
                    </div>
                </div>
            </a>
        </div>
    </div>
   
    <script>
        // Adicionar efeitos interativos
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px)';
                });
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>


<?php
// Logout
if (isset($_GET['sair'])) {
    unset($_SESSION['admin_logado']);
    header("Location: area_admin.php");
    exit();
}
