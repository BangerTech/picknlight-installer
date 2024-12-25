<div class="step" id="step-triggers">
    <h3>4. Importing Triggers</h3>
    <p>Setting up database triggers for LED mapping...</p>
    <div class="status">
        <span class="status-text">Pending...</span>
        <div class="spinner"></div>
    </div>
</div>

<script>
async function setupTriggers() {
    try {
        console.log('Starting trigger setup...');
        updateStepStatus('triggers', 'pending');
        
        const response = await fetch('/ajax/database_setup.php?step=import_triggers');
        console.log('Trigger setup response:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Trigger setup data:', data);
        
        if (data.success) {
            console.log('Trigger setup successful');
            updateStepStatus('triggers', 'complete');
            
            // Warte kurz und gehe dann zum nächsten Schritt
            await new Promise(resolve => setTimeout(resolve, 1000));
            nextStep();
            
            return true;
        } else {
            console.error('Trigger setup failed:', data.error);
            updateStepStatus('triggers', 'error', data.error || 'Failed to import triggers');
            return false;
        }
    } catch (error) {
        console.error('Error setting up triggers:', error);
        updateStepStatus('triggers', 'error', 'Network error while importing triggers');
        return false;
    }
}

// Exportiere die Funktion global
window.setupTriggers = setupTriggers;
</script> 