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
    // Prüfe ob der Container läuft
    $result = execCommand("docker ps --filter 'name=nodered' --format '{{.Status}}'");
    
    if (!$result['success']) {
        throw new Exception('Failed to check container status');
    }
    
    if (empty($result['output'])) {
        throw new Exception('Node-RED container is not running');
    }
    
    // Prüfe ob der Container "healthy" oder "starting" ist
    if (strpos($result['output'], 'Up') === false) {
        throw new Exception('Node-RED container is not in a healthy state');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 