<div class="database-step">
    <h2>Database Setup</h2>
    
    <div class="setup-status">
        <div class="status-step" id="step-create-db">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating database 'partdb'...</span>
        </div>
        <div class="status-step" id="step-create-table">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating LED mapping table...</span>
        </div>
        <div class="status-step" id="step-import-triggers">
            <span class="status-icon">⭕</span>
            <span class="status-text">Importing triggers...</span>
        </div>
        <div class="status-step" id="step-verify">
            <span class="status-icon">⭕</span>
            <span class="status-text">Verifying setup...</span>
        </div>
    </div>

    <div class="partdb-integration-status" style="display: none;">
        <h3>Part-DB Integration</h3>
        <div class="status-step" id="step-stop-partdb">
            <span class="status-icon">⭕</span>
            <span class="status-text">Stopping Part-DB container...</span>
        </div>
        <div class="status-step" id="step-update-config">
            <span class="status-icon">⭕</span>
            <span class="status-text">Updating database configuration...</span>
        </div>
        <div class="status-step" id="step-start-partdb">
            <span class="status-icon">⭕</span>
            <span class="status-text">Starting Part-DB container...</span>
        </div>
        <div class="status-step" id="step-migrate-db">
            <span class="status-icon">⭕</span>
            <span class="status-text">Migrating database...</span>
        </div>
    </div>

    <div class="verification-output" style="display: none;">
        <h3>Database Verification</h3>
        <pre class="verification-results"></pre>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="window.previousStep()">Back</button>
        <button class="button install" onclick="setupDatabase()">Setup Database</button>
        <button class="button integrate" onclick="integratePartDB()" style="display: none;">Integrate with Part-DB</button>
        <button class="button next" onclick="navigateToStep('final')" style="display: none;">Continue</button>
    </div>
</div>

<script>
async function updateStatus(step, status, message = null) {
    const statusStep = document.getElementById(`step-${step}`);
    if (!statusStep) return;
    
    const icon = statusStep.querySelector('.status-icon');
    const text = statusStep.querySelector('.status-text');
    
    if (message) {
        text.textContent = message;
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
        document.querySelector('.button.install').disabled = true;
        document.querySelector('.setup-status').style.display = 'block';
        
        // 1. Create Database
        updateStatus('create-db', 'pending');
        const createDbResponse = await fetch('ajax/database_setup.php?step=create_database');
        const createDbData = await createDbResponse.json();
        if (!createDbData.success) {
            throw new Error(createDbData.error || 'Failed to create database');
        }
        updateStatus('create-db', 'success');
        
        // 2. Create Table
        updateStatus('create-table', 'pending');
        const createTableResponse = await fetch('ajax/database_setup.php?step=create_table');
        const createTableData = await createTableResponse.json();
        if (!createTableData.success) {
            throw new Error(createTableData.error || 'Failed to create table');
        }
        updateStatus('create-table', 'success');
        
        // 3. Import Triggers
        updateStatus('import-triggers', 'pending');
        const importTriggersResponse = await fetch('ajax/database_setup.php?step=import_triggers');
        const importTriggersData = await importTriggersResponse.json();
        if (!importTriggersData.success) {
            throw new Error(importTriggersData.error || 'Failed to import triggers');
        }
        updateStatus('import-triggers', 'success');
        
        // 4. Verify Setup
        updateStatus('verify', 'pending');
        const verifyResponse = await fetch('ajax/database_setup.php?step=verify');
        const verifyData = await verifyResponse.json();
        if (!verifyData.success) {
            throw new Error(verifyData.error || 'Verification failed');
        }
        updateStatus('verify', 'success');
        
        // Show verification results
        if (verifyData.results) {
            document.querySelector('.verification-output').style.display = 'block';
            document.querySelector('.verification-results').textContent = verifyData.results;
        }
        
        // Setup erfolgreich
        showSuccess('Database setup completed successfully!');
        document.querySelector('.button.install').style.display = 'none';
        document.querySelector('.button.integrate').style.display = 'inline-block';
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        document.querySelector('.button.install').disabled = false;
    }
}

async function integratePartDB() {
    try {
        console.log('Starting Part-DB integration...');
        document.querySelector('.button.integrate').disabled = true;
        document.querySelector('.partdb-integration-status').style.display = 'block';
        
        updateStatus('stop-partdb', 'pending');
        updateStatus('update-config', 'pending');
        updateStatus('start-partdb', 'pending');
        updateStatus('migrate-db', 'pending');
        
        console.log('Sending request to update_partdb_config.php...');
        const response = await fetch('ajax/update_partdb_config.php');
        console.log('Got response:', response);
        const data = await response.json();
        console.log('Parsed data:', data);
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to integrate Part-DB');
        }
        
        updateStatus('stop-partdb', 'success');
        updateStatus('update-config', 'success');
        updateStatus('start-partdb', 'success');
        updateStatus('migrate-db', 'success');
        
        if (data.verification) {
            document.querySelector('.verification-output').style.display = 'block';
            document.querySelector('.verification-results').textContent = data.verification;
        }
        
        showSuccess('Part-DB integration completed successfully!');
        document.querySelector('.button.integrate').style.display = 'none';
        document.querySelector('.button.next').style.display = 'inline-block';
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        document.querySelector('.button.integrate').disabled = false;
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

<style>
.verification-output {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.verification-results {
    background: #fff;
    padding: 10px;
    border: 1px solid #eee;
    border-radius: 3px;
    white-space: pre-wrap;
    font-family: monospace;
}

.partdb-integration-status {
    margin: 20px 0;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}
</style> 