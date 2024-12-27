case 'verify':
    try {
        // Hole Tabellenstruktur
        $showTableCmd = "cd /app/config && docker compose -f docker-compose-mariadb.yml exec -T mariadb mysql -N -B -u root -proot partdb -e 'SHOW CREATE TABLE led_mapping\\G'";
        exec($showTableCmd, $tableOutput, $returnCode);
        $tableStructure = '';
        foreach ($tableOutput as $line) {
            if (strpos($line, 'Create Table:') !== false) {
                $tableStructure = trim(substr($line, 13));
                break;
            }
        }

        // Hole Trigger
        $showTriggersCmd = "cd /app/config && docker compose -f docker-compose-mariadb.yml exec -T mariadb mysql -N -B -u root -proot partdb -e 'SHOW TRIGGERS\\G'";
        exec($showTriggersCmd, $triggerOutput, $returnCode);
        $triggerDetails = [];
        $currentTrigger = '';
        foreach ($triggerOutput as $line) {
            if (strpos($line, 'Statement:') !== false) {
                $currentTrigger = trim(substr($line, 10));
                $triggerDetails[] = $currentTrigger;
            }
        }

        // Hole das Passwort aus der docker-compose-mariadb.yml
        $composeFile = '/app/config/docker-compose-mariadb.yml';
        $dbPassword = 'root'; // Standardwert
        if (file_exists($composeFile)) {
            $compose = file_get_contents($composeFile);
            if (preg_match('/MYSQL_ROOT_PASSWORD:\s*([^\s]+)/', $compose, $matches)) {
                $dbPassword = trim($matches[1]);
            }
        }

        // Alternativ aus der .env Datei
        if ($dbPassword === 'root') {
            $envFile = '/app/config/.env';
            if (file_exists($envFile)) {
                $envContent = file_get_contents($envFile);
                if (preg_match('/MYSQL_ROOT_PASSWORD=([^\n]+)/', $envContent, $matches)) {
                    $dbPassword = trim($matches[1]);
                }
            }
        }

        echo json_encode([
            'success' => true,
            'host' => 'localhost',
            'port' => '3306',
            'database' => 'partdb',
            'username' => 'root',
            'password' => $dbPassword,
            'tableStructure' => $tableStructure,
            'triggers' => implode("\n\n", $triggerDetails)
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false, 
            'error' => $e->getMessage(),
            'tableStructure' => '',
            'triggers' => ''
        ]);
    }
    break; 

case 'migrate_partdb':
    try {
        // 1. Stoppe den Part-DB Container
        $stopCommand = "cd /app/config && docker compose -f docker-compose-partdb.yml down";
        exec($stopCommand, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Failed to stop Part-DB container");
        }
        
        // 2. Update die Database URL in der docker-compose.yml
        $configFile = '/app/config/docker-compose-partdb.yml';
        if (!file_exists($configFile)) {
            throw new Exception("Part-DB configuration file not found");
        }
        
        $config = file_get_contents($configFile);
        $config = preg_replace(
            '/DATABASE_URL=.*$/m',
            'DATABASE_URL=mysql://partdb:root@mariadb:3306/partdb',
            $config
        );
        file_put_contents($configFile, $config);
        
        // 3. Starte den Part-DB Container neu
        $startCommand = "cd /app/config && docker compose -f docker-compose-partdb.yml up -d";
        exec($startCommand, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Failed to start Part-DB container");
        }
        
        // Warte kurz, bis der Container gestartet ist
        sleep(5);
        
        // 4. Führe die Datenbank-Migration aus
        $migrateCommand = "cd /app/config && docker compose -f docker-compose-partdb.yml exec -T --user=www-data partdb php bin/console doctrine:migrations:migrate --no-interaction";
        exec($migrateCommand, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Failed to run database migrations");
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    break;

case 'import_data':
    try {
        // Initialisiere die Datenbank
        $command = "cd /app/config && docker compose -f docker-compose-partdb.yml exec -T --user=www-data partdb php bin/console app:database:init --no-interaction";
        exec($command, $output, $returnCode);
        
        if ($returnCode !== 0) {
            throw new Exception("Failed to initialize database");
        }
        
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    break; 