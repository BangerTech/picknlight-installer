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

    <div class="next-steps">
        <h3>Next Steps</h3>
        <ol>
            <li>Import Node-RED flows:
                <div class="flow-import-options">
                    <button class="button import-default" onclick="importDefaultFlow()">Import Default Flow</button>
                    <div class="upload-section">
                        <label for="custom-flow" class="button">Upload Custom Flow</label>
                        <input type="file" id="custom-flow" accept=".json" style="display: none;" onchange="handleCustomFlow(this)">
                    </div>
                </div>
                <div id="import-status" style="display: none;"></div>
            </li>
            <li>Configure Node-RED nodes</li>
            <li>Test the setup</li>
        </ol>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button finish" onclick="window.location.href='<?php echo $partdbUrl; ?>'">Go to Part-DB</button>
    </div>
</div> 

<script>
async function importDefaultFlow() {
    try {
        const importStatus = document.getElementById('import-status');
        importStatus.style.display = 'block';
        importStatus.innerHTML = '⏳ Importing default flow...';
        
        const response = await fetch('ajax/import_nodered_flow.php?type=default');
        const data = await response.json();
        
        if (data.success) {
            importStatus.innerHTML = '✅ Flow imported successfully!';
        } else {
            throw new Error(data.error || 'Import failed');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('import-status').innerHTML = '❌ ' + error.message;
    }
}

async function handleCustomFlow(input) {
    try {
        const file = input.files[0];
        if (!file) return;
        
        const importStatus = document.getElementById('import-status');
        importStatus.style.display = 'block';
        importStatus.innerHTML = '⏳ Uploading custom flow...';
        
        const formData = new FormData();
        formData.append('flow', file);
        
        const response = await fetch('ajax/import_nodered_flow.php?type=custom', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            importStatus.innerHTML = '✅ Custom flow imported successfully!';
        } else {
            throw new Error(data.error || 'Import failed');
        }
    } catch (error) {
        console.error('Error:', error);
        document.getElementById('import-status').innerHTML = '❌ ' + error.message;
    }
}
</script>

<style>
.flow-import-options {
    margin: 10px 0;
    display: flex;
    gap: 10px;
    align-items: center;
}

.upload-section {
    display: inline-block;
}

#import-status {
    margin-top: 10px;
    padding: 10px;
    border-radius: 4px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}
</style> 