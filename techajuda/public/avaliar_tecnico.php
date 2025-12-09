<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id']) || !isset($_POST['tecnico_id']) || !isset($_POST['nota'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$tecnico_id = intval($_POST['tecnico_id']);
$usuario_id = $_SESSION['usuario_id'];
$nota = floatval($_POST['nota']);
$comentario = isset($_POST['comentario']) ? trim($_POST['comentario']) : '';

if ($nota < 0.5 || $nota > 5.0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Nota inválida']);
    exit();
}

$conn = getDBConnection();

// Verificar se já existe avaliação
$sql_check = "SELECT id FROM avaliacoes WHERE tecnico_id = ? AND cliente_id = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param('ii', $tecnico_id, $usuario_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Atualizar avaliação existente
    $sql = "UPDATE avaliacoes SET nota = ?, comentario = ?, data_avaliacao = CURRENT_TIMESTAMP WHERE tecnico_id = ? AND cliente_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('dsii', $nota, $comentario, $tecnico_id, $usuario_id);
} else {
    // Inserir nova avaliação
    $sql = "INSERT INTO avaliacoes (tecnico_id, cliente_id, nota, comentario) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('iids', $tecnico_id, $usuario_id, $nota, $comentario);
}

if ($stmt->execute()) {
    // Atualizar avaliação média do técnico
    $sql_media = "UPDATE tecnicos SET avaliacao_media = (
        SELECT AVG(nota) FROM avaliacoes WHERE tecnico_id = ?
    ) WHERE id = ?";
    $stmt_media = $conn->prepare($sql_media);
    $stmt_media->bind_param('ii', $tecnico_id, $tecnico_id);
    $stmt_media->execute();
    $stmt_media->close();
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar avaliação']);
}

$stmt->close();
$conn->close();
?>