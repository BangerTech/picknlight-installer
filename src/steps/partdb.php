<div class="partdb-step">
    <h2>Part-DB Configuration</h2>
    
    <form id="partdbForm" class="setup-form">
        <div class="form-group">
            <label for="useTraefik">Use Traefik Reverse Proxy?</label>
            <select id="useTraefik" name="useTraefik" class="form-control">
                <option value="0">No (Local Access Only)</option>
                <option value="1">Yes (With Traefik)</option>
            </select>
        </div>

        <div class="form-group traefik-options" style="display: none;">
            <label for="partdbDomain">Part-DB Domain</label>
            <input type="text" id="partdbDomain" name="partdbDomain" 
                   placeholder="partdb.yourdomain.com" class="form-control">
        </div>

        <div class="form-group">
            <label for="partdbPort">Local Port (default: 8036)</label>
            <input type="number" id="partdbPort" name="partdbPort" 
                   value="8036" class="form-control">
        </div>
    </form>

    <div class="setup-status">
        <div class="status-step" id="step-config" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Saving configuration...</span>
        </div>
        <div class="status-step" id="step-directories" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating directories...</span>
        </div>
        <div class="status-step" id="step-container" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Starting Part-DB container...</span>
        </div>
        <div class="status-step" id="step-database" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Setting up database...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button primary" id="actionButton" onclick="installPartDB()">Install Part-DB</button>
    </div>
</div>

<script>
document.getElementById('useTraefik').addEventListener('change', function() {
    const traefikOptions = document.querySelector('.traefik-options');
    traefikOptions.style.display = this.value === '1' ? 'block' : 'none';
});

async function installPartDB() {
    const actionButton = document.getElementById('actionButton');
    try {
        actionButton.textContent = 'Installing...';
        actionButton.disabled = true;
        document.querySelector('.setup-status').style.display = 'block';
        
        const steps = ['config', 'directories', 'container', 'database'];
        steps.forEach(step => updateStatus(step, 'pending'));
        
        const formData = new FormData(document.getElementById('partdbForm'));
        const saveResponse = await fetch('ajax/save_partdb_config.php', {
            method: 'POST',
            body: formData
        });
        
        const saveData = await saveResponse.json();
        if (!saveData.success) {
            throw new Error(saveData.error || 'Failed to save configuration');
        }
        updateStatus('config', 'success');
        
        const response = await fetch('ajax/setup_partdb.php');
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
                                updateStatus(step, 'pending', data.progress || null);
                            }
                        }
                    }
                } catch (e) {
                    console.error('Failed to parse update:', e, 'Content:', update);
                }
            }
        }
        
        steps.forEach(step => updateStatus(step, 'success'));
        showSuccess('Part-DB installed successfully!');
        actionButton.textContent = 'Continue';
        actionButton.disabled = false;
        actionButton.onclick = () => navigateToStep('mariadb');
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        actionButton.textContent = 'Install Part-DB';
        actionButton.disabled = false;
    }
}
</script> 