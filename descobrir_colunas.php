<?php
// Descobrir colunas da tabela
try {
    $pdo = new PDO(
        'pgsql:host=127.0.0.1;port=5432;dbname=chatbot',
        'postgres',
        '1234'
    );
    
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'agente_gerados'");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📊 COLUNAS DA TABELA agente_gerados:\n";
    echo "─────────────────────────────────────\n";
    foreach($cols as $col) {
        echo "  - " . $col['column_name'] . "\n";
    }
    echo "\n";
    
    // Agora buscar com as colunas corretas
    $stmt2 = $pdo->query('SELECT * FROM agente_gerados LIMIT 1');
    $sample = $stmt2->fetch(PDO::FETCH_ASSOC);
    
    echo "📋 AMOSTRA DOS DADOS:\n";
    echo "─────────────────────────────────────\n";
    if ($sample) {
        foreach ($sample as $key => $value) {
            echo "  $key: " . ($value ?? 'NULL') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
