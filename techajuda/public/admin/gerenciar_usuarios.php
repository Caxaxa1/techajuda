<?php
session_start();


// Verificação de acesso
if (!isset($_SESSION['admin_logado']) || $_SESSION['admin_logado'] !== true) {
    header("Location: area_admin.php");
    exit();
}


// ... (mantenha todo o resto do código existente) ...


// No final do arquivo, na parte do logout, atualize:
if (isset($_GET['sair'])) {
    unset($_SESSION['admin_logado']);
    header("Location: area_admin.php");
    exit();
}
?>

