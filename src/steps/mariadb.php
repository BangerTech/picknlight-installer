<div class="mariadb-step">
    <h2>MariaDB Setup</h2>
    
    <form id="mariadbForm" class="setup-form">
        <div class="form-group">
            <label for="rootPassword">MariaDB Root Password</label>
            <input type="password" id="rootPassword" name="rootPassword" 
                   value="root" class="form-control">
            <small class="form-text">Password for the MariaDB root user</small>
        </div>

        <div class="form-group">
            <label for="dbPassword">Database User Password</label>
            <input type="password" id="dbPassword" name="dbPassword" 
                   value="root" class="form-control">
            <small class="form-text">Password for the Part-DB database user</small>
        </div>

        <div class="form-group">
            <label for="dbPort">MariaDB Port (default: 3306)</label>
            <input type="number" id="dbPort" name="dbPort" 
                   value="3306" class="form-control">
            <small class="form-text">Local port for MariaDB access</small>
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
        <div class="status-step" id="step-mariadb" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Starting MariaDB...</span>
        </div>
        <div class="status-step" id="step-check" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Checking database connection...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button primary" id="actionButton" onclick="setupMariaDB()">Install MariaDB</button>
    </div>
</div>

<script>
async function setupMariaDB() {
    const actionButton = document.getElementById('actionButton');
    try {
        actionButton.textContent = 'Installing...';
        actionButton.disabled = true;
        document.querySelector('.setup-status').style.display = 'block';
        
        // 1. Konfiguration speichern
        updateStatus('config', 'pending');
        const formData = new FormData(document.getElementById('mariadbForm'));
        const saveResponse = await fetch('ajax/save_mariadb_config.php', {
            method: 'POST',
            body: formData
        });
        
        if (!saveResponse.ok) {
            throw new Error(`HTTP error! status: ${saveResponse.status}`);
        }
        
        let saveData = await saveResponse.json();
        if (!saveData.success) {
            throw new Error(saveData.error || 'Failed to save configuration');
        }
        updateStatus('config', 'success');
        
        // 2. Verzeichnisse erstellen
        updateStatus('directories', 'pending');
        const dirResponse = await fetch('ajax/create_mariadb_dirs.php');
        let dirData = await dirResponse.json();
        if (!dirData.success) {
            throw new Error(dirData.error || 'Failed to create directories');
        }
        updateStatus('directories', 'success');
        
        // 3. MariaDB starten
        updateStatus('mariadb', 'pending');
        const setupResponse = await fetch('ajax/setup_mariadb.php');
        let setupData = await setupResponse.json();
        if (!setupData.success) {
            throw new Error(setupData.error || 'Failed to start MariaDB');
        }
        updateStatus('mariadb', 'success');
        
        // 4. Verbindung prüfen
        updateStatus('check', 'pending');
        const checkResponse = await fetch('ajax/check_mariadb.php');
        let checkData = await checkResponse.json();
        if (!checkData.success) {
            throw new Error(checkData.error || 'Failed to connect to MariaDB');
        }
        updateStatus('check', 'success');
        
        // Setup erfolgreich
        actionButton.textContent = 'Continue';
        actionButton.disabled = false;
        actionButton.onclick = () => navigateToStep('database');
        showSuccess('MariaDB setup completed successfully!');
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        actionButton.textContent = 'Install MariaDB';
        actionButton.disabled = false;
    }
}
</script> 