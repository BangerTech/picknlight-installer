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

function updateStepStatus(step, status, error = '') {
    console.log(`Updating step ${step} to status ${status}`);
    const stepElement = document.getElementById(`step-${step}`);
    if (!stepElement) {
        console.error(`Step element not found: step-${step}`);
        return;
    }

    const statusElement = stepElement.querySelector('.status');
    const statusText = statusElement.querySelector('.status-text');
    const spinner = statusElement.querySelector('.spinner');

    // Entferne alle Status-Klassen
    stepElement.classList.remove('pending', 'complete', 'error');
    
    // Füge neue Status-Klasse hinzu
    stepElement.classList.add(status);

    switch (status) {
        case 'complete':
            statusText.textContent = 'Complete';
            spinner.style.display = 'none';
            break;
        case 'error':
            statusText.textContent = error || 'Error';
            spinner.style.display = 'none';
            break;
        case 'pending':
            statusText.textContent = 'Pending...';
            spinner.style.display = 'block';
            break;
    }
} 