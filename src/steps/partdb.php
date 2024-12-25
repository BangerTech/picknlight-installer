<div class="step" id="step-partdb">
    <h3>4. Part-DB Setup</h3>
    <p>Setting up Part-DB container...</p>
    <div class="status">
        <span class="status-text">Pending...</span>
        <div class="spinner"></div>
    </div>
</div>

<script>
async function setupPartDB() {
    try {
        console.log('Starting Part-DB setup...');
        updateStepStatus('partdb', 'pending');
        
        // Erstelle und starte den Part-DB Container
        const response = await fetch('/ajax/setup_partdb.php');
        console.log('Part-DB setup response:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Part-DB setup data:', data);
        
        if (data.success) {
            console.log('Part-DB setup successful');
            updateStepStatus('partdb', 'complete');
            nextStep();
            return true;
        } else {
            console.error('Part-DB setup failed:', data.error);
            updateStepStatus('partdb', 'error', data.error || 'Failed to setup Part-DB');
            return false;
        }
    } catch (error) {
        console.error('Error setting up Part-DB:', error);
        updateStepStatus('partdb', 'error', 'Network error while setting up Part-DB');
        return false;
    }
}

window.setupPartDB = setupPartDB;
</script> 