<?php
$configDir = getenv('CONFIG_DIR') ?: '/app/config';

// Lade Node-RED Konfiguration
$noderedConfig = [];
$noderedConfigFile = $configDir . '/nodered-config.json';
if (file_exists($noderedConfigFile)) {
    $noderedConfig = json_decode(file_get_contents($noderedConfigFile), true) ?: [];
}

// Lade Part-DB Konfiguration
$partdbConfig = [];
$partdbConfigFile = $configDir . '/partdb-config.json';
if (file_exists($partdbConfigFile)) {
    $partdbConfig = json_decode(file_get_contents($partdbConfigFile), true) ?: [];
}

// Bestimme die URLs basierend auf der Konfiguration
$noderedUrl = $noderedConfig['useTraefik'] 
    ? "https://{$noderedConfig['domain']}"
    : "http://localhost:{$noderedConfig['port']}";

$partdbUrl = $partdbConfig['useTraefik']
    ? "https://{$partdbConfig['domain']}"
    : "http://localhost:{$partdbConfig['port']}";
?>

<div class="final-step">
    <h2>Setup Complete!</h2>
    
    <h3>Configuration Summary</h3>
    
    <h4>Node-RED</h4>
    <p>Access URL: <a href="<?php echo $noderedUrl; ?>" target="_blank"><?php echo $noderedUrl; ?></a></p>

    <h4>Part-DB</h4>
    <p>Access URL: <a href="<?php echo $partdbUrl; ?>" target="_blank"><?php echo $partdbUrl; ?></a></p>

    <h3>Next Steps</h3>
    <ol>
        <li>Import Node-RED flows from the provided file</li>
        <li>Configure MQTT settings in Node-RED</li>
        <li>Install the ViolentMonkey browser extension</li>
        <li>Import the ViolentMonkey scripts</li>
        <li>Start adding parts to your inventory!</li>
    </ol>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button finish" onclick="window.location.href='<?php echo $partdbUrl; ?>'">Go to Part-DB</button>
    </div>
</div> 