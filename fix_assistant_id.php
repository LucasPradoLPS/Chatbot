<?php

$config = [
    'driver' => 'pgsql',
    'host' => '127.0.0.1',
    'port' => 5432,
    'database' => 'chatbot',
    'username' => 'postgres',
    'password' => '1234'
];

try {
    $pdo = new PDO(
        'pgsql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['database'],
        $config['username'],
        $config['password']
    );

    // Atualizar o assistant_id correto para o agente da empresa 2
    $stmt = $pdo->prepare('UPDATE agente_gerados SET assistant_id = :assistant_id WHERE empresa_id = 2');
    $stmt->execute([':assistant_id' => 'asst_TK2zcCJXJE7reRvMIY0Vw4im']);
    
    echo "✓ AgenteGerado atualizado com o assistant_id correto\n";
    
    // Verificar
    $result = $pdo->query('SELECT * FROM agente_gerados WHERE empresa_id = 2');
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "ID: " . $row['id'] . ", Assistant ID: " . $row['assistant_id'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
