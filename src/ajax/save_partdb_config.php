<?php
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
    
    $configFile = $configDir . '/partdb-config.json';
    if (file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT)) === false) {
        throw new Exception('Could not write configuration file');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 