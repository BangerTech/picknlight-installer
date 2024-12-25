<div class="database-step">
    <h2>Database Configuration</h2>
    
    <form id="dbForm" class="setup-form">
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
            <span class="status-text">Starting MariaDB...</span>
        </div>
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating database...</span>
        </div>
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Importing triggers...</span>
        </div>
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Updating Part-DB configuration...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button install" onclick="setupDatabase()">Setup Database</button>
        <button class="button next" onclick="nextStep()" style="display: none;">Continue</button>
    </div>
</div>

<div class="step" id="step-database">
    <h3>2. Creating Database</h3>
    <p>Setting up PartDB database...</p>
    <div class="status">
        <span class="status-text">Pending...</span>
        <div class="spinner"></div>
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

async function setupDatabase() {
    try {
        // Setup-Status anzeigen
        document.querySelector('.setup-status').style.display = 'block';
        document.querySelector('.button.install').disabled = true;
        
        // 1. Konfiguration speichern
        updateStatus(0, 'pending');
        const formData = new FormData(document.getElementById('dbForm'));
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
        
        // 2. MariaDB starten
        updateStatus(1, 'pending');
        const setupResponse = await fetch('ajax/setup_mariadb.php');
        let setupData = await setupResponse.json();
        if (!setupData.success) {
            throw new Error(setupData.error || 'Failed to start MariaDB');
        }
        updateStatus(1, 'success');
        
        // 3. Datenbank erstellen
        updateStatus(2, 'pending');
        const dbResponse = await fetch('ajax/create_database.php');
        let dbData = await dbResponse.json();
        if (!dbData.success) {
            throw new Error(dbData.error || 'Failed to create database');
        }
        updateStatus(2, 'success');
        
        // 4. Trigger importieren
        updateStatus(3, 'pending');
        const triggerResponse = await fetch('ajax/import_triggers.php');
        let triggerData = await triggerResponse.json();
        if (!triggerData.success) {
            throw new Error(triggerData.error || 'Failed to import triggers');
        }
        updateStatus(3, 'success');
        
        // 5. Part-DB Konfiguration aktualisieren
        updateStatus(4, 'pending');
        const updateResponse = await fetch('ajax/update_partdb_config.php');
        let updateData = await updateResponse.json();
        if (!updateData.success) {
            throw new Error(updateData.error || 'Failed to update Part-DB configuration');
        }
        updateStatus(4, 'success');
        
        // Setup erfolgreich
        showSuccess('Database setup completed successfully!');
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

window.setupDatabase = setupDatabase;
</script>

<style>
.form-text {
    font-size: 0.875em;
    color: #666;
    margin-top: 0.25rem;
}
</style> 