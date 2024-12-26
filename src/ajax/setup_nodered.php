<?php
// Aktiviere Error Reporting für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php/error.log');

// Starte Output Buffering
ob_start();

header('Content-Type: application/json');

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    error_log("Command: $command");
    error_log("Output: " . implode("\n", $output));
    error_log("Return: $return_var");
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

function ensureNetworkExists() {
    // Prüfe, ob das Netzwerk bereits existiert
    $result = execCommand("docker network ls --filter name=^picknlight$ --format '{{.Name}}'");
    if (empty(trim($result['output']))) {
        // Netzwerk existiert nicht, erstelle es
        $result = execCommand("docker network create picknlight");
        if (!$result['success']) {
            throw new Exception('Failed to create Docker network: ' . $result['output']);
        }
    }
    
    // Überprüfe nochmal, ob das Netzwerk jetzt existiert
    $result = execCommand("docker network ls --filter name=^picknlight$ --format '{{.Name}}'");
    if (empty(trim($result['output']))) {
        throw new Exception('Failed to verify Docker network creation');
    }
}

// Hilfsfunktion für sicheres Flushen des Buffers
function flushOutput($data) {
    echo json_encode($data) . "\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    error_log("Config dir: $configDir");
    
    $status = ['step' => 'config'];
    
    // Stelle als erstes sicher, dass das Docker-Netzwerk existiert
    ensureNetworkExists();
    
    // Erstelle benötigte Verzeichnisse
    $directories = [
        "$configDir/nodered/data"
    ];
    
    $status['step'] = 'directories';
    foreach ($directories as $dir) {
        error_log("Creating directory: $dir");
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
        
        // Setze volle Berechtigungen für das Verzeichnis
        chmod($dir, 0777);
    }
    
    // Nach dem Erstellen der Verzeichnisse
    $status['step'] = 'directories';
    flushOutput([
        'success' => true,
        'status' => $status,
        'progress' => 'Directories created'
    ]);
    
    // Node-RED Container erstellen und starten
    $configFile = $configDir . '/nodered-config.json';
    error_log("Reading config from: $configFile");
    
    if (!file_exists($configFile)) {
        throw new Exception("Configuration file not found: $configFile");
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    if ($config === null) {
        throw new Exception("Failed to parse configuration file");
    }
    
    error_log("Config: " . print_r($config, true));

    $template = file_get_contents('../templates/docker-compose-nodered.yml');
    if ($template === false) {
        throw new Exception("Failed to read template file");
    }
    
    $template = str_replace('{{PORT}}', $config['port'], $template);

    if ($config['useTraefik']) {
        $traefikLabels = <<<EOT
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.nodered.rule=Host(`{$config['domain']}`)"
      - "traefik.http.services.nodered.loadbalancer.server.port=1880"
EOT;
    } else {
        $traefikLabels = '';
    }

    $template = str_replace('{{TRAEFIK_LABELS}}', $traefikLabels, $template);
    
    $noderedConfig = $configDir . '/docker-compose-nodered.yml';
    error_log("Writing config to: $noderedConfig");
    error_log("Config content: $template");
    
    if (file_put_contents($noderedConfig, $template) === false) {
        throw new Exception('Could not write Node-RED configuration file');
    }
    
    // Nach dem Start des Containers
    $status['step'] = 'container';
    flushOutput([
        'success' => true,
        'status' => $status,
        'progress' => 'Starting Node-RED container...'
    ]);
    
    $result = execCommand("cd $configDir && docker compose -f docker-compose-nodered.yml up -d");
    if (!$result['success']) {
        throw new Exception('Failed to start Node-RED: ' . $result['output']);
    }
    
    // Warte auf Node-RED Start
    $retries = 0;
    $maxRetries = 30;
    $finalResponse = null;
    
    while ($retries < $maxRetries) {
        sleep(1);
        $result = execCommand("docker inspect -f '{{.State.Health.Status}}' nodered");
        
        // Sende Statusupdate
        flushOutput([
            'success' => true,
            'status' => $status,
            'progress' => "Waiting for Node-RED to start ($retries/$maxRetries)..."
        ]);
        
        if (trim($result['output']) === 'healthy') {
            // Markiere Container-Start als erfolgreich
            flushOutput([
                'success' => true,
                'status' => $status,
                'progress' => 'Node-RED container started successfully'
            ]);
            
            // Update status to nodes installation
            $status['step'] = 'nodes';
            flushOutput([
                'success' => true,
                'status' => $status,
                'progress' => 'Starting nodes installation...'
            ]);
            
            // Installiere notwendige Node-RED Pakete
            $nodesToInstall = [
                'node-red-node-mysql',
                'node-red-dashboard',
                'node-red-node-ui-table',
                'node-red-contrib-ui-iro-color-picker'
            ];
            
            foreach ($nodesToInstall as $node) {
                flushOutput([
                    'success' => true,
                    'status' => $status,
                    'progress' => "Installing Node-RED package: $node"
                ]);
                
                $installResult = execCommand("docker exec nodered npm install $node");
                if (!$installResult['success']) {
                    throw new Exception("Failed to install Node-RED package $node: " . $installResult['output']);
                }
            }
            
            // Starte Node-RED neu, damit die neuen Nodes geladen werden
            $status['progress'] = "Restarting Node-RED...";
            execCommand("docker restart nodered");
            
            // Warte kurz, bis Node-RED wieder gestartet ist
            sleep(10);
            
            $finalResponse = [
                'success' => true,
                'status' => $status,
                'message' => 'Node-RED successfully started and packages installed'
            ];
            break;
        }
        $retries++;
        
        // Aktualisiere nur den Status, sende aber keine Response
        $status['progress'] = "Waiting for Node-RED to start ($retries/$maxRetries)...";
    }
    
    // Sende die finale Response nur einmal am Ende
    if ($finalResponse === null) {
        $finalResponse = [
            'success' => false,
            'error' => 'Timeout waiting for Node-RED to start'
        ];
    }
    
    flushOutput($finalResponse);

} catch (Exception $e) {
    flushOutput([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 