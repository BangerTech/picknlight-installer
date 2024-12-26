function getCurrentStep() {
    const urlParams = new URLSearchParams(window.location.search);
    const step = urlParams.get('step') || 'welcome';
    console.log('Current step:', step);
    return step;
}

function navigateToStep(step) {
    console.log('Navigating to step:', step);
    if (stepOrder.includes(step)) {
        console.log('Step order:', stepOrder);
        console.log('Valid step, navigating to:', step);
        window.location.href = `index.php?step=${step}`;
    } else {
        console.error('Invalid step:', step);
    }
}

function nextStep() {
    const currentStep = getCurrentStep();
    const currentIndex = stepOrder.indexOf(currentStep);
    console.log('Next step - Current index:', currentIndex);
    console.log('Step order:', stepOrder);
    
    if (currentIndex >= 0 && currentIndex < stepOrder.length - 1) {
        const nextStep = stepOrder[currentIndex + 1];
        console.log('Moving to next step:', nextStep);
        navigateToStep(nextStep);
    } else {
        console.error('Cannot navigate: current step not found or already at last step');
    }
}

function previousStep() {
    const currentStep = getCurrentStep();
    const currentIndex = stepOrder.indexOf(currentStep);
    console.log('Previous step - Current index:', currentIndex);
    
    if (currentIndex > 0) {
        const prevStep = stepOrder[currentIndex - 1];
        console.log('Moving to previous step:', prevStep);
        navigateToStep(prevStep);
    } else {
        console.error('Cannot navigate: current step not found or already at first step');
    }
} 

const stepOrder = [
    'welcome',
    'nodered',
    'partdb',
    'mariadb',
    'database',
    'final'
]; 