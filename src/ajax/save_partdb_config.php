<?php
session_start();

try {
    // Fehlerbehandlung aktivieren
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Konfigurationsverzeichnis
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Konfiguration speichern
    $_SESSION['partdb_config'] = [
        'useTraefik' => $_POST['useTraefik'] === '1',
        'partdbDomain' => $_POST['partdbDomain'] ?? '',
        'partdbPort' => $_POST['partdbPort'] ?? '8080',
        'dbRootPassword' => $_POST['dbRootPassword'] ?? 'root',
        'dbPassword' => $_POST['dbPassword'] ?? 'partdb',
        'instanceName' => $_POST['instanceName'] ?? 'Pick\'n\'Light',
        'lang' => $_POST['lang'] ?? 'de'
    ];

    // Template laden und anpassen
    $template = file_get_contents('../templates/docker-compose-partdb.yml');
    if ($template === false) {
        throw new Exception('Could not read template file');
    }
    
    // Platzhalter ersetzen
    $replacements = [
        '{{PORT}}' => $_SESSION['partdb_config']['partdbPort'],
        '{{DATABASE_URL}}' => "mysql://partdb:{$_SESSION['partdb_config']['dbPassword']}@mariadb:3306/partdb",
        '{{LANG}}' => $_SESSION['partdb_config']['lang'],
        '{{INSTANCE_NAME}}' => $_SESSION['partdb_config']['instanceName'],
        '{{DEFAULT_URI}}' => $_SESSION['partdb_config']['useTraefik'] 
            ? "https://{$_SESSION['partdb_config']['partdbDomain']}"
            : "http://localhost:{$_SESSION['partdb_config']['partdbPort']}",
        '{{ROOT_PASSWORD}}' => $_SESSION['partdb_config']['dbRootPassword'],
        '{{DB_PASSWORD}}' => $_SESSION['partdb_config']['dbPassword']
    ];

    $template = str_replace(array_keys($replacements), array_values($replacements), $template);
    
    if ($_SESSION['partdb_config']['useTraefik']) {
        $traefikLabels = "
      labels:
        - traefik.enable=true
        - traefik.http.routers.partdb.rule=Host(`{$_SESSION['partdb_config']['partdbDomain']}`)
        - traefik.http.routers.partdb.tls=true
        - traefik.http.routers.partdb.tls.certresolver=cloudflare
        - traefik.http.services.partdb.loadbalancer.server.port=80";
    } else {
        $traefikLabels = '';
    }
    
    $template = str_replace('{{TRAEFIK_LABELS}}', $traefikLabels, $template);
    
    // Konfiguration speichern
    if (!is_dir($configDir)) {
        if (!mkdir($configDir, 0777, true)) {
            throw new Exception("Failed to create directory: $configDir");
        }
    }
    
    $configFile = $configDir . '/docker-compose-partdb.yml';
    if (file_put_contents($configFile, $template) === false) {
        throw new Exception("Failed to write configuration to: $configFile");
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Error in save_partdb_config.php: " . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 