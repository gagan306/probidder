// Ladder Animation
function createLadder() {
    const steps = document.querySelector('.steps');
    const stepCount = 12;
    const stepHeight = 100 / stepCount;

    for (let i = 0; i < stepCount; i++) {
        const step = document.createElement('div');
        step.className = 'step';
        step.style.bottom = `${i * stepHeight}%`;
        steps.appendChild(step);
    }
}

function createStars() {
    const container = document.querySelector('.animation-container');
    const starCount = 20;

    for (let i = 0; i < starCount; i++) {
        const star = document.createElement('div');
        star.className = 'star';
        star.style.left = `${Math.random() * 100}%`;
        star.style.top = `${Math.random() * 100}%`;
        star.style.animationDelay = `${Math.random() * 2}s`;
        container.appendChild(star);
    }
}

function animateDot() {
    const dot = document.querySelector('.red-dot');
    const steps = document.querySelectorAll('.step');
    let currentStep = 0;

    function moveToNextStep() {
        if (currentStep < steps.length) {
            const step = steps[currentStep];
            const rect = step.getBoundingClientRect();
            const containerRect = document.querySelector('.animation-container').getBoundingClientRect();
            
            dot.style.bottom = `${100 - (currentStep * (100 / steps.length))}%`;
            currentStep++;
            
            setTimeout(moveToNextStep, 1000);
        } else {
            // Reset animation
            currentStep = 0;
            dot.style.bottom = '0%';
            setTimeout(moveToNextStep, 1000);
        }
    }

    moveToNextStep();
}

// Initialize animations when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    createLadder();
    createStars();
    animateDot();
});
