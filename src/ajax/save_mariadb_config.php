<?php
header('Content-Type: application/json');

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Validiere Eingaben
    $rootPassword = $_POST['rootPassword'] ?? '';
    $dbPassword = $_POST['dbPassword'] ?? '';
    $dbPort = $_POST['dbPort'] ?? '3306';
    
    if (empty($rootPassword) || empty($dbPassword)) {
        throw new Exception('Passwords cannot be empty');
    }
    
    // Validiere Port
    if (!is_numeric($dbPort) || $dbPort < 1 || $dbPort > 65535) {
        throw new Exception('Invalid port number');
    }
    
    // Speichere Konfiguration
    $config = [
        'root_password' => $rootPassword,
        'db_password' => $dbPassword,
        'port' => (int)$dbPort
    ];
    
    if (file_put_contents("$configDir/mariadb-config.json", json_encode($config)) === false) {
        throw new Exception('Failed to save configuration');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 