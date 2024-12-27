<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // 1. Stoppe den Part-DB Container
    error_log("Stopping Part-DB container...");
    exec("cd /app/config && docker compose -f docker-compose-partdb.yml down 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to stop Part-DB container: " . implode("\n", $output));
    }
    
    // 2. Hole die MariaDB Konfiguration
    error_log("Reading MariaDB configuration...");
    $mariadbConfig = file_get_contents('/app/config/docker-compose-mariadb.yml');
    if (!$mariadbConfig) {
        throw new Exception("Could not read MariaDB configuration");
    }
    
    // Extrahiere die Zugangsdaten
    preg_match('/MYSQL_ROOT_PASSWORD:\s*([^\s\n]+)/', $mariadbConfig, $matches);
    $dbPassword = $matches[1] ?? 'root';
    
    // 3. Update die Database URL in der Part-DB Konfiguration
    error_log("Updating Part-DB configuration...");
    $configFile = '/app/config/docker-compose-partdb.yml';
    if (!file_exists($configFile)) {
        throw new Exception("Part-DB configuration file not found");
    }
    
    $config = file_get_contents($configFile);
    $config = preg_replace(
        '/DATABASE_URL=.*$/m',
        "DATABASE_URL=mysql://partdb:${dbPassword}@mariadb:3306/partdb",
        $config
    );
    file_put_contents($configFile, $config);
    
    // 4. Starte den Part-DB Container neu
    error_log("Starting Part-DB container...");
    exec("cd /app/config && docker compose -f docker-compose-partdb.yml up -d 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to start Part-DB container: " . implode("\n", $output));
    }
    
    // 5. Warte kurz, bis der Container gestartet ist
    sleep(5);
    
    // 6. Führe die Datenbank-Migration aus
    error_log("Running database migration...");
    exec("docker exec --user=www-data partdb php bin/console doctrine:migrations:migrate --no-interaction 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to run database migrations: " . implode("\n", $output));
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    error_log("Migration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 