<?php
header('Content-Type: application/json');

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Lade MariaDB-Konfiguration
    $config = json_decode(file_get_contents("$configDir/mariadb-config.json"), true);
    if ($config === null) {
        throw new Exception('Failed to load configuration');
    }
    
    // Kopiere Trigger-Datei
    if (!copy('../sql/create_triggers.sql', "$configDir/create_triggers.sql")) {
        throw new Exception('Failed to copy trigger file');
    }
    
    // Importiere Trigger
    $result = execCommand("docker exec -i mariadb mysql -uroot -p{$config['root_password']} partdb < $configDir/create_triggers.sql");
    if (!$result['success']) {
        throw new Exception('Failed to import triggers: ' . $result['output']);
    }
    
    // Lösche temporäre Datei
    unlink("$configDir/create_triggers.sql");
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 