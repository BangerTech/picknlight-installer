<?php
header('Content-Type: application/json');

try {
    // 1. Stop PartDB Container
    exec('docker stop partdb 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to stop PartDB container: " . implode("\n", $output));
    }

    // 2. Update docker-compose.yml
    $composePath = '/app/config/docker-compose-partdb.yml';
    if (!file_exists($composePath)) {
        throw new Exception("PartDB docker-compose file not found");
    }

    // Lese und aktualisiere die Konfiguration
    $config = file_get_contents($composePath);
    $config = preg_replace(
        '/DATABASE_URL=.*$/m',
        'DATABASE_URL=mysql://partdb:root@mariadb:3306/partdb',
        $config
    );
    file_put_contents($composePath, $config);

    // 3. Start PartDB Container
    exec('docker start partdb 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception("Failed to start PartDB container: " . implode("\n", $output));
    }

    // 4. Run database migration
    exec('docker exec --user=www-data partdb php bin/console doctrine:migrations:migrate --no-interaction 2>&1', 
        $output, 
        $returnCode
    );
    if ($returnCode !== 0) {
        throw new Exception("Failed to migrate database: " . implode("\n", $output));
    }

    $response = [
        'success' => true,
        'message' => 'Migration completed successfully',
        'details' => implode("\n", $output)
    ];

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response); 