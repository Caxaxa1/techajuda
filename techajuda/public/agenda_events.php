<?php
session_start();
require_once "../src/config.php";

// Configurar headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Desativar exibição de erros para não quebrar o JSON
ini_set('display_errors', 0);
error_reporting(0);

$conn = getDBConnection();

// Verificar se a conexão foi bem sucedida
if (!$conn) {
    echo json_encode(['error' => 'Erro de conexão com o banco']);
    exit();
}

try {
    // Verificar se está visualizando agenda de outro técnico
    $visualizando_outro_tecnico = isset($_GET['visualizando_outro_tecnico']) && $_GET['visualizando_outro_tecnico'] == 'true';

    // Determinar de onde buscar os eventos
    if (isset($_GET['tecnico_id'])) {
        $tecnico_id = intval($_GET['tecnico_id']);
        
        // Se está visualizando outro técnico, mostrar apenas eventos confirmados/concluídos
        if ($visualizando_outro_tecnico) {
            $sql = "SELECT 
                        a.*,
                        u.nome as cliente_nome,
                        t.usuario_id as tecnico_user_id
                     FROM agendamentos a
                     INNER JOIN usuarios u ON a.cliente_id = u.id
                     INNER JOIN tecnicos t ON a.tecnico_id = t.id
                     WHERE a.tecnico_id = ? AND a.status IN ('agendado', 'confirmado', 'concluido')
                     ORDER BY a.data_agendamento, a.hora_inicio";
        } else {
            // Agenda normal do técnico - mostrar TODOS os eventos
            $sql = "SELECT 
                        a.*,
                        u.nome as cliente_nome,
                        t.usuario_id as tecnico_user_id
                     FROM agendamentos a
                     INNER JOIN usuarios u ON a.cliente_id = u.id
                     INNER JOIN tecnicos t ON a.tecnico_id = t.id
                     WHERE a.tecnico_id = ?
                     ORDER BY a.data_agendamento, a.hora_inicio";
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $tecnico_id);
        
    } elseif (isset($_GET['cliente_id'])) {
        // Agenda do cliente - MOSTRAR TODOS OS AGENDAMENTOS
        $cliente_id = intval($_GET['cliente_id']);
        $sql = "SELECT 
                    a.*,
                    u.nome as tecnico_nome,
                    t.usuario_id as tecnico_user_id
                 FROM agendamentos a
                 INNER JOIN tecnicos t ON a.tecnico_id = t.id
                 INNER JOIN usuarios u ON t.usuario_id = u.id
                 WHERE a.cliente_id = ?
                 ORDER BY a.data_agendamento, a.hora_inicio";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $cliente_id);
    } else {
        echo json_encode([]);
        exit();
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $agendamentos = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $events = [];

    foreach ($agendamentos as $agendamento) {
        // Verificar se data e hora existem
        if (empty($agendamento['data_agendamento']) || empty($agendamento['hora_inicio'])) {
            continue;
        }
        
        // Definir cor baseada no status
        $backgroundColor = '';
        switch ($agendamento['status']) {
            case 'pendente_confirmacao':
                $backgroundColor = '#ff9800';
                break;
            case 'agendado':
                $backgroundColor = '#ff9800';
                break;
            case 'confirmado':
                $backgroundColor = '#4caf50';
                break;
            case 'concluido':
                $backgroundColor = '#2196f3';
                break;
            default:
                $backgroundColor = '#666666';
        }
        
        $event = [
            'id' => $agendamento['id'],
            'title' => $agendamento['titulo'] ?: 'Sem título',
            'start' => $agendamento['data_agendamento'] . 'T' . $agendamento['hora_inicio'],
            'end' => $agendamento['data_agendamento'] . 'T' . $agendamento['hora_fim'],
            'backgroundColor' => $backgroundColor,
            'borderColor' => $backgroundColor,
            'textColor' => '#ffffff',
            'extendedProps' => [
                'descricao' => $agendamento['descricao'] ?? '',
                'status' => $agendamento['status'],
                'local' => $agendamento['local_atendimento'],
                'cliente_nome' => $agendamento['cliente_nome'] ?? null,
                'tecnico_nome' => $agendamento['tecnico_nome'] ?? null,
                'valor' => $agendamento['valor'],
                'visualizando_outro_tecnico' => $visualizando_outro_tecnico
            ]
        ];
        
        $events[] = $event;
    }

    $conn->close();
    
    // Retornar JSON válido
    echo json_encode($events);
    
} catch (Exception $e) {
    // Em caso de erro, retornar JSON vazio
    echo json_encode([]);
}
?>