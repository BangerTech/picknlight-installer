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

    <div class="setup-status">
        <div class="status-step" id="step-config">
            <span class="status-icon">⭕</span>
            <span class="status-text">Saving configuration...</span>
        </div>
        <div class="status-step" id="step-directories">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating directories...</span>
        </div>
        <div class="status-step" id="step-nodes">
            <span class="status-icon">⭕</span>
            <span class="status-text">Installing required nodes...</span>
        </div>
        <div class="status-step" id="step-container">
            <span class="status-icon">⭕</span>
            <span class="status-text">Starting Node-RED container...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button install" onclick="installNodeRed()">Install Node-RED</button>
        <button class="button next" onclick="navigateToStep('partdb')" style="display: none;">Continue</button>
    </div>
</div>

<script>
document.getElementById('useTraefik').addEventListener('change', function() {
    const traefikOptions = document.querySelector('.traefik-options');
    traefikOptions.style.display = this.value === '1' ? 'block' : 'none';
});

async function updateStatus(step, status, message = null) {
    const statusStep = document.getElementById(`step-${step}`);
    if (!statusStep) return;
    
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
        document.querySelector('.button.install').disabled = true;
        document.querySelector('.setup-status').style.display = 'block';
        
        updateStatus('config', 'pending');
        updateStatus('directories', 'pending');
        updateStatus('nodes', 'pending');
        updateStatus('container', 'pending');
        
        const formData = new FormData(document.getElementById('noderedForm'));
        const saveResponse = await fetch('ajax/save_nodered_config.php', {
            method: 'POST',
            body: formData
        });
        
        const saveData = await saveResponse.json();
        if (!saveData.success) {
            throw new Error(saveData.error || 'Failed to save configuration');
        }
        updateStatus('config', 'success');
        
        const response = await fetch('ajax/setup_nodered.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Installation failed');
        }
        
        if (data.status) {
            const steps = ['config', 'directories', 'nodes', 'container'];
            const currentStepIndex = steps.indexOf(data.status.step);
            
            for (let i = 0; i <= currentStepIndex; i++) {
                updateStatus(steps[i], 'success');
            }
            
            if (data.progress) {
                const currentStep = steps[currentStepIndex];
                const statusStep = document.getElementById(`step-${currentStep}`);
                if (statusStep) {
                    statusStep.querySelector('.status-text').textContent = data.progress;
                }
            }
        }
        
        showSuccess('Node-RED installed successfully!');
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