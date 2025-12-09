<?php
session_start();
require_once "../src/config.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: entrar.php");
    exit();
}

$conn = getDBConnection();

// Verificar todos os técnicos cadastrados
echo "<h2>Diagnóstico do Banco de Dados - Técnicos</h2>";

// 1. Verificar técnicos na tabela tecnicos
$sql_tecnicos = "SELECT t.id, u.nome, t.status, t.data_cadastro 
                 FROM tecnicos t 
                 INNER JOIN usuarios u ON t.usuario_id = u.id";
$result_tecnicos = $conn->query($sql_tecnicos);

echo "<h3>Técnicos Cadastrados:</h3>";
if ($result_tecnicos->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Status</th><th>Data Cadastro</th></tr>";
    while($row = $result_tecnicos->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nome'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . $row['data_cadastro'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhum técnico encontrado na tabela tecnicos.</p>";
}

// 2. Verificar especialidades cadastradas
$sql_especialidades = "SELECT et.tecnico_id, cs.nome as categoria_nome, u.nome as tecnico_nome
                       FROM especialidades_tecnico et
                       INNER JOIN categorias_servico cs ON et.categoria_id = cs.id
                       INNER JOIN tecnicos t ON et.tecnico_id = t.id
                       INNER JOIN usuarios u ON t.usuario_id = u.id";
$result_especialidades = $conn->query($sql_especialidades);

echo "<h3>Especialidades Cadastradas:</h3>";
if ($result_especialidades->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Técnico ID</th><th>Nome do Técnico</th><th>Especialidade</th></tr>";
    while($row = $result_especialidades->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['tecnico_id'] . "</td>";
        echo "<td>" . $row['tecnico_nome'] . "</td>";
        echo "<td>" . $row['categoria_nome'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhuma especialidade cadastrada.</p>";
}

// 3. Verificar categorias disponíveis
$sql_categorias = "SELECT id, nome, descricao FROM categorias_servico";
$result_categorias = $conn->query($sql_categorias);

echo "<h3>Categorias Disponíveis:</h3>";
if ($result_categorias->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Nome</th><th>Descrição</th></tr>";
    while($row = $result_categorias->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['nome'] . "</td>";
        echo "<td>" . $row['descricao'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>Nenhuma categoria cadastrada.</p>";
}

$conn->close();
?>

<br><br>
<a href="area_logada.php">Voltar para Área Logada</a>