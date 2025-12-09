<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id']) || !isset($_POST['tecnico_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$tecnico_id = intval($_POST['tecnico_id']);
$usuario_id = $_SESSION['usuario_id'];

$conn = getDBConnection();

$sql = "DELETE FROM avaliacoes WHERE tecnico_id = ? AND cliente_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $tecnico_id, $usuario_id);

if ($stmt->execute()) {
    // Atualizar avaliação média do técnico
    $sql_media = "UPDATE tecnicos SET avaliacao_media = (
        SELECT COALESCE(AVG(nota), 0) FROM avaliacoes WHERE tecnico_id = ?
    ) WHERE id = ?";
    $stmt_media = $conn->prepare($sql_media);
    $stmt_media->bind_param('ii', $tecnico_id, $tecnico_id);
    $stmt_media->execute();
    $stmt_media->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao remover avaliação']);
}

$stmt->close();
$conn->close();
?>