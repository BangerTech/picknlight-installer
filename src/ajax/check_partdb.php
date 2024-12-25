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
    // Prüfe ob der Container läuft und "healthy" ist
    $result = execCommand("docker ps --filter 'name=partdb' --format '{{.Status}}'");
    if (!$result['success'] || empty($result['output'])) {
        throw new Exception('Part-DB container is not running');
    }
    
    if (strpos($result['output'], 'Up') === false) {
        throw new Exception('Part-DB container is not in a healthy state');
    }
    
    // Hole die IP-Adresse des Containers
    $result = execCommand("docker inspect -f '{{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}}' partdb");
    if (!$result['success'] || empty($result['output'])) {
        throw new Exception('Could not determine container IP');
    }
    $containerIP = trim($result['output']);
    
    // Hole Port aus der Konfiguration
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    $config = json_decode(file_get_contents($configDir . '/partdb-config.json'), true);
    $port = $config['port'] ?? '8034';
    
    // Prüfe die Verbindung zum Container
    $fp = @fsockopen($containerIP, 80, $errno, $errstr, 1);
    if (!$fp) {
        // Fallback: Prüfe den Host-Port
        $fp = @fsockopen('host.docker.internal', $port, $errno, $errstr, 1);
        if (!$fp) {
            throw new Exception('Part-DB web interface is not accessible');
        }
    }
    if ($fp) {
        fclose($fp);
    }
    
    // Wenn wir bis hier kommen, läuft alles
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Part-DB check error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 