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
        const formData = new FormData(document.getElementById('noderedForm'));
        const response = await fetch('ajax/save_nodered_config.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            await setupNodeRed();
            nextStep();
        } else {
            showError(data.error || 'Failed to save configuration');
        }
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
    }
}
</script> 