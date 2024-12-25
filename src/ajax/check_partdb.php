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
    
    // Hole Port aus der Konfiguration
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    $config = json_decode(file_get_contents($configDir . '/partdb-config.json'), true);
    $port = $config['port'] ?? '8034';
    
    // Versuche verschiedene URLs
    $urls = [
        "http://192.168.2.86:$port",
        "http://localhost:$port",
        "http://host.docker.internal:$port",
        "http://172.17.0.1:$port",  // Docker-Host-IP
        "http://partdb",            // Container-Name
        "http://partdb:80"         // Container-Name mit Port
    ];
    
    // Mehrere Versuche mit Wartezeit
    $maxAttempts = 5;  // Maximale Anzahl der Versuche
    $attempt = 1;
    $success = false;

    while ($attempt <= $maxAttempts && !$success) {
        error_log("Attempt $attempt of $maxAttempts");
        
        foreach ($urls as $url) {
            error_log("Trying to connect to: $url");
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);  // Längerer Timeout
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            error_log("Response from $url: $httpCode");
            
            if ($httpCode !== 0) {
                $success = true;
                break 2;  // Beende beide Schleifen
            }
        }
        
        if (!$success && $attempt < $maxAttempts) {
            error_log("Waiting 5 seconds before next attempt...");
            sleep(5);  // Warte 5 Sekunden zwischen den Versuchen
        }
        
        $attempt++;
    }
    
    if (!$success) {
        throw new Exception('Part-DB web interface is not responding after ' . $maxAttempts . ' attempts');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Part-DB check error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 