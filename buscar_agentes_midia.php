<?php
// Script para buscar agentes com capacidades de mídia
try {
    $pdo = new PDO(
        'pgsql:host=127.0.0.1;port=5432;dbname=chatbot',
        'postgres',
        '1234'
    );
    
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Verificar todas as tabelas relacionadas
    echo "📊 TABELAS DO BANCO DE DADOS\n";
    echo "─────────────────────────────────────\n";
    
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public'
        ORDER BY table_name
    ");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tables as $table) {
        echo "  - " . $table['table_name'] . "\n";
    }
    
    echo "\n📋 VERIFICANDO ESTRUTURA DE AGENTES\n";
    echo "─────────────────────────────────────\n";
    
    // Ver se existe tabela 'agentes' (singular)
    $stmt = $pdo->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_name LIKE '%agent%'
    ");
    $agent_tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($agent_tables as $table) {
        echo "Tabela: " . $table['table_name'] . "\n";
        
        // Ver colunas
        $cols_stmt = $pdo->query("
            SELECT column_name 
            FROM information_schema.columns 
            WHERE table_name = '" . $table['table_name'] . "'
        ");
        $cols = $cols_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($cols as $col) {
            echo "  - " . $col['column_name'] . "\n";
        }
        echo "\n";
    }
    
    // Buscar dados completos de agentes
    echo "📌 AGENTES COM DETALHES\n";
    echo "─────────────────────────────────────\n";
    
    $stmt = $pdo->query('SELECT * FROM agente_gerados ORDER BY id DESC');
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($agents as $agent) {
        echo "\nAgente #" . $agent['id'] . ":\n";
        foreach ($agent as $key => $value) {
            echo "  $key: " . ($value ?? 'NULL') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
