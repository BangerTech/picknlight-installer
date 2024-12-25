<?php
header('Content-Type: application/json');

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

function waitForService($host, $port, $timeout = 30) {
    $start = time();
    while (time() < $start + $timeout) {
        if (@fsockopen($host, $port, $errno, $errstr, 1)) {
            return true;
        }
        sleep(1);
    }
    return false;
}

try {
    // Prüfe ob der Container läuft
    $result = execCommand("docker ps --filter 'name=partdb' --format '{{.Status}}'");
    if (!$result['success'] || empty($result['output'])) {
        throw new Exception('Part-DB container is not running');
    }
    
    // Hole Port aus der Konfiguration
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    $config = json_decode(file_get_contents($configDir . '/partdb-config.json'), true);
    $port = $config['port'] ?? '8034';
    
    // Warte auf Part-DB Webinterface
    if (!waitForService('localhost', $port, 60)) {  // 60 Sekunden Timeout
        throw new Exception('Part-DB web interface is not responding');
    }
    
    // Zusätzliche Prüfung der Weboberfläche
    $maxAttempts = 10;
    $attempt = 0;
    $success = false;
    
    while ($attempt < $maxAttempts) {
        $ch = curl_init("http://localhost:$port");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 400) {
            $success = true;
            break;
        }
        
        $attempt++;
        sleep(3);  // Warte 3 Sekunden zwischen den Versuchen
    }
    
    if (!$success) {
        throw new Exception('Part-DB web interface is not responding properly');
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Part-DB check error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 