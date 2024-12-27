document.addEventListener('DOMContentLoaded', () => {
    // Aktuelle Schritt-Information
    const currentStep = getCurrentStep();
    const currentIndex = stepOrder.indexOf(currentStep);
    
    // Back Button Funktionalität
    const backButton = document.querySelector('.button.previous');
    if (backButton) {
        if (currentIndex > 0) {
            backButton.style.display = 'inline-flex';
            backButton.addEventListener('click', () => previousStep());
        } else {
            backButton.style.display = 'none';
        }
    }
});

function getCurrentStep() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('step') || 'welcome';
}

function navigateToStep(step) {
    if (stepOrder.includes(step)) {
        window.location.href = `index.php?step=${step}`;
    }
}

function nextStep() {
    const currentStep = getCurrentStep();
    const currentIndex = stepOrder.indexOf(currentStep);
    
    if (currentIndex >= 0 && currentIndex < stepOrder.length - 1) {
        navigateToStep(stepOrder[currentIndex + 1]);
    }
}

function previousStep() {
    const currentStep = getCurrentStep();
    const currentIndex = stepOrder.indexOf(currentStep);
    
    if (currentIndex > 0) {
        navigateToStep(stepOrder[currentIndex - 1]);
    }
}

// Globale Funktionen verfügbar machen
window.navigateToStep = navigateToStep;
window.nextStep = nextStep;
window.previousStep = previousStep; 

console.log('Navigation.js loaded');
console.log('Step order available:', stepOrder); 