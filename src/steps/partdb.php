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
            <span class="status-text">Starting containers...</span>
        </div>
        <div class="status-step">
            <span class="status-icon">⭕</span>
            <span class="status-text">Initializing database...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button install" onclick="installPartDB()">Install Part-DB</button>
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

async function installPartDB() {
    try {
        // Setup-Status anzeigen
        document.querySelector('.setup-status').style.display = 'block';
        document.querySelector('.button.install').disabled = true;
        
        // 1. Konfiguration speichern
        updateStatus(0, 'pending');
        const formData = new FormData(document.getElementById('partdbForm'));
        const saveResponse = await fetch('ajax/save_partdb_config.php', {
            method: 'POST',
            body: formData
        });
        
        if (!saveResponse.ok) {
            throw new Error(`HTTP error! status: ${saveResponse.status}`);
        }
        
        let saveData;
        try {
            saveData = await saveResponse.json();
        } catch (e) {
            const text = await saveResponse.text();
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid JSON');
        }
        
        if (!saveData.success) {
            throw new Error(saveData.error || 'Failed to save configuration');
        }
        updateStatus(0, 'success');
        
        // 2. Container starten
        updateStatus(1, 'pending');
        const setupResponse = await fetch('ajax/setup_partdb.php');
        
        if (!setupResponse.ok) {
            throw new Error(`HTTP error! status: ${setupResponse.status}`);
        }
        
        let setupData;
        try {
            setupData = await setupResponse.json();
        } catch (e) {
            const text = await setupResponse.text();
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid JSON');
        }
        
        if (!setupData.success) {
            throw new Error(setupData.error || 'Failed to start Part-DB');
        }
        updateStatus(1, 'success');
        
        // 3. Container-Status prüfen
        updateStatus(2, 'pending', 'Waiting for services to start...');
        await new Promise(resolve => setTimeout(resolve, 5000)); // Warte 5 Sekunden
        const checkResponse = await fetch('ajax/check_partdb.php');
        
        if (!checkResponse.ok) {
            throw new Error(`HTTP error! status: ${checkResponse.status}`);
        }
        
        let checkData;
        try {
            checkData = await checkResponse.json();
        } catch (e) {
            const text = await checkResponse.text();
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid JSON');
        }
        
        if (!checkData.success) {
            throw new Error(checkData.error || 'Part-DB is not running properly');
        }
        updateStatus(2, 'success');
        
        // 4. Datenbank-Initialisierung prüfen
        updateStatus(3, 'pending', 'Waiting for database initialization...');
        await new Promise(resolve => setTimeout(resolve, 5000)); // Warte 5 Sekunden
        const initResponse = await fetch('ajax/check_partdb.php');
        
        if (!initResponse.ok) {
            throw new Error(`HTTP error! status: ${initResponse.status}`);
        }
        
        let initData;
        try {
            initData = await initResponse.json();
        } catch (e) {
            const text = await initResponse.text();
            console.error('Invalid JSON response:', text);
            throw new Error('Server returned invalid JSON');
        }
        
        if (!initData.success) {
            throw new Error(initData.error || 'Failed to initialize database');
        }
        updateStatus(3, 'success');
        
        // Installation erfolgreich
        if (setupData.password) {
            showSuccess(`Part-DB was successfully installed! Login credentials:\nUsername: admin\nPassword: ${setupData.password}`);
        } else {
            showSuccess('Part-DB was successfully installed! Default login is admin/admin');
        }
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
                icon.textContent = '❌';
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
</script> 