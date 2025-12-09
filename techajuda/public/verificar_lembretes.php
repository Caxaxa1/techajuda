<?php
require_once "../src/config.php";

$conn = getDBConnection();

// Buscar lembretes ativos que devem ser enviados agora
$sql = "SELECT 
            la.*,
            a.*,
            u.nome as cliente_nome, 
            u.email as cliente_email, 
            t.usuario_id as tecnico_user_id, 
            ut.nome as tecnico_nome,
            ut.email as tecnico_email
        FROM lembretes_agendamento la
        INNER JOIN agendamentos a ON la.agendamento_id = a.id
        INNER JOIN usuarios u ON a.cliente_id = u.id
        INNER JOIN tecnicos t ON a.tecnico_id = t.id
        INNER JOIN usuarios ut ON t.usuario_id = ut.id
        WHERE la.ativo = 1
        AND a.status IN ('agendado', 'confirmado')
        AND DATE_ADD(a.data_agendamento, INTERVAL -la.minutos_antes MINUTE) <= NOW()
        AND la.enviado = 0";

$result = $conn->query($sql);
$lembretes = $result->fetch_all(MYSQLI_ASSOC);

foreach ($lembretes as $lembrete) {
    // Enviar lembrete para o cliente
    enviarLembreteCliente($lembrete);
    
    // Enviar lembrete para o técnico
    enviarLembreteTecnico($lembrete);
    
    // Marcar como enviado (precisa adicionar esta coluna)
    $sql_update = "UPDATE lembretes_agendamento SET enviado = 1, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    $stmt->bind_param('i', $lembrete['id']);
    $stmt->execute();
    $stmt->close();
    
    error_log("Lembrete enviado: Agendamento ID " . $lembrete['agendamento_id'] . " - " . $lembrete['minutos_antes'] . "min antes");
}

$conn->close();

function enviarLembreteCliente($lembrete) {
    $minutos = $lembrete['minutos_antes'];
    $tempo_texto = $minutos >= 1440 ? floor($minutos/1440) . " dias" : 
                  ($minutos >= 60 ? floor($minutos/60) . " horas" : $minutos . " minutos");
    
    $mensagem = "🔔 Lembrete de Agendamento - TechAjuda\n\n";
    $mensagem .= "Olá " . $lembrete['cliente_nome'] . "!\n\n";
    $mensagem .= "Lembrete: Seu agendamento está programado para daqui a " . $tempo_texto . "!\n\n";
    $mensagem .= "📅 Serviço: " . $lembrete['titulo'] . "\n";
    $mensagem .= "🗓️ Data: " . date('d/m/Y', strtotime($lembrete['data_agendamento'])) . "\n";
    $mensagem .= "⏰ Horário: " . date('H:i', strtotime($lembrete['hora_inicio'])) . "\n";
    $mensagem .= "👨‍🔧 Técnico: " . $lembrete['tecnico_nome'] . "\n";
    $mensagem .= "📍 Local: " . ($lembrete['local_atendimento'] === 'local_tecnico' ? 'Local do Técnico' : 'Seu Local') . "\n\n";
    $mensagem .= "Por favor, esteja disponível no horário agendado!\n\n";
    $mensagem .= "Atenciosamente,\nEquipe TechAjuda";
    
    error_log("LEMBRETE CLIENTE [" . $tempo_texto . "]: " . $lembrete['cliente_email'] . " - " . $lembrete['titulo']);
}

function enviarLembreteTecnico($lembrete) {
    $minutos = $lembrete['minutos_antes'];
    $tempo_texto = $minutos >= 1440 ? floor($minutos/1440) . " dias" : 
                  ($minutos >= 60 ? floor($minutos/60) . " horas" : $minutos . " minutos");
    
    $mensagem = "🔔 Lembrete de Agendamento - TechAjuda\n\n";
    $mensagem .= "Olá " . $lembrete['tecnico_nome'] . "!\n\n";
    $mensagem .= "Lembrete: Seu agendamento está programado para daqui a " . $tempo_texto . "!\n\n";
    $mensagem .= "📅 Serviço: " . $lembrete['titulo'] . "\n";
    $mensagem .= "🗓️ Data: " . date('d/m/Y', strtotime($lembrete['data_agendamento'])) . "\n";
    $mensagem .= "⏰ Horário: " . date('H:i', strtotime($lembrete['hora_inicio'])) . "\n";
    $mensagem .= "👤 Cliente: " . $lembrete['cliente_nome'] . "\n";
    $mensagem .= "📍 Local: " . ($lembrete['local_atendimento'] === 'local_tecnico' ? 'Seu Local' : 'Local do Cliente') . "\n\n";
    
    if (!empty($lembrete['descricao'])) {
        $mensagem .= "📝 Descrição: " . $lembrete['descricao'] . "\n\n";
    }
    
    if ($lembrete['valor']) {
        $mensagem .= "💰 Valor: R$ " . number_format($lembrete['valor'], 2, ',', '.') . "\n\n";
    }
    
    $mensagem .= "Não se esqueça do seu compromisso!\n\n";
    $mensagem .= "Atenciosamente,\nEquipe TechAjuda";
    
    error_log("LEMBRETE TÉCNICO [" . $tempo_texto . "]: " . $lembrete['tecnico_email'] . " - " . $lembrete['titulo']);
}
?>