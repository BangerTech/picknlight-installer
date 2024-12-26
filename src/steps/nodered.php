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
        <div class="status-step" id="step-container">
            <span class="status-icon">⭕</span>
            <span class="status-text">Starting Node-RED container...</span>
        </div>
        <div class="status-step" id="step-nodes">
            <span class="status-icon">⭕</span>
            <span class="status-text">Installing required nodes...</span>
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
    const textElement = statusStep.querySelector('.status-text');
    
    if (message) {
        textElement.textContent = message;
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
        
        const steps = ['config', 'directories', 'container', 'nodes'];
        steps.forEach(step => updateStatus(step, 'pending'));
        
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
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        
        while (true) {
            const {value, done} = await reader.read();
            if (done) break;
            
            const chunk = decoder.decode(value);
            const updates = chunk.split('\n').filter(line => line.trim());
            
            for (const update of updates) {
                try {
                    if (!update.trim()) continue;
                    const data = JSON.parse(update);
                    if (data.status) {
                        const currentStep = data.status.step;
                        for (let i = 0; i < steps.length; i++) {
                            const step = steps[i];
                            if (steps.indexOf(step) < steps.indexOf(currentStep)) {
                                updateStatus(step, 'success');
                            } else if (step === currentStep) {
                                if (currentStep === 'container' && data.progress === 'Node-RED container started successfully') {
                                    updateStatus(step, 'success', data.progress);
                                } else {
                                    updateStatus(step, 'pending', data.progress || null);
                                }
                            }
                        }
                    }
                } catch (e) {
                    if (update.trim()) {
                        console.error('Failed to parse update:', e, 'Content:', update);
                    }
                }
            }
        }
        
        steps.forEach(step => updateStatus(step, 'success'));
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
    
    document.querySelectorAll('.success-message, .error-message').forEach(el => el.remove());
    
    document.querySelector('.button-group').before(successDiv);
}
</script> 