<?php
// config.php - Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // substitua pelo seu usuário MySQL
define('DB_PASS', ''); // substitua pela sua senha MySQL
define('DB_NAME', 'techajuda');


function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
   
    if ($conn->connect_error) {
        die("Erro de conexão: " . $conn->connect_error);
    }
   
    return $conn;
}
?>
