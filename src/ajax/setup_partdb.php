<?php
header('Content-Type: application/json');

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    error_log("Command: $command");
    error_log("Output: " . implode("\n", $output));
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Erstelle benötigte Verzeichnisse
    $directories = [
        "$configDir/partdb/uploads",
        "$configDir/partdb/data",
        "$configDir/partdb/var/db"
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
        chmod($dir, 0777);
    }
    
    // Part-DB Container erstellen und starten
    $configFile = $configDir . '/partdb-config.json';
    if (!file_exists($configFile)) {
        throw new Exception("Configuration file not found: $configFile");
    }
    
    $config = json_decode(file_get_contents($configFile), true);
    if ($config === null) {
        throw new Exception("Failed to parse configuration file");
    }

    $template = file_get_contents('../templates/docker-compose-partdb.yml');
    if ($template === false) {
        throw new Exception("Failed to read template file");
    }
    
    // Ersetze Platzhalter
    $replacements = [
        '{{PORT}}' => $config['port'],
        '{{INSTANCE_NAME}}' => $config['instanceName'],
        '{{DEFAULT_LANG}}' => $config['defaultLang']
    ];
    
    foreach ($replacements as $key => $value) {
        $template = str_replace($key, $value, $template);
    }

    // Traefik Labels
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
    
    // Speichere Docker Compose Datei
    $composeFile = $configDir . '/docker-compose-partdb.yml';
    if (file_put_contents($composeFile, $template) === false) {
        throw new Exception('Could not write docker-compose file');
    }
    
    // Stelle sicher, dass das Docker-Netzwerk existiert
    $result = execCommand("docker network inspect picknlight >/dev/null 2>&1 || docker network create picknlight");
    if (!$result['success']) {
        throw new Exception('Failed to create Docker network: ' . $result['output']);
    }
    
    // Starte den Container
    $result = execCommand("cd $configDir && docker compose -f docker-compose-partdb.yml up -d");
    if (!$result['success']) {
        throw new Exception('Failed to start Part-DB: ' . $result['output']);
    }
    
    // Warte kurz, bis der Container gestartet ist
    sleep(5);
    
    // Führe die Datenbank-Migration aus und erfasse die Ausgabe
    $result = execCommand("docker exec --user=www-data partdb php bin/console doctrine:migrations:migrate --no-interaction");
    if (!$result['success']) {
        error_log("Migration output: " . $result['output']);
        throw new Exception('Failed to initialize database: ' . $result['output']);
    }
    
    // Extrahiere das Passwort aus der Migration-Ausgabe oder setze es zurück
    $password = 'admin';  // Standardpasswort
    if (strpos($result['output'], 'Already at the latest version') !== false) {
        // Migration wurde bereits ausgeführt, setze das Passwort zurück
        $result = execCommand('printf "yes\nadmin\nadmin\n" | docker exec -i --user=www-data partdb php bin/console partdb:users:set-password admin');
        if (!$result['success']) {
            error_log("Password reset output: " . $result['output']);
            throw new Exception('Failed to reset admin password: ' . $result['output']);
        }
    }
    
    echo json_encode([
        'success' => true,
        'password' => $password  // Gebe das Passwort zurück
    ]);
} catch (Exception $e) {
    error_log('Part-DB setup error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 