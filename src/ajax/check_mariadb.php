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

function waitForConnection($config, $maxAttempts = 30) {
    for ($i = 1; $i <= $maxAttempts; $i++) {
        error_log("Connection attempt $i of $maxAttempts");
        
        // Warte erst mal, bis der Container wirklich bereit ist
        sleep(2);
        
        $result = execCommand("docker exec mariadb /usr/bin/mariadb -u root -p{$config['root_password']} --protocol=tcp -h 127.0.0.1 -e 'SELECT 1'");
        if ($result['success']) {
            error_log("Successfully connected to MariaDB");
            return true;
        }
        
        error_log("Connection failed, retrying...");
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
    
    // Prüfe Container-Status
    $result = execCommand("docker inspect -f '{{.State.Health.Status}}' mariadb");
    $status = trim($result['output']);
    error_log("MariaDB health status: " . $status);
    
    if (!$result['success'] || !in_array($status, ['healthy', 'starting'])) {
        throw new Exception('MariaDB container is not healthy');
    }
    
    // Versuche mehrmals eine Verbindung herzustellen
    if (!waitForConnection($config)) {
        throw new Exception('Could not connect to MariaDB: ' . $result['output']);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 