<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id']) || !isset($_POST['tecnico_id']) || !isset($_POST['acao'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

$tecnico_id = intval($_POST['tecnico_id']);
$usuario_id = $_SESSION['usuario_id'];
$acao = $_POST['acao'];

$conn = getDBConnection();

if ($acao === 'adicionar') {
    $sql = "INSERT INTO favoritos (usuario_id, tecnico_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $usuario_id, $tecnico_id);
} else {
    $sql = "DELETE FROM favoritos WHERE usuario_id = ? AND tecnico_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $usuario_id, $tecnico_id);
}

if ($stmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro ao processar favorito']);
}

$stmt->close();
$conn->close();
?>