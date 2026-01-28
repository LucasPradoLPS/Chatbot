<?php
// Script para listar os agentes com detalhes
try {
    $pdo = new PDO(
        'pgsql:host=127.0.0.1;port=5432;dbname=chatbot',
        'postgres',
        '1234'
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar agentes com empresa info
    $stmt = $pdo->query('
        SELECT 
            ag.id,
            ag.funcao,
            ag.agente_base_id,
            ag.assistant_id,
            ag.created_at,
            ag.updated_at,
            emp.nome as empresa_nome
        FROM agente_gerados ag
        LEFT JOIN empresas emp ON ag.empresa_id = emp.id
        ORDER BY ag.created_at DESC
    ');
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║              SEUS AGENTES CADASTRADOS                  ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";
    
    foreach ($agents as $idx => $agent) {
        echo ($idx + 1) . "️⃣ AGENTE #" . $agent['id'] . "\n";
        echo "───────────────────────────────────────────────────────\n";
        echo "   Empresa: " . ($agent['empresa_nome'] ?? 'N/A') . "\n";
        echo "   Função: " . ($agent['funcao'] ?? 'N/A') . "\n";
        echo "   Base ID: " . ($agent['agente_base_id'] ?? 'N/A') . "\n";
        echo "   Assistant ID: " . ($agent['assistant_id'] ?? '❌ NÃO ATRIBUÍDO') . "\n";
        echo "   Criado em: " . ($agent['created_at'] ?? 'N/A') . "\n";
        echo "\n";
    }
    
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║  Total: " . count($agents) . " agente(s)                              ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
