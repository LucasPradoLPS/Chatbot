<?php
// Verificar tabela 'agentes' completa
try {
    $pdo = new PDO(
        'pgsql:host=127.0.0.1;port=5432;dbname=chatbot',
        'postgres',
        '1234'
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Ver todos os campos da tabela agentes
    $stmt = $pdo->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_name = 'agentes'
        ORDER BY ordinal_position
    ");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 ESTRUTURA DA TABELA 'agentes'\n";
    echo "─────────────────────────────────────\n";
    foreach ($cols as $col) {
        echo "  - " . $col['column_name'] . " (" . $col['data_type'] . ")\n";
    }
    
    // Buscar dados
    echo "\n\n📌 DADOS DA TABELA 'agentes'\n";
    echo "─────────────────────────────────────\n";
    
    $stmt = $pdo->query('
        SELECT 
            a.id,
            a.empresa_id,
            a.ia_ativa,
            a.responder_grupo,
            a.created_at,
            a.updated_at,
            e.nome as empresa_nome
        FROM agentes a
        LEFT JOIN empresas e ON a.empresa_id = e.id
        ORDER BY a.id DESC
    ');
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($agents as $idx => $agent) {
        echo "\n" . ($idx + 1) . "️⃣ Agente #" . $agent['id'] . "\n";
        echo "───────────────────────\n";
        echo "   Empresa: " . $agent['empresa_nome'] . "\n";
        echo "   IA Ativa: " . ($agent['ia_ativa'] ? '✅' : '❌') . "\n";
        echo "   Responder Grupo: " . ($agent['responder_grupo'] ? '✅' : '❌') . "\n";
        echo "   Criado: " . $agent['created_at'] . "\n";
    }
    
    // Procurar por registros que possam indicar suporte a mídia
    echo "\n\n🔍 PROCURANDO AGENTES COM SUPORTE A MÍDIA\n";
    echo "─────────────────────────────────────\n";
    
    // Buscar em todos os campos text/json
    $search_tables = [
        'agente_gerados',
        'ia_intervencoes',
        'event_logs'
    ];
    
    echo "Procurando por 'imagem', 'pdf', 'arquivo', 'media', 'vision'...\n";
    
    // Verificar em ia_intervencoes
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'ia_intervencoes'");
    $cols_inter = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n📄 Tabela: ia_intervencoes\n";
    foreach ($cols_inter as $col) {
        echo "  - " . $col['column_name'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
