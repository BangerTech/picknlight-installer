async function updateStatus(step, status, message = null) {
    const statusStep = document.getElementById(`step-${step}`);
    if (!statusStep) return;
    
    const icon = statusStep.querySelector('.status-icon');
    const textElement = statusStep.querySelector('.status-text');
    
    if (message) {
        textElement.textContent = message;
    }
    
    statusStep.setAttribute('data-status', status);
    
    switch (status) {
        case 'pending':
            icon.textContent = '⏳';
            break;
        case 'success':
            icon.textContent = '✅';
            break;
        case 'error':
            icon.textContent = '❌';
            break;
        default:
            icon.textContent = '⭕';
    }
}

function showSuccess(message) {
    document.querySelectorAll('.success-message').forEach(el => el.remove());
    
    const successDiv = document.createElement('div');
    successDiv.className = 'success-message';
    successDiv.textContent = message;
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.classList.add('hiding');
        setTimeout(() => {
            successDiv.remove();
        }, 300);
    }, 5000);
}

function showError(message) {
    document.querySelectorAll('.error-message').forEach(el => el.remove());
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.classList.add('hiding');
        setTimeout(() => {
            errorDiv.remove();
        }, 300);
    }, 5000);
}

function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    
    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = '🔒';  // Geschlossenes Schloss wenn Passwort sichtbar
    } else {
        input.type = 'password';
        button.innerHTML = '👁️';  // Auge wenn Passwort verborgen
    }
} 