<div class="nodered-step">
    <h2>Node-RED Configuration</h2>
    
    <form id="noderedForm" class="setup-form">
        <div class="form-group">
            <label for="useTraefik">Use Traefik Reverse Proxy?</label>
            <select id="useTraefik" name="useTraefik" class="form-control">
                <option value="0">No (Local Access Only)</option>
                <option value="1">Yes (With Traefik)</option>
            </select>
        </div>

        <div class="form-group traefik-options" style="display: none;">
            <label for="noderedDomain">Node-RED Domain</label>
            <input type="text" id="noderedDomain" name="noderedDomain" 
                   placeholder="nodered.yourdomain.com" class="form-control">
        </div>

        <div class="form-group">
            <label for="noderedPort">Local Port (default: 8035)</label>
            <input type="number" id="noderedPort" name="noderedPort" 
                   value="8035" class="form-control">
        </div>
    </form>

    <div id="step-nodered" class="step-status">
        <div class="status">
            <span class="status-text">Pending...</span>
            <div class="spinner"></div>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button next" onclick="saveNodeRedConfig()">Next</button>
    </div>
</div>

<script>
document.getElementById('useTraefik').addEventListener('change', function() {
    const traefikOptions = document.querySelector('.traefik-options');
    traefikOptions.style.display = this.value === '1' ? 'block' : 'none';
});

async function saveNodeRedConfig() {
    try {
        // Zeige Status an
        const statusElement = document.querySelector('#step-nodered .status-text');
        if (statusElement) {
            statusElement.textContent = 'Saving configuration...';
        }

        // 1. Speichere die Konfiguration
        const formData = new FormData(document.getElementById('noderedForm'));
        const saveResponse = await fetch('ajax/save_nodered_config.php', {
            method: 'POST',
            body: formData
        });
        
        if (!saveResponse.ok) {
            throw new Error(`HTTP error! status: ${saveResponse.status}`);
        }
        
        const saveData = await saveResponse.json();
        if (!saveData.success) {
            throw new Error(saveData.error || 'Failed to save configuration');
        }

        // 2. Starte den Container
        console.log('Configuration saved, starting Node-RED...');
        const setupResponse = await fetch('ajax/setup_nodered.php');
        
        if (!setupResponse.ok) {
            throw new Error(`HTTP error! status: ${setupResponse.status}`);
        }
        
        const setupData = await setupResponse.json();
        if (!setupData.success) {
            throw new Error(setupData.error || 'Failed to setup Node-RED');
        }

        // 3. Wenn alles erfolgreich war, gehe zum nächsten Schritt
        console.log('Node-RED setup complete');
        nextStep();
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        
        // Zeige Fehler im Status an
        const statusElement = document.querySelector('#step-nodered .status-text');
        if (statusElement) {
            statusElement.textContent = 'Error: ' + error.message;
        }
        const statusDiv = document.querySelector('#step-nodered .status');
        if (statusDiv) {
            statusDiv.classList.remove('pending');
            statusDiv.classList.add('error');
        }
    }
}
</script> 