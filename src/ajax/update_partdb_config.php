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
    
    // Lade MariaDB-Konfiguration
    $dbConfig = json_decode(file_get_contents("$configDir/mariadb-config.json"), true);
    if ($dbConfig === null) {
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
    
    // Ersetze die Datenbankverbindung im Template
    $template = str_replace(
        'DATABASE_URL=sqlite:///%kernel.project_dir%/var/db/app.db',
        "DATABASE_URL=$dbUrl",
        $template
    );
    
    // Speichere aktualisierte Konfiguration
    if (file_put_contents("$configDir/docker-compose-partdb.yml", $template) === false) {
        throw new Exception('Failed to save Part-DB configuration');
    }
    
    // Starte Part-DB neu
    $result = execCommand("cd $configDir && docker compose -f docker-compose-partdb.yml up -d");
    if (!$result['success']) {
        throw new Exception('Failed to start Part-DB: ' . $result['output']);
    }
    
    // Warte kurz
    sleep(5);
    
    // Führe Datenbank-Migration aus
    $result = execCommand("docker exec --user=www-data partdb php bin/console doctrine:migrations:migrate --no-interaction");
    if (!$result['success']) {
        throw new Exception('Failed to migrate database: ' . $result['output']);
    }
    
    // Hole Verifikationsinformationen
    $verification = [];
    
    // Prüfe docker-compose Konfiguration
    $verification[] = "Part-DB docker-compose.yml:";
    $verification[] = file_get_contents("$configDir/docker-compose-partdb.yml");
    
    // Prüfe Datenbankverbindung
    $result = execCommand("docker exec --user=www-data partdb php bin/console doctrine:migrations:status");
    if ($result['success']) {
        $verification[] = "\nDatabase Migration Status:";
        $verification[] = $result['output'];
    }
    
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