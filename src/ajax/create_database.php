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

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Lade MariaDB-Konfiguration
    $config = json_decode(file_get_contents("$configDir/mariadb-config.json"), true);
    if ($config === null) {
        throw new Exception('Failed to load configuration');
    }
    
    // Erstelle die Datenbank
    $sql = "
    CREATE DATABASE IF NOT EXISTS partdb;
    USE partdb;
    
    CREATE TABLE IF NOT EXISTS led_mapping (
        part_id INT PRIMARY KEY,
        led_position INT NOT NULL,
        UNIQUE (led_position)
    );";
    
    // Schreibe SQL in temporäre Datei
    $tmpFile = "$configDir/create_database.sql";
    if (file_put_contents($tmpFile, $sql) === false) {
        throw new Exception('Failed to write SQL file');
    }
    
    // Warte bis MariaDB bereit ist
    $maxAttempts = 30;
    for ($i = 1; $i <= $maxAttempts; $i++) {
        error_log("Waiting for MariaDB... Attempt $i of $maxAttempts");
        
        $result = execCommand("docker exec mariadb mysqladmin ping -h localhost -u root -p{$config['root_password']} --silent");
        if ($result['success']) {
            break;
        }
        
        if ($i === $maxAttempts) {
            throw new Exception('MariaDB did not become ready in time');
        }
        
        sleep(1);
    }
    
    // Führe SQL aus
    $result = execCommand("docker exec -i mariadb mysql -uroot -p{$config['root_password']} < $configDir/create_database.sql");
    if (!$result['success']) {
        throw new Exception('Failed to create database: ' . $result['output']);
    }
    
    // Lösche temporäre Datei
    unlink($tmpFile);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 