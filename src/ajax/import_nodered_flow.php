<?php
header('Content-Type: application/json');

function execCommand($command) {
    exec($command . " 2>&1", $output, $return_var);
    error_log("Executing command: $command");
    error_log("Command output: " . implode("\n", $output));
    return [
        'success' => $return_var === 0,
        'output' => implode("\n", $output)
    ];
}

try {
    $type = $_GET['type'] ?? '';
    $configDir = getenv('CONFIG_DIR') ?: '/app/config';
    
    if ($type === 'default') {
        // Default Flow von GitHub herunterladen
        $defaultFlow = file_get_contents('https://raw.githubusercontent.com/BangerTech/picknlight-installer/main/Node-RED-flow.json');
        if ($defaultFlow === false) {
            throw new Exception('Failed to download default flow');
        }
        $flowData = $defaultFlow;
    } 
    elseif ($type === 'custom') {
        if (!isset($_FILES['flow'])) {
            throw new Exception('No flow file uploaded');
        }
        $flowData = file_get_contents($_FILES['flow']['tmp_name']);
        if ($flowData === false) {
            throw new Exception('Failed to read uploaded flow');
        }
    } 
    else {
        throw new Exception('Invalid import type');
    }
    
    // Flow-Datei temporär speichern
    $tempFile = "$configDir/temp_flow.json";
    if (file_put_contents($tempFile, $flowData) === false) {
        throw new Exception('Failed to save flow file');
    }
    
    // Node-RED API aufrufen um Flow zu importieren
    $noderedConfig = json_decode(file_get_contents("$configDir/nodered-config.json"), true);
    if ($noderedConfig === null) {
        throw new Exception('Failed to load Node-RED configuration');
    }
    
    $port = $noderedConfig['port'] ?? '1880';
    $useTraefik = $noderedConfig['useTraefik'] ?? false;
    
    // Bestimme die Node-RED URL
    if ($useTraefik) {
        $noderedUrl = "https://{$noderedConfig['domain']}";
    } else {
        // Für lokale Entwicklung/Docker-Netzwerk
        $noderedUrl = "http://nodered:1880";
    }
    
    // Warte bis Node-RED bereit ist
    $retries = 0;
    while ($retries < 30) {
        $ch = curl_init("$noderedUrl/flows");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("Trying to connect to Node-RED at $noderedUrl (Attempt $retries)");
        error_log("HTTP Code: $httpCode");
        if ($error) {
            error_log("Curl error: $error");
        }
        
        if ($httpCode === 200) {
            error_log("Successfully connected to Node-RED");
            break;
        }
        
        $retries++;
        sleep(1);
    }
    
    if ($retries === 30) {
        throw new Exception('Node-RED is not responding');
    }
    
    // Flow importieren
    $ch = curl_init("$noderedUrl/flows");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $flowData);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Node-RED-Deployment-Type: full'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 204) {
        throw new Exception('Failed to import flow: ' . $response);
    }
    
    // Temporäre Datei löschen
    unlink($tempFile);
    
    echo json_encode([
        'success' => true,
        'message' => 'Flow imported successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Flow import error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} 