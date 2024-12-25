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

    <div class="setup-status" style="display: none;">
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Saving configuration...</span>
        </div>
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating directories...</span>
        </div>
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Starting Node-RED container...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button install" onclick="installNodeRed()">Install Node-RED</button>
        <button class="button next" onclick="nextStep()" style="display: none;">Continue</button>
    </div>
</div>

<script>
document.getElementById('useTraefik').addEventListener('change', function() {
    const traefikOptions = document.querySelector('.traefik-options');
    traefikOptions.style.display = this.value === '1' ? 'block' : 'none';
});

async function updateStatus(step, status, message = null) {
    const statusSteps = document.querySelectorAll('.status-step');
    const statusStep = statusSteps[step];
    const icon = statusStep.querySelector('.status-icon');
    
    if (message) {
        statusStep.querySelector('.status-text').textContent = message;
    }
    
    switch (status) {
        case 'pending':
            icon.textContent = '⏳';
            break;
        case 'success':
            icon.textContent = '✅';
            break;
        case 'error':
            icon.textContent = '❌';
            break;
        default:
            icon.textContent = '⭕';
    }
}

async function installNodeRed() {
    try {
        // Setup-Status anzeigen
        document.querySelector('.setup-status').style.display = 'block';
        document.querySelector('.button.install').disabled = true;
        
        // 1. Konfiguration speichern
        updateStatus(0, 'pending');
        const formData = new FormData(document.getElementById('noderedForm'));
        const saveResponse = await fetch('ajax/save_nodered_config.php', {
            method: 'POST',
            body: formData
        });
        
        const saveData = await saveResponse.json();
        if (!saveData.success) {
            throw new Error(saveData.error || 'Failed to save configuration');
        }
        updateStatus(0, 'success');
        
        // 2. Container starten
        updateStatus(1, 'pending');
        const setupResponse = await fetch('ajax/setup_nodered.php');
        const setupData = await setupResponse.json();
        
        if (!setupData.success) {
            throw new Error(setupData.error || 'Failed to start Node-RED');
        }
        updateStatus(1, 'success');
        
        // 3. Container-Status prüfen
        updateStatus(2, 'pending');
        await new Promise(resolve => setTimeout(resolve, 2000)); // Warte 2 Sekunden
        const checkResponse = await fetch('ajax/check_nodered.php');
        const checkData = await checkResponse.json();
        
        if (!checkData.success) {
            throw new Error(checkData.error || 'Node-RED is not running properly');
        }
        updateStatus(2, 'success');
        
        // Setup erfolgreich
        showSuccess('Node-RED was successfully installed!');
        document.querySelector('.button.install').style.display = 'none';
        document.querySelector('.button.next').style.display = 'inline-block';
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        document.querySelector('.button.install').disabled = false;
    }
}

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    
    // Entferne vorherige Nachrichten
    document.querySelectorAll('.success-message, .error-message').forEach(el => el.remove());
    
    document.querySelector('.button-group').before(successDiv);
}
</script> 