<div class="migrate-step">
    <h2>Part-DB Migration</h2>
    
    <div class="migration-status" style="margin: 30px 0;">
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
        <button class="button primary" id="migrateButton" onclick="migratePartDB()">Migrate to Part-DB</button>
    </div>
</div>

<style>
.migrate-step {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.migration-status {
    background: var(--card-background);
    border-radius: 12px;
    padding: 20px;
    box-shadow: var(--shadow);
}

.status-step {
    display: flex;
    align-items: center;
    margin: 10px 0;
    padding: 12px;
    background: var(--background-color);
    border-radius: 8px;
    transition: all 0.3s ease;
}

.status-icon {
    margin-right: 15px;
    font-size: 20px;
}

.status-text {
    color: var(--text-color);
    flex: 1;
}

.button-group {
    margin-top: 30px;
    display: flex;
    gap: 10px;
    justify-content: center;
}

h2 {
    color: var(--text-color);
    text-align: center;
    margin-bottom: 30px;
}
</style>

<script>
async function migratePartDB() {
    const migrateButton = document.getElementById('migrateButton');
    try {
        console.log('Starting Part-DB integration...');
        migrateButton.disabled = true;
        
        updateStatus('stop-partdb', 'pending');
        updateStatus('update-config', 'pending');
        updateStatus('start-partdb', 'pending');
        updateStatus('migrate-db', 'pending');
        
        const response = await fetch('ajax/migrate_partdb.php');
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