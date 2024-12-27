<div class="database-step">
    <h2>Database Setup</h2>
    
    <div class="setup-status">
        <div class="status-step" id="step-create-db" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating database 'partdb'...</span>
        </div>
        <div class="status-step" id="step-create-table" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Creating LED mapping table...</span>
        </div>
        <div class="status-step" id="step-import-triggers" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Importing triggers...</span>
        </div>
        <div class="status-step" id="step-verify" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Verifying setup...</span>
        </div>
    </div>

    <!-- Container für die Datenbank-Informationen -->
    <div class="database-info" style="display: none;">
        <div class="info-tabs">
            <button class="tab-button active" onclick="showTab('connection')">Connection</button>
            <button class="tab-button" onclick="showTab('table')">Table Structure</button>
            <button class="tab-button" onclick="showTab('triggers')">Triggers</button>
        </div>

        <div class="tab-content">
            <!-- Connection Tab -->
            <div id="connection-tab" class="tab-pane active">
                <h3>Database Connection Information</h3>
                <div class="info-grid">
                    <div class="info-item">
                        <label>Host:</label>
                        <span id="dbHost"></span>
                    </div>
                    <div class="info-item">
                        <label>Port:</label>
                        <span id="dbPort"></span>
                    </div>
                    <div class="info-item">
                        <label>Database:</label>
                        <span id="dbName"></span>
                    </div>
                    <div class="info-item">
                        <label>Username:</label>
                        <span id="dbUser"></span>
                    </div>
                    <div class="info-item">
                        <label>Password:</label>
                        <span id="dbPass"></span>
                    </div>
                </div>
            </div>

            <!-- Table Structure Tab -->
            <div id="table-tab" class="tab-pane">
                <h3>LED Mapping Table Structure</h3>
                <div class="code-block">
                    <pre id="tableStructure"></pre>
                </div>
            </div>

            <!-- Triggers Tab -->
            <div id="triggers-tab" class="tab-pane">
                <h3>Database Triggers</h3>
                <div class="code-block">
                    <pre id="triggerCode"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Migration Status Container -->
    <div class="migration-status" style="display: none;">
        <div class="status-step" id="step-stop-partdb" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Stopping Part-DB container...</span>
        </div>
        <div class="status-step" id="step-update-config" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Updating database configuration...</span>
        </div>
        <div class="status-step" id="step-start-partdb" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Starting Part-DB container...</span>
        </div>
        <div class="status-step" id="step-migrate-db" data-status="waiting">
            <span class="status-icon">⭕</span>
            <span class="status-text">Migrating database...</span>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button primary" id="setupButton" onclick="setupDatabase()">Setup Database</button>
        <button class="button primary" id="migrateButton" onclick="migratePartDB()" style="display: none;">Migrate to Part-DB</button>
    </div>
</div>

<style>
.database-info {
    margin-top: 30px;
    padding: 20px;
    background: var(--card-background);
    border-radius: 12px;
    box-shadow: var(--shadow);
}

.info-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    border-bottom: 1px solid var(--border-color);
    padding-bottom: 8px;
}

.tab-button {
    padding: 8px 16px;
    border: none;
    background: none;
    color: var(--text-color);
    cursor: pointer;
    opacity: 0.7;
    transition: all 0.3s ease;
    border-radius: 6px;
}

.tab-button:hover {
    opacity: 1;
    background: var(--background-color);
}

.tab-button.active {
    opacity: 1;
    background: var(--primary-color);
    color: white;
}

.tab-pane {
    display: none;
}

.tab-pane.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

.code-block {
    background: var(--background-color);
    border-radius: 8px;
    padding: 16px;
    overflow-x: auto;
    white-space: pre;
    max-height: 500px;
    overflow-y: auto;
}

.code-block pre {
    margin: 0;
    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', 'Consolas', monospace;
    font-size: 13px;
    line-height: 1.5;
    color: var(--text-color);
    tab-size: 4;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.info-grid {
    display: grid;
    gap: 16px;
    max-width: 500px;
    margin: 0 auto;
}

.info-item {
    display: grid;
    grid-template-columns: 120px 1fr;
    align-items: center;
    padding: 12px;
    background: var(--background-color);
    border-radius: 8px;
}

.info-item label {
    font-weight: 500;
    color: var(--text-color);
}

.info-item span {
    color: var(--primary-color);
    font-family: monospace;
    font-size: 14px;
    user-select: all;
}

.tab-pane h3 {
    text-align: center;
    margin-bottom: 20px;
    color: var(--text-color);
    font-weight: 500;
}
</style>

<script>
function showTab(tabName) {
    // Deaktiviere alle Tabs
    document.querySelectorAll('.tab-button').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelectorAll('.tab-pane').forEach(pane => {
        pane.classList.remove('active');
    });
    
    // Aktiviere ausgewählten Tab
    document.querySelector(`button[onclick="showTab('${tabName}')"]`).classList.add('active');
    document.getElementById(`${tabName}-tab`).classList.add('active');
}

async function setupDatabase() {
    const setupButton = document.getElementById('setupButton');
    const migrateButton = document.getElementById('migrateButton');
    try {
        setupButton.textContent = 'Setting up...';
        setupButton.disabled = true;
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
        
        // Anzeigen der Datenbank-Informationen
        document.getElementById('dbHost').textContent = verifyData.host || 'localhost';
        document.getElementById('dbPort').textContent = verifyData.port || '3306';
        document.getElementById('dbName').textContent = verifyData.database || 'partdb';
        document.getElementById('dbUser').textContent = verifyData.username || 'partdb';
        document.getElementById('dbPass').textContent = verifyData.password || '';
        
        // Formatierte Tabellen-Struktur anzeigen
        if (verifyData.tableStructure) {
            const formattedTable = verifyData.tableStructure
                .replace(/,/g, ',\n    ')
                .replace(/\(/g, ' (\n    ')
                .replace(/\)/g, '\n)');
            document.getElementById('tableStructure').textContent = formattedTable;
        } else {
            document.getElementById('tableStructure').textContent = 'Table structure not available';
        }
        
        // Formatierte Trigger anzeigen
        if (verifyData.triggers) {
            const formattedTriggers = verifyData.triggers
                .split('\n\n')
                .join('\n\n/* ---------------------------------------- */\n\n');
            document.getElementById('triggerCode').textContent = formattedTriggers;
        } else {
            document.getElementById('triggerCode').textContent = 'No triggers defined';
        }
        
        // UI-Updates
        setupButton.style.display = 'none';
        migrateButton.style.display = 'inline-block';
        document.querySelector('.database-info').style.display = 'block';
        showTab('connection');
        
        showSuccess('Database setup completed successfully!');
        
        // Navigiere zum nächsten Schritt
        navigateToStep('migrate');
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        setupButton.textContent = 'Setup Database';
        setupButton.disabled = false;
    }
}

async function migratePartDB() {
    const migrateButton = document.getElementById('migrateButton');
    try {
        console.log('Starting Part-DB integration...');
        migrateButton.disabled = true;
        document.querySelector('.migration-status').style.display = 'block';
        
        updateStatus('stop-partdb', 'pending');
        updateStatus('update-config', 'pending');
        updateStatus('start-partdb', 'pending');
        updateStatus('migrate-db', 'pending');
        
        const response = await fetch('ajax/database_setup.php?step=migrate');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to integrate Part-DB');
        }
        
        updateStatus('stop-partdb', 'success');
        updateStatus('update-config', 'success');
        updateStatus('start-partdb', 'success');
        updateStatus('migrate-db', 'success');
        
        migrateButton.textContent = 'Continue';
        migrateButton.disabled = false;
        migrateButton.onclick = () => navigateToStep('final');
        showSuccess('Part-DB integration completed successfully!');
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        migrateButton.textContent = 'Migrate to Part-DB';
        migrateButton.disabled = false;
    }
}
</script> 