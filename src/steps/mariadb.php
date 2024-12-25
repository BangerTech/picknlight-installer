<div class="step" id="step-mariadb">
    <h3>1. Setting up MariaDB</h3>
    <p>Creating and configuring MariaDB container...</p>
    <div class="status">
        <span class="status-text">Pending...</span>
        <div class="spinner"></div>
    </div>
</div>

<script>
async function setupMariaDB() {
    try {
        console.log('Starting MariaDB setup...');
        updateStepStatus('mariadb', 'pending');
        
        const response = await fetch('/ajax/database_setup.php?step=create_mariadb');
        console.log('MariaDB setup response:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('MariaDB setup data:', data);
        
        if (data.success) {
            console.log('MariaDB setup successful');
            updateStepStatus('mariadb', 'complete');
            return true;
        } else {
            console.error('MariaDB setup failed:', data.error);
            updateStepStatus('mariadb', 'error', data.error || 'Failed to setup MariaDB');
            return false;
        }
    } catch (error) {
        console.error('Error setting up MariaDB:', error);
        updateStepStatus('mariadb', 'error', 'Network error while setting up MariaDB');
        return false;
    }
}

window.setupMariaDB = setupMariaDB;
</script> 