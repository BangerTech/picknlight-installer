// Definiere die Reihenfolge der Schritte als globale Variable
window.stepOrder = ['welcome', 'nodered', 'partdb', 'mariadb', 'database', 'migrate', 'final'];

// Jetzt erst die Logs
console.log('Loading navigation.js');
console.log('Initial stepOrder:', window.stepOrder);

document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded');
    // Aktuelle Schritt-Information
    const currentStep = getCurrentStep();
    console.log('Current step:', currentStep);
    const currentIndex = window.stepOrder.indexOf(currentStep);
    console.log('Current index:', currentIndex);
    
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
    console.log('Navigating to step:', step);
    window.location.href = `index.php?step=${step}`;
}

function nextStep() {
    const currentStep = getCurrentStep();
    const currentIndex = window.stepOrder.indexOf(currentStep);
    
    if (currentIndex >= 0 && currentIndex < window.stepOrder.length - 1) {
        navigateToStep(window.stepOrder[currentIndex + 1]);
    }
}

function previousStep() {
    const currentStep = getCurrentStep();
    const currentIndex = window.stepOrder.indexOf(currentStep);
    
    if (currentIndex > 0) {
        navigateToStep(window.stepOrder[currentIndex - 1]);
    }
}

// Globale Funktionen verfügbar machen
window.navigateToStep = navigateToStep;
window.nextStep = nextStep;
window.previousStep = previousStep;

console.log('Navigation.js loaded');
console.log('Step order available:', window.stepOrder); 