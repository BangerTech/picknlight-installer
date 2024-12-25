<?php
header('Content-Type: application/json');

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    error_log("Command: $command");
    error_log("Output: " . implode("\n", $output));
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

function waitForMariaDB($config, $maxAttempts = 60) {
    for ($i = 1; $i <= $maxAttempts; $i++) {
        error_log("Waiting for MariaDB... Attempt $i of $maxAttempts");
        
        // Prüfe ob Container läuft und gesund ist
        $result = execCommand("docker inspect -f '{{.State.Health.Status}}' mariadb");
        if ($result['success'] && trim($result['output']) === 'healthy') {
            return true;
        }
        
        sleep(5);
    }
    return false;
}

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Lade Konfiguration
    $config = json_decode(file_get_contents("$configDir/mariadb-config.json"), true);
    if ($config === null) {
        throw new Exception('Failed to load configuration');
    }
    
    // Erstelle docker-compose.yml
    $template = file_get_contents('../templates/docker-compose-mariadb.yml');
    if ($template === false) {
        throw new Exception('Failed to read template file');
    }
    
    $replacements = [
        '{{ROOT_PASSWORD}}' => $config['root_password'],
        '{{DB_PASSWORD}}' => $config['db_password'],
        '{{PORT}}' => $config['port']
    ];
    
    foreach ($replacements as $key => $value) {
        $template = str_replace($key, $value, $template);
    }
    
    if (file_put_contents("$configDir/docker-compose-mariadb.yml", $template) === false) {
        throw new Exception('Failed to write docker-compose file');
    }
    
    // Starte Container
    $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml up -d");
    if (!$result['success']) {
        throw new Exception('Failed to start MariaDB: ' . $result['output']);
    }
    
    // Warte auf MariaDB
    if (!waitForMariaDB($config)) {
        throw new Exception('MariaDB did not become ready in time');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 