<?php
// Aktiviere Error Reporting für Debugging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php/error.log');

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

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    error_log("Config dir: $configDir");
    
    // Erstelle benötigte Verzeichnisse
    $directories = [
        "$configDir/nodered/data",
        "$configDir/nodered/ssh"
    ];
    
    foreach ($directories as $dir) {
        error_log("Creating directory: $dir");
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
        // Setze Berechtigungen
        chmod($dir, 0777);
        // Berechtigungen nur setzen wenn möglich
        if (function_exists('posix_getuid')) {
            $processUser = posix_getuid();
            if ($processUser === 0) { // Nur als root ausführen
                chown($dir, 1000);
                chgrp($dir, 1000);
            }
        }
    }
    
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
    error_log('Stack trace: ' . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 