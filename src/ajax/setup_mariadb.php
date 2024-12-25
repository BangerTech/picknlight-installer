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
        
        // Prüfe ob Container läuft
        $result = execCommand("docker ps --filter 'name=mariadb' --format '{{.Status}}'");
        if (!$result['success'] || empty($result['output'])) {
            sleep(1);
            continue;
        }
        
        // Versuche eine Verbindung zur Datenbank
        $result = execCommand("docker exec mariadb mysqladmin ping -h localhost -u root -p{$config['root_password']} --silent");
        if ($result['success']) {
            return true;
        }
        
        sleep(1);
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
    
    // Erstelle Verzeichnisse
    $directories = [
        "$configDir/mariadb/data"
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
        chmod($dir, 0777);
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