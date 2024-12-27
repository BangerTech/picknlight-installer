// Theme Switching Logic
function setTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem('theme', theme);
    
    // Update active state der Buttons
    document.getElementById('lightTheme').classList.toggle('active', theme === 'light');
    document.getElementById('darkTheme').classList.toggle('active', theme === 'dark');
}

// Check for saved theme preference or system preference
const savedTheme = localStorage.getItem('theme') || 
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
setTheme(savedTheme);

// Theme switch event listeners
document.getElementById('lightTheme').addEventListener('click', () => setTheme('light'));
document.getElementById('darkTheme').addEventListener('click', () => setTheme('dark'));

// Optional: Reagiere auf System-Theme-Änderungen
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
    if (!localStorage.getItem('theme')) {
        setTheme(e.matches ? 'dark' : 'light');
    }
}); 