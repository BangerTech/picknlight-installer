<div class="step" id="step-table">
    <h3>3. Creating LED Mapping Table</h3>
    <p>Setting up LED mapping table...</p>
    <div class="status">
        <span class="status-text">Pending...</span>
        <div class="spinner"></div>
    </div>
</div>

<script>
async function setupTable() {
    try {
        console.log('Starting table setup...');
        updateStepStatus('table', 'pending');
        
        const response = await fetch('/ajax/database_setup.php?step=create_table');
        console.log('Table setup response:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Table setup data:', data);
        
        if (data.success) {
            console.log('Table setup successful');
            updateStepStatus('table', 'complete');
            return true;
        } else {
            console.error('Table setup failed:', data.error);
            updateStepStatus('table', 'error', data.error || 'Failed to create table');
            return false;
        }
    } catch (error) {
        console.error('Error creating table:', error);
        updateStepStatus('table', 'error', 'Network error while creating table');
        return false;
    }
}

window.setupTable = setupTable;
</script> 