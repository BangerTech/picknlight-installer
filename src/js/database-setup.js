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

async function setupPartDB() {
    try {
        console.log('Starting Part-DB setup...');
        updateStepStatus('partdb', 'pending');
        
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

async function setupNodeRed() {
    try {
        console.log('Starting Node-RED setup...');
        updateStepStatus('nodered', 'pending');
        
        const response = await fetch('/ajax/setup_nodered.php');
        console.log('Node-RED setup response:', response);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Node-RED setup data:', data);
        
        if (data.success) {
            console.log('Node-RED setup successful');
            updateStepStatus('nodered', 'complete');
            return true;
        } else {
            console.error('Node-RED setup failed:', data.error);
            updateStepStatus('nodered', 'error', data.error || 'Failed to setup Node-RED');
            return false;
        }
    } catch (error) {
        console.error('Error setting up Node-RED:', error);
        updateStepStatus('nodered', 'error', 'Network error while setting up Node-RED');
        return false;
    }
}

window.setupMariaDB = setupMariaDB;
window.setupDatabase = setupDatabase;
window.setupTable = setupTable;
window.setupTriggers = setupTriggers;
window.setupPartDB = setupPartDB;
window.setupNodeRed = setupNodeRed; 