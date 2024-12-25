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

    <div id="step-nodered" class="step-status" style="display: none;">
        <div class="status">
            <span class="status-text">Pending...</span>
            <div class="spinner"></div>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button save" onclick="saveNodeRedConfig()">Save Configuration</button>
        <button class="button next" onclick="startNodeRed()" style="display: none;">Start Node-RED</button>
    </div>
</div>

<script>
document.getElementById('useTraefik').addEventListener('change', function() {
    const traefikOptions = document.querySelector('.traefik-options');
    traefikOptions.style.display = this.value === '1' ? 'block' : 'none';
});

async function saveNodeRedConfig() {
    try {
        const formData = new FormData(document.getElementById('noderedForm'));
        const response = await fetch('ajax/save_nodered_config.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            showSuccess('Configuration saved successfully');
            document.querySelector('.button.save').style.display = 'none';
            document.querySelector('.button.next').style.display = 'inline-block';
        } else {
            showError(data.error || 'Failed to save configuration');
        }
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
    }
}

async function startNodeRed() {
    try {
        const statusElement = document.getElementById('step-nodered');
        statusElement.style.display = 'block';
        
        const response = await fetch('ajax/setup_nodered.php');
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Node-RED started successfully');
            nextStep();
        } else {
            showError(data.error || 'Failed to start Node-RED');
            statusElement.style.display = 'none';
        }
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        document.getElementById('step-nodered').style.display = 'none';
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