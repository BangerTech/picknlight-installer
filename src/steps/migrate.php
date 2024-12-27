<div class="migrate-step">
    <h2>Part-DB Migration</h2>
    
    <div class="migration-status">
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

<script>
async function migratePartDB() {
    const migrateButton = document.getElementById('migrateButton');
    try {
        console.log('Starting Part-DB integration...');
        migrateButton.disabled = true;
        
        // Set all steps to pending
        updateStatus('stop-partdb', 'pending');
        updateStatus('update-config', 'pending');
        updateStatus('start-partdb', 'pending');
        updateStatus('migrate-db', 'pending');
        
        const response = await fetch('ajax/migrate_partdb.php');
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to integrate Part-DB');
        }

        // Update status steps one by one with a delay
        await new Promise(resolve => setTimeout(resolve, 500));
        updateStatus('stop-partdb', 'success');
        
        await new Promise(resolve => setTimeout(resolve, 500));
        updateStatus('update-config', 'success');
        
        await new Promise(resolve => setTimeout(resolve, 500));
        updateStatus('start-partdb', 'success');
        
        await new Promise(resolve => setTimeout(resolve, 500));
        updateStatus('migrate-db', 'success');
        
        migrateButton.textContent = 'Continue';
        migrateButton.disabled = false;
        migrateButton.onclick = () => navigateToStep('final');
        showSuccess('Part-DB integration completed successfully!');
        
        if (data.details) {
            console.log('Migration details:', data.details);
        }
        
    } catch (error) {
        console.error('Error:', error);
        showError(error.message);
        migrateButton.textContent = 'Retry Migration';
        migrateButton.disabled = false;
        
        // Mark all pending steps as failed
        document.querySelectorAll('.status-step[data-status="pending"]').forEach(step => {
            step.setAttribute('data-status', 'error');
        });
    }
}
</script> 