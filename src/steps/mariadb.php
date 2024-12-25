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
            <span class="status-text">Starting MariaDB...</span>
        </div>
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Checking database connection...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button install" onclick="setupMariaDB()">Install MariaDB</button>
        <button class="button next" onclick="navigateToStep('database')" style="display: none;">Continue</button>
    </div>
</div>

<script>
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

async function setupMariaDB() {
    try {
        // Setup-Status anzeigen
        document.querySelector('.setup-status').style.display = 'block';
        document.querySelector('.button.install').disabled = true;
        
        // 1. Konfiguration speichern
        updateStatus(0, 'pending');
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
        updateStatus(0, 'success');
        
        // 2. Verzeichnisse erstellen
        updateStatus(1, 'pending');
        const dirResponse = await fetch('ajax/create_mariadb_dirs.php');
        let dirData = await dirResponse.json();
        if (!dirData.success) {
            throw new Error(dirData.error || 'Failed to create directories');
        }
        updateStatus(1, 'success');
        
        // 3. MariaDB starten
        updateStatus(2, 'pending');
        const setupResponse = await fetch('ajax/setup_mariadb.php');
        let setupData = await setupResponse.json();
        if (!setupData.success) {
            throw new Error(setupData.error || 'Failed to start MariaDB');
        }
        updateStatus(2, 'success');
        
        // 4. Verbindung prüfen
        updateStatus(3, 'pending');
        const checkResponse = await fetch('ajax/check_mariadb.php');
        let checkData = await checkResponse.json();
        if (!checkData.success) {
            throw new Error(checkData.error || 'Failed to connect to MariaDB');
        }
        updateStatus(3, 'success');
        
        // Setup erfolgreich
        showSuccess('MariaDB setup completed successfully!');
        document.querySelector('.button.install').style.display = 'none';
        document.querySelector('.button.next').style.display = 'inline-block';
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        document.querySelector('.button.install').disabled = false;
        
        // Zeige Fehler im Status an
        const statusSteps = document.querySelectorAll('.status-step');
        for (let i = 0; i < statusSteps.length; i++) {
            const icon = statusSteps[i].querySelector('.status-icon');
            if (icon.textContent === '⏳') {
                updateStatus(i, 'error');
            }
        }
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

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    // Entferne vorherige Nachrichten
    document.querySelectorAll('.success-message, .error-message').forEach(el => el.remove());
    
    document.querySelector('.button-group').before(errorDiv);
}
</script> 