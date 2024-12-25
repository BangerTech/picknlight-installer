<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php/error.log');

header('Content-Type: application/json');

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    $config = [
        'useTraefik' => $_POST['useTraefik'] === '1',
        'domain' => $_POST['partdbDomain'] ?? '',
        'port' => $_POST['partdbPort'] ?? '8034',
        'instanceName' => $_POST['instanceName'] ?? 'Pick\'n\'Light',
        'defaultLang' => $_POST['defaultLang'] ?? 'de'
    ];
    
    if ($config['useTraefik'] && empty($config['domain'])) {
        throw new Exception('Domain is required when using Traefik');
    }
    
    if (empty($config['port'])) {
        $config['port'] = '8034';  // Default Port
    }
    
    if (!file_exists($configDir)) {
        if (!mkdir($configDir, 0777, true)) {
            throw new Exception("Failed to create config directory: $configDir");
        }
    }
    
    $configFile = $configDir . '/partdb-config.json';
    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Could not write configuration file');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Part-DB config error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 