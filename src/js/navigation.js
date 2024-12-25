const stepOrder = ['welcome', 'nodered', 'partdb', 'mariadb', 'database', 'finish'];

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