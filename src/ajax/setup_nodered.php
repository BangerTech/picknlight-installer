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
        "$configDir/nodered/data",
        "$configDir/nodered/ssh"
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
    }
    
    // Node-RED Container erstellen und starten
    $config = json_decode(file_get_contents($configDir . '/nodered-config.json'), true);

    $template = file_get_contents('../templates/docker-compose-nodered.yml');
    $template = str_replace('{{PORT}}', $config['port'], $template);

    if ($config['useTraefik']) {
        $traefikLabels = "
    labels:
      - traefik.enable=true
      - traefik.http.routers.nodered.rule=Host(`{$config['domain']}`)
      - traefik.http.services.nodered.loadbalancer.server.port=1880";
    } else {
        $traefikLabels = '';
    }

    $template = str_replace('{{TRAEFIK_LABELS}}', $traefikLabels, $template);
    
    $noderedConfig = $configDir . '/docker-compose-nodered.yml';
    if (file_put_contents($noderedConfig, $template) === false) {
        throw new Exception('Could not write Node-RED configuration file');
    }
    
    // Stelle sicher, dass das Docker-Netzwerk existiert
    $result = execCommand("docker network inspect picknlight >/dev/null 2>&1 || docker network create picknlight");
    if (!$result['success']) {
        throw new Exception('Failed to create Docker network: ' . $result['output']);
    }
    
    $result = execCommand("cd $configDir && docker compose -f docker-compose-nodered.yml up -d");
    if (!$result['success']) {
        throw new Exception('Failed to start Node-RED: ' . $result['output']);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log('Node-RED setup error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 