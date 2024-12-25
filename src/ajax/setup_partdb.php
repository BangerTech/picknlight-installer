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
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Erstelle benötigte Verzeichnisse
    $directories = [
        "$configDir/partdb/data",
        "$configDir/partdb/uploads"
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
    }
    
    // Part-DB Container erstellen und starten
    $config = json_decode(file_get_contents($configDir . '/partdb-config.json'), true);
    
    $template = file_get_contents('../templates/docker-compose-partdb.yml');
    
    // Ersetze die Platzhalter
    $template = str_replace([
        '{{PORT}}',
        '{{INSTANCE_NAME}}',
        '{{LANG}}'
    ], [
        $config['port'],
        $config['instanceName'],
        $config['defaultLang']
    ], $template);
    
    // Füge Traefik-Labels hinzu wenn aktiviert
    if ($config['useTraefik']) {
        $traefikLabels = <<<EOT
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.partdb.rule=Host(`{$config['domain']}`)"
      - "traefik.http.services.partdb.loadbalancer.server.port=80"
EOT;
    } else {
        $traefikLabels = '';
    }
    
    $template = str_replace('{{TRAEFIK_LABELS}}', $traefikLabels, $template);
    
    $partdbConfig = $configDir . '/docker-compose-partdb.yml';
    if (file_put_contents($partdbConfig, $template) === false) {
        throw new Exception('Could not write Part-DB configuration file');
    }
    
    // Stelle sicher, dass das Docker-Netzwerk existiert
    $result = execCommand("docker network inspect picknlight >/dev/null 2>&1 || docker network create picknlight");
    if (!$result['success']) {
        throw new Exception('Failed to create Docker network: ' . $result['output']);
    }
    
    $result = execCommand("cd $configDir && docker compose -f docker-compose-partdb.yml up -d");
    if (!$result['success']) {
        throw new Exception('Failed to start Part-DB: ' . $result['output']);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Part-DB setup error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 