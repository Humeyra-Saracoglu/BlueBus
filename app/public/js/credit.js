document.addEventListener('DOMContentLoaded', function() {
    const creditOptions = document.querySelectorAll('.credit-option');
    const creditInput = document.getElementById('credit_amount');

    if (creditOptions.length > 0 && creditInput) {
        creditOptions.forEach(btn => {
            btn.addEventListener('click', function() {
                creditOptions.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const amount = this.dataset.amount;
                creditInput.value = amount;
                this.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    this.style.transform = '';
                }, 100);
            });
        });

        creditInput.addEventListener('input', function() {
            creditOptions.forEach(b => b.classList.remove('active'));
        });
    }
});

const style = document.createElement('style');
style.textContent = `
    .credit-option.active {
        background: var(--primary-color) !important;
        color: white !important;
        border-color: var(--primary-color) !important;
    }
`;
document.head.appendChild(style);