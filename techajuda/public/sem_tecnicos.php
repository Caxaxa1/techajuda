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
    <title>Sem Técnicos - TechAjuda</title>
    <style>
        body {
            background-color: #000;
            color: #fff;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        
        .mensagem-container {
            padding: 40px;
            border: 2px solid #08ebf3;
            border-radius: 10px;
            max-width: 600px;
        }
        
        h1 {
            color: #08ebf3;
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        p {
            font-size: 1.2em;
            margin-bottom: 30px;
        }
        
        .botao-voltar {
            display: inline-block;
            padding: 12px 30px;
            background-color: #08ebf3;
            color: #001a66;
            text-decoration: none;
            font-weight: bold;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .botao-voltar:hover {
            background-color: #007acc;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="mensagem-container">
        <h1>Não há técnicos disponíveis ainda</h1>
        <p>No momento, não encontramos técnicos cadastrados em nossa plataforma para atender sua solicitação.</p>
        <a href="area_logada.php" class="botao-voltar">Voltar</a>
    </div>
</body>
</html>