<?php
header('Content-Type: application/json');

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    error_log("Executing command: $command");
    error_log("Command output: " . implode("\n", $output));
    error_log("Command return: $return_var");
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

try {
    error_log("Starting Part-DB integration process");
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Lade MariaDB-Konfiguration
    $dbConfig = json_decode(file_get_contents("$configDir/mariadb-config.json"), true);
    if ($dbConfig === null) {
        error_log("Failed to load MariaDB configuration");
        throw new Exception('Failed to load MariaDB configuration');
    }
    
    // Stoppe Part-DB Container
    $result = execCommand("cd $configDir && docker compose -f docker-compose-partdb.yml down");
    if (!$result['success']) {
        throw new Exception('Failed to stop Part-DB: ' . $result['output']);
    }
    
    // Aktualisiere Part-DB docker-compose.yml
    $template = file_get_contents('../templates/docker-compose-partdb.yml');
    if ($template === false) {
        throw new Exception('Failed to read template file');
    }
    
    // Lade Part-DB-Konfiguration
    $partdbConfig = json_decode(file_get_contents("$configDir/partdb-config.json"), true);
    if ($partdbConfig === null) {
        throw new Exception('Failed to load Part-DB configuration');
    }
    
    // Erstelle Datenbankverbindungs-URL
    $dbUrl = sprintf(
        "mysql://partdb:%s@mariadb:3306/partdb",
        urlencode($dbConfig['db_password'])
    );
    
    // Bereite Traefik Labels vor
    if ($partdbConfig['useTraefik']) {
        $traefikLabels = <<<EOT
      labels:
        traefik.enable: true
        traefik.http.routers.partdb.rule: Host(`{$partdbConfig['domain']}`)
        traefik.http.services.partdb.loadbalancer.server.port: 80
 EOT;
    } else {
        $traefikLabels = '';
    }
    
    // Ersetze die Datenbankverbindung im Template
    $template = str_replace(
        'DATABASE_URL: sqlite:///%kernel.project_dir%/var/db/app.db',
        "DATABASE_URL: $dbUrl",
        $template
    );
    
    // Ersetze weitere Template-Variablen
    $replacements = [
        '{{DEFAULT_LANG}}' => $partdbConfig['defaultLang'] ?? 'en',
        '{{INSTANCE_NAME}}' => $partdbConfig['instanceName'] ?? 'Pick\'n\'Light',
        '{{PORT}}' => $partdbConfig['port'] ?? '8034'
    ];
    
    foreach ($replacements as $key => $value) {
        $template = str_replace($key, $value, $template);
    }
    
    // Ersetze die Traefik Labels
    $template = str_replace('{{TRAEFIK_LABELS}}', $traefikLabels, $template);
    
    // Speichere aktualisierte Konfiguration
    if (file_put_contents("$configDir/docker-compose-partdb.yml", $template) === false) {
        throw new Exception('Failed to save Part-DB configuration');
    }
    
    // Zeige Fortschritt in der Verifikation
    $verification = [];
    $verification[] = "=== Stopping Part-DB container ===";
    
    // Starte Part-DB neu
    $result = execCommand("cd $configDir && docker compose -f docker-compose-partdb.yml up -d");
    $verification[] = "\n=== Starting Part-DB with new configuration ===";
    $verification[] = "Updated docker-compose.yml:";
    $verification[] = $template;
    if (!$result['success']) {
        throw new Exception('Failed to start Part-DB: ' . $result['output']);
    }
    
    // Warte kurz
    sleep(5);
    $verification[] = "\n=== Running database migration ===";
    
    // Führe Datenbank-Migration aus
    $result = execCommand("docker exec --user=www-data partdb php bin/console doctrine:migrations:migrate --no-interaction");
    if (!$result['success']) {
        throw new Exception('Failed to migrate database: ' . $result['output']);
    }
    $verification[] = $result['output'];
    
    // Prüfe Datenbankverbindung
    $verification[] = "\n=== Verifying database connection ===";
    $result = execCommand("docker exec --user=www-data partdb php bin/console doctrine:migrations:status");
    if ($result['success']) {
        $verification[] = $result['output'];
    }
    
    $verification[] = "\n=== Integration completed successfully ===";
    
    echo json_encode([
        'success' => true,
        'verification' => implode("\n", $verification)
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 