<?php
header('Content-Type: application/json');

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Konfiguration aus dem Formular
    $config = [
        'useTraefik' => $_POST['useTraefik'] === '1',
        'domain' => $_POST['noderedDomain'] ?? '',
        'port' => $_POST['noderedPort'] ?? '8035'
    ];
    
    // Validierung
    if ($config['useTraefik'] && empty($config['domain'])) {
        throw new Exception('Domain is required when using Traefik');
    }
    
    if (empty($config['port'])) {
        $config['port'] = '8035';  // Default Port
    }
    
    // Stelle sicher, dass das Konfigurationsverzeichnis existiert
    if (!file_exists($configDir)) {
        if (!mkdir($configDir, 0777, true)) {
            throw new Exception("Failed to create config directory: $configDir");
        }
    }
    
    // Speichere die Konfiguration
    $configFile = $configDir . '/nodered-config.json';
    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Could not write configuration file');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Node-RED config error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 