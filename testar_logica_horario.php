<?php
require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\Log;

echo "Testando lógica de horário de atendimento:\n";
echo "==========================================\n\n";

// Testador timezone
$timezones = [
    'America/Sao_Paulo',
    'UTC'
];

foreach ($timezones as $tz) {
    $agora = now($tz);
    $dia_semana = $agora->dayOfWeek;
    $hora_atual = $agora->hour;
    $dias = ['domingo', 'segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado'];
    
    $eh_fim_semana = $dia_semana == 0 || $dia_semana == 6;
    $fora_horario = $hora_atual < 8 || $hora_atual >= 17;
    $atendimento_ativo = !($eh_fim_semana || $fora_horario);
    
    echo "Timezone: $tz\n";
    echo "Data/Hora: " . $agora->format('d/m/Y H:i:s') . "\n";
    echo "Dia: " . $dias[$dia_semana] . "\n";
    echo "Hora: {$hora_atual}h\n";
    echo "Fim de semana? " . ($eh_fim_semana ? 'SIM' : 'NÃO') . "\n";
    echo "Fora do horário (antes 08h ou depois 17h)? " . ($fora_horario ? 'SIM' : 'NÃO') . "\n";
    echo "Atendimento ativo? " . ($atendimento_ativo ? 'SIM ✅' : 'NÃO ❌ - Responder com horário') . "\n";
    echo "---\n\n";
}

echo "LÓGICA DE IMPLEMENTAÇÃO:\n";
echo "1. Se é fim de semana OU fora do horário -> Enviar mensagem de horário\n";
echo "2. Caso contrário -> Processar com a IA normalmente\n\n";

echo "HORÁRIO DE ATENDIMENTO:\n";
echo "🕗 Segunda a sexta-feira, das 08h às 17h\n";
