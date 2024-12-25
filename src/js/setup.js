function nextStep() {
    fetch('ajax/next_step.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function previousStep() {
    fetch('ajax/previous_step.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.reload();
        }
    });
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    
    // Entferne vorherige Fehlermeldungen
    const oldErrors = document.querySelectorAll('.error-message');
    oldErrors.forEach(err => err.remove());
    
    // Füge neue Fehlermeldung hinzu
    document.querySelector('.button-group').before(errorDiv);
}

function saveNodeRedConfig() {
    const formData = new FormData(document.getElementById('noderedForm'));
    
    fetch('ajax/save_nodered_config.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.text();
    })
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                nextStep();
            } else {
                showError(data.error || 'Failed to save configuration');
            }
        } catch (e) {
            console.error('Server response:', text);
            showError('Invalid server response');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showError(error.message);
    });
}

function updateStepStatus(stepId, status, message = '') {
    console.log(`Updating step ${stepId} to status ${status}`);
    
    const stepElement = document.querySelector(`#step-${stepId}`);
    if (!stepElement) {
        console.warn(`Step element not found: step-${stepId}`);
        return;
    }
    
    const statusElement = stepElement.querySelector('.status');
    if (!statusElement) {
        console.warn(`Status element not found in step ${stepId}`);
        return;
    }
    
    const statusTextElement = statusElement.querySelector('.status-text');
    if (statusTextElement) {
        statusTextElement.textContent = message || status;
    }
    
    // Entferne alte Status-Klassen
    statusElement.classList.remove('pending', 'complete', 'error');
    statusElement.classList.add(status);
} 