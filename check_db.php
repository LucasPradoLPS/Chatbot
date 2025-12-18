<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== EMPRESAS ===" . PHP_EOL;
$empresas = \Illuminate\Support\Facades\DB::table('empresas')->get();
foreach ($empresas as $e) {
    echo "ID: " . $e->id . ", Nome: " . $e->nome . PHP_EOL;
}

echo PHP_EOL . "=== AGENTES ===" . PHP_EOL;
$agentes = \Illuminate\Support\Facades\DB::table('agentes')->get();
if (count($agentes) == 0) {
    echo "NENHUM AGENTE ENCONTRADO!" . PHP_EOL;
} else {
    foreach ($agentes as $a) {
        echo "ID: " . $a->id . ", Empresa: " . $a->empresa_id . ", IA Ativa: " . $a->ia_ativa . PHP_EOL;
    }
}

echo PHP_EOL . "=== AGENTES GERADOS ===" . PHP_EOL;
$agenteGerados = \Illuminate\Support\Facades\DB::table('agente_gerados')->get();
if (count($agenteGerados) == 0) {
    echo "NENHUM AGENTE GERADO ENCONTRADO!" . PHP_EOL;
} else {
    foreach ($agenteGerados as $ag) {
        echo "ID: " . $ag->id . ", Empresa: " . $ag->empresa_id . ", Funcao: " . $ag->funcao . ", Assistant ID: " . $ag->agente_base_id . PHP_EOL;
    }
}

echo PHP_EOL . "=== INSTÂNCIAS WHATSAPP ===" . PHP_EOL;
$instancias = \Illuminate\Support\Facades\DB::table('instancia_whatsapps')->get();
if (count($instancias) == 0) {
    echo "NENHUMA INSTÂNCIA ENCONTRADA!" . PHP_EOL;
} else {
    foreach ($instancias as $i) {
        echo "ID: " . $i->id . ", Nome: " . $i->instance_name . ", Empresa: " . $i->empresa_id . PHP_EOL;
    }
}

echo PHP_EOL . "=== DETALHES - AGENTES DA EMPRESA 2 (onde N8n está) ===" . PHP_EOL;
$agentes2 = \Illuminate\Support\Facades\DB::table('agentes')->where('empresa_id', 2)->get();
foreach ($agentes2 as $a) {
    echo "ID: " . $a->id . ", IA Ativa: " . ($a->ia_ativa ? 'SIM' : 'NÃO') . ", Responder Grupo: " . ($a->responder_grupo ? 'SIM' : 'NÃO') . PHP_EOL;
}
