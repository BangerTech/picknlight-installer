<?php
// Fehlerbehandlung
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Setze Header für JSON
header('Content-Type: application/json');

session_start();

// Funktion zum sauberen JSON-Output
function outputJSON($data) {
    if (headers_sent()) {
        error_log('Headers already sent in database_setup.php');
        return;
    }
    echo json_encode($data);
    exit;
}

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

function findMysqlPath($configDir) {
    // Versuche direkt den Befehl zu finden
    $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb sh -c 'command -v mysql'");
    if ($result['success']) {
        $path = trim($result['output']);
        error_log("Found mysql at: $path");
        return $path;
    }

    // Fallback: Suche in typischen Verzeichnissen
    $paths = [
        '/usr/bin/mysql',
        '/bin/mysql',
        '/usr/local/bin/mysql'
    ];

    foreach ($paths as $path) {
        $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb sh -c '[ -x $path ] && echo $path'");
        if ($result['success'] && !empty($result['output'])) {
            error_log("Found mysql at: $path");
            return trim($path);
        }
    }

    // Letzter Versuch: Suche in $PATH
    $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb sh -c 'find /usr -name mysql -type f -executable 2>/dev/null'");
    if ($result['success'] && !empty($result['output'])) {
        $path = trim(explode("\n", $result['output'])[0]);
        error_log("Found mysql at: $path");
        return $path;
    }
    
    error_log("Could not find mysql client. Container might be using different binary name.");
    return 'mariadb'; // Fallback zum mariadb-Befehl
}

function waitForMariaDB($configDir) {
    $maxAttempts = 60;
    $attempts = 0;
    
    while ($attempts < $maxAttempts) {
        // Prüfe Container-Status und Health
        $result = execCommand("docker inspect mariadb --format '{{.State.Status}},{{.State.Health.Status}}'");
        error_log("Docker inspect result: " . print_r($result, true));
        
        if ($result['success']) {
            $status = explode(',', trim($result['output']));
            error_log("Container status: " . print_r($status, true));
            
            if ($status[0] === 'running') {
                // Versuche eine direkte Verbindung mit mariadb
                $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb mariadb -h 127.0.0.1 -u root -proot -e 'SELECT 1' 2>/dev/null");
                if ($result['success']) {
                    error_log("MariaDB is ready and accepting connections!");
                    return true;
                }
                error_log("Connection attempt failed: " . $result['output']);
            }
        }
        
        error_log("Waiting for MariaDB... Attempt $attempts of $maxAttempts");
        $attempts++;
        sleep(1);
    }
    return false;
}

try {
    $step = $_GET['step'] ?? '';
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';

    // Entferne die MySQL-Client-Pfad-Überprüfung
    // $mysqlPath = findMysqlPath($configDir);
    // if (!$mysqlPath) {
    //     throw new Exception('Could not find mysql client in container!');
    // }

    switch ($step) {
        case 'create_mariadb':
            // MariaDB Container erstellen und starten
            $template = file_get_contents('../templates/docker-compose-mariadb.yml');
            if ($template === false) {
                throw new Exception('Could not read MariaDB template file');
            }

            $template = str_replace(
                ['{{ROOT_PASSWORD}}', '{{DB_PASSWORD}}'],
                ['root', 'root'],
                $template
            );
            
            $mariadbConfig = $configDir . '/docker-compose-mariadb.yml';
            if (file_put_contents($mariadbConfig, $template) === false) {
                throw new Exception('Could not write MariaDB configuration file');
            }
            
            // Stelle sicher, dass das Docker-Netzwerk existiert
            $result = execCommand("docker network inspect picknlight >/dev/null 2>&1 || docker network create picknlight");
            if (!$result['success']) {
                throw new Exception('Failed to create Docker network: ' . $result['output']);
            }
            
            $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml up -d");
            if (!$result['success']) {
                throw new Exception('Failed to start MariaDB: ' . $result['output']);
            }
            
            echo json_encode(['success' => true]);
            break;

        case 'create_database':
            if (!waitForMariaDB($configDir)) {
                $logs = execCommand("docker logs mariadb 2>&1");
                error_log("MariaDB container logs: " . $logs['output']);
                throw new Exception('MariaDB is not ready after 60 seconds. Container logs have been written to error log.');
            }

            // Verwende mariadb statt mysql als Befehl
            $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb mariadb -h 127.0.0.1 -u root -proot -e 'CREATE DATABASE IF NOT EXISTS partdb;'");
            if (!$result['success']) {
                throw new Exception('Failed to create database: ' . $result['output']);
            }
            echo json_encode(['success' => true]);
            break;

        case 'create_table':
            $sql = "
            CREATE TABLE IF NOT EXISTS led_mapping (
                part_id INT PRIMARY KEY,
                led_position INT NOT NULL,
                UNIQUE (led_position)
            );";
            
            $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb mariadb -h 127.0.0.1 -u root -proot partdb -e " . escapeshellarg($sql));
            if (!$result['success']) {
                throw new Exception('Failed to create table: ' . $result['output']);
            }
            echo json_encode(['success' => true]);
            break;

        case 'import_triggers':
            try {
                error_log("Starting trigger import process...");
                
                // Pfade zu den Trigger-Dateien
                $triggerFiles = [
                    __DIR__ . '/../sql/triggers/Trigger_assign_led_after_insert.sql',
                    __DIR__ . '/../sql/triggers/Trigger_delete_led_mapping_after_part_delete.sql'
                ];
                
                foreach ($triggerFiles as $triggerFile) {
                    if (!file_exists($triggerFile)) {
                        error_log("Trigger file not found at: $triggerFile");
                        throw new Exception("Trigger file not found: $triggerFile");
                    }
                    
                    // Extrahiere Trigger-Namen aus der Datei
                    $content = file_get_contents($triggerFile);
                    if (preg_match('/CREATE TRIGGER\s+(\w+)/', $content, $matches)) {
                        $triggerName = $matches[1];
                        
                        // Prüfe ob Trigger bereits existiert
                        $checkCommand = "cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb mariadb -h 127.0.0.1 -u root -proot partdb -e 'SHOW TRIGGERS WHERE Trigger = \"$triggerName\"'";
                        $result = execCommand($checkCommand);
                        
                        if (!empty($result['output']) && strpos($result['output'], $triggerName) !== false) {
                            // Trigger existiert bereits - lösche ihn zuerst
                            $dropCommand = "cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb mariadb -h 127.0.0.1 -u root -proot partdb -e 'DROP TRIGGER IF EXISTS $triggerName'";
                            $result = execCommand($dropCommand);
                            if (!$result['success']) {
                                throw new Exception("Failed to drop existing trigger: " . $result['output']);
                            }
                            error_log("Dropped existing trigger: $triggerName");
                        }
                        
                        // Trigger-Datei in den Container kopieren
                        $basename = basename($triggerFile);
                        $copyCommand = "docker cp $triggerFile mariadb:/tmp/$basename";
                        $result = execCommand($copyCommand);
                        if (!$result['success']) {
                            throw new Exception('Failed to copy trigger file: ' . $result['output']);
                        }
                        
                        // Trigger importieren
                        $importCommand = "cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb bash -c 'cat /tmp/$basename | mariadb -h 127.0.0.1 -u root -proot partdb'";
                        $result = execCommand($importCommand);
                        if (!$result['success']) {
                            throw new Exception('Failed to import trigger: ' . $result['output']);
                        }
                        
                        // Aufräumen
                        execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb rm /tmp/$basename");
                        error_log("Successfully imported trigger: $triggerName");
                    }
                }
                
                echo json_encode(['success' => true]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;

        case 'verify':
            try {
                // Hole die tatsächliche Tabellenstruktur
                $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb mariadb -h 127.0.0.1 -u root -proot partdb -e 'DESCRIBE led_mapping' 2>/dev/null");
                if (!$result['success']) {
                    throw new Exception('Failed to get table structure: ' . $result['output']);
                }
                $tableStructure = $result['output'];
                
                // Hole die Trigger
                $result = execCommand("cd $configDir && docker compose -f docker-compose-mariadb.yml exec -T mariadb mariadb -h 127.0.0.1 -u root -proot partdb -e 'SHOW CREATE TRIGGER assign_led_after_insert; SHOW CREATE TRIGGER delete_led_mapping_after_part_delete;' 2>/dev/null");
                if (!$result['success']) {
                    throw new Exception('Failed to get triggers: ' . $result['output']);
                }
                
                $triggers = $result['output'];

                $response = [
                    'success' => true,
                    'message' => 'Verification completed successfully',
                    'host' => 'localhost',
                    'port' => '3306',
                    'database' => 'partdb',
                    'username' => 'partdb',
                    'password' => $mariadbPassword,
                    'tableStructure' => $tableStructure,
                    'triggers' => $triggers
                ];
                
                outputJSON($response);
                
            } catch (Exception $e) {
                outputJSON([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
            }
            break;

        default:
            throw new Exception('Unknown step: ' . $step);
    }
} catch (Exception $e) {
    error_log('Database setup error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 