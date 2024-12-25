<?php
session_start();

try {
    // Fehlerbehandlung aktivieren
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Konfigurationsverzeichnis
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Konfiguration speichern
    $_SESSION['nodered_config'] = [
        'useTraefik' => $_POST['useTraefik'] === '1',
        'noderedDomain' => $_POST['noderedDomain'] ?? '',
        'noderedPort' => $_POST['noderedPort'] ?? '8035'
    ];

    // Template laden und anpassen
    $template = file_get_contents('../templates/docker-compose-nodered.yml');
    if ($template === false) {
        throw new Exception('Could not read template file');
    }
    
    // Platzhalter ersetzen
    $template = str_replace('{{PORT}}', $_SESSION['nodered_config']['noderedPort'], $template);
    
    if ($_SESSION['nodered_config']['useTraefik']) {
        $traefikLabels = "
      labels:
        - traefik.enable=true
        - traefik.http.routers.nodered.rule=Host(`{$_SESSION['nodered_config']['noderedDomain']}`)
        - traefik.http.routers.nodered.tls=true
        - traefik.http.routers.nodered.tls.certresolver=cloudflare
        - traefik.http.services.nodered.loadbalancer.server.scheme=http
        - traefik.http.services.nodered.loadbalancer.server.port={$_SESSION['nodered_config']['noderedPort']}";
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
    
    $configFile = $configDir . '/docker-compose-nodered.yml';
    if (file_put_contents($configFile, $template) === false) {
        throw new Exception("Failed to write configuration to: $configFile");
    }
    
    // Erfolg zurückmelden
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // Fehler loggen
    error_log("Error in save_nodered_config.php: " . $e->getMessage());
    
    // Fehler zurückmelden
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 