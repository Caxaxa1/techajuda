<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

$mensagem = '';
$erro = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['foto_perfil'])) {
    $conn = getDBConnection();
    $usuario_id = $_SESSION['usuario_id'];
    
    // Primeiro, buscar a foto atual para deletar depois
    $sql_foto_atual = "SELECT foto_perfil FROM usuarios WHERE id = $usuario_id";
    $result_foto = $conn->query($sql_foto_atual);
    $foto_antiga = null;
    
    if ($result_foto->num_rows > 0) {
        $usuario = $result_foto->fetch_assoc();
        $foto_antiga = $usuario['foto_perfil'];
    }
    
    // Configurações do upload
    $diretorio = "../uploads/perfis/";
    $extensoes_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    $tamanho_maximo = 5 * 1024 * 1024; // 5MB
    
    // Criar diretório se não existir
    if (!file_exists($diretorio)) {
        mkdir($diretorio, 0777, true);
    }
    
    $arquivo = $_FILES['foto_perfil'];
    $nome_arquivo = $arquivo['name'];
    $tipo_arquivo = $arquivo['type'];
    $tamanho_arquivo = $arquivo['size'];
    $arquivo_tmp = $arquivo['tmp_name'];
    $erro_arquivo = $arquivo['error'];
    
    // Verificar erros no upload
    if ($erro_arquivo !== UPLOAD_ERR_OK) {
        $erro = true;
        $mensagem = "Erro no upload do arquivo. Código: $erro_arquivo";
    }
    // Verificar tamanho
    elseif ($tamanho_arquivo > $tamanho_maximo) {
        $erro = true;
        $mensagem = "Arquivo muito grande. Tamanho máximo: 5MB";
    }
    // Verificar extensão
    else {
        $extensao = strtolower(pathinfo($nome_arquivo, PATHINFO_EXTENSION));
        if (!in_array($extensao, $extensoes_permitidas)) {
            $erro = true;
            $mensagem = "Formato não permitido. Use: JPG, JPEG, PNG ou GIF";
        }
    }
    
    if (!$erro) {
        // Gerar nome único para o arquivo
        $novo_nome = "perfil_" . $usuario_id . "_" . time() . "." . $extensao;
        $caminho_completo = $diretorio . $novo_nome;
        
        // Mover arquivo
        if (move_uploaded_file($arquivo_tmp, $caminho_completo)) {
    // Atualizar banco de dados
    $caminho_bd = "uploads/perfis/" . $novo_nome;
    $sql = "UPDATE usuarios SET foto_perfil = '$caminho_bd' WHERE id = $usuario_id";
   
    if ($conn->query($sql)) {
        // DELETAR FOTO ANTIGA se existir
        if ($foto_antiga && file_exists("../" . $foto_antiga)) {
            unlink("../" . $foto_antiga);
        }
       
        // CORREÇÃO: Atualizar a foto na sessão também
        $_SESSION['usuario_foto'] = $caminho_bd;
       
        $mensagem = "Foto de perfil atualizada com sucesso!";
    } else {
                $erro = true;
                $mensagem = "Erro ao atualizar banco de dados: " . $conn->error;
                // Remover arquivo se deu erro no BD
                unlink($caminho_completo);
            }
        } else {
            $erro = true;
            $mensagem = "Erro ao mover arquivo";
        }
    }
    
    $conn->close();
    
    // Redirecionar de volta para minha_conta.php com mensagem
    header("Location: minha_conta.php?mensagem=" . urlencode($mensagem) . "&erro=" . ($erro ? '1' : '0'));
    exit();
}

header("Location: minha_conta.php");
exit();