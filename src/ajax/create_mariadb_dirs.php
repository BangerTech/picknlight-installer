<?php
header('Content-Type: application/json');

try {
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    // Erstelle Verzeichnisse
    $directories = [
        "$configDir/mariadb/data"
    ];
    
    foreach ($directories as $dir) {
        if (!file_exists($dir)) {
            if (!mkdir($dir, 0777, true)) {
                throw new Exception("Failed to create directory: $dir");
            }
        }
        chmod($dir, 0777);
    }
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 