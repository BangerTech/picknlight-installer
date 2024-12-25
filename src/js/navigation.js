function getCurrentStep() {
    // Hole den aktuellen Schritt aus der URL
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('step') || 'welcome';
}

function navigateToStep(step) {
    // Navigiere zum angegebenen Schritt
    window.location.href = `index.php?step=${step}`;
}

function nextStep() {
    const currentStep = getCurrentStep();
    const currentIndex = stepOrder.indexOf(currentStep);
    if (currentIndex < stepOrder.length - 1) {
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