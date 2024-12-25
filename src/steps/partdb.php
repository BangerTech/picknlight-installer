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
            <label for="partdbPort">Local Port (default: 8034)</label>
            <input type="number" id="partdbPort" name="partdbPort" 
                   value="8034" class="form-control">
        </div>

        <div class="form-group">
            <label for="instanceName">Instance Name</label>
            <input type="text" id="instanceName" name="instanceName" 
                   value="Pick'n'Light" class="form-control">
        </div>

        <div class="form-group">
            <label for="defaultLang">Default Language</label>
            <select id="defaultLang" name="defaultLang" class="form-control">
                <option value="de">Deutsch</option>
                <option value="en">English</option>
            </select>
        </div>
    </form>

    <div id="step-partdb" class="step-status">
        <div class="status">
            <span class="status-text">Pending...</span>
            <div class="spinner"></div>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button next" onclick="savePartDBConfig()">Next</button>
    </div>
</div>

<script>
document.getElementById('useTraefik').addEventListener('change', function() {
    const traefikOptions = document.querySelector('.traefik-options');
    traefikOptions.style.display = this.value === '1' ? 'block' : 'none';
});

async function savePartDBConfig() {
    try {
        const formData = new FormData(document.getElementById('partdbForm'));
        const response = await fetch('ajax/save_partdb_config.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            await setupPartDB();
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