<div class="database-step">
    <h2>Database Configuration</h2>
    
    <div id="dbSetupProgress">
        <div class="progress-item">
            <h3>1. Creating MariaDB Container</h3>
            <div class="status pending">Pending...</div>
        </div>
        
        <div class="progress-item">
            <h3>2. Creating Database</h3>
            <div class="status">Waiting...</div>
        </div>
        
        <div class="progress-item">
            <h3>3. Creating LED Mapping Table</h3>
            <div class="status">Waiting...</div>
        </div>
        
        <div class="progress-item">
            <h3>4. Importing Triggers</h3>
            <div class="status">Waiting...</div>
        </div>
    </div>

    <div class="button-group">
        <button class="button previous" onclick="previousStep()">Back</button>
        <button class="button next" onclick="startDatabaseSetup()" id="startDbSetup">Start Database Setup</button>
        <button class="button next" onclick="nextStep()" id="continueButton" style="display: none;">Continue</button>
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
async function startDatabaseSetup() {
    document.getElementById('startDbSetup').disabled = true;
    
    // Führe die Datenbankeinrichtung Schritt für Schritt aus
    const steps = [
        'create_mariadb',
        'create_database',
        'create_table',
        'import_triggers'
    ];

    for (let i = 0; i < steps.length; i++) {
        const response = await fetch(`ajax/database_setup.php?step=${steps[i]}`);
        const data = await response.json();
        
        const statusElement = document.querySelectorAll('.status')[i];
        if (data.success) {
            statusElement.className = 'status success';
            statusElement.textContent = '✓ Complete';
        } else {
            statusElement.className = 'status error';
            statusElement.textContent = '✗ Error: ' + data.error;
            return;
        }
    }

    document.getElementById('continueButton').style.display = 'block';
}

async function setupDatabase() {
    try {
        console.log('Starting database setup...');
        updateStepStatus('database', 'pending');
        
        const response = await fetch('/ajax/database_setup.php?step=create_database');
        console.log('Database setup response:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Database setup data:', data);
        
        if (data.success) {
            console.log('Database setup successful');
            updateStepStatus('database', 'complete');
            return true;
        } else {
            console.error('Database setup failed:', data.error);
            updateStepStatus('database', 'error', data.error || 'Failed to create database');
            return false;
        }
    } catch (error) {
        console.error('Error creating database:', error);
        updateStepStatus('database', 'error', 'Network error while creating database');
        return false;
    }
}

window.setupDatabase = setupDatabase;
</script> 