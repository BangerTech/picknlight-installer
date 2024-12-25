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