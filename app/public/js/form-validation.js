class FormValidator {
    constructor(formId) {
        this.form = document.getElementById(formId);
        if (this.form) {
            this.init();
        }
    }

    init() {
        this.form.addEventListener('submit', (e) => this.validate(e));

        const inputs = this.form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            input.addEventListener('blur', () => this.validateField(input));
            input.addEventListener('input', () => this.clearError(input));
        });

        const password = this.form.querySelector('input[name="password"]');
        const passwordConfirm = this.form.querySelector('input[name="password_confirm"]');
        
        if (password && passwordConfirm) {
            passwordConfirm.addEventListener('input', () => {
                if (passwordConfirm.value !== password.value) {
                    this.showError(passwordConfirm, 'Şifreler eşleşmiyor');
                } else {
                    this.clearError(passwordConfirm);
                }
            });
        }
    }

    validate(e) {
        e.preventDefault();
        let isValid = true;
        const errors = [];

        const requiredFields = this.form.querySelectorAll('[required]');
        requiredFields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
                errors.push(`${this.getFieldLabel(field)} zorunludur`);
            }
        });

        const emailFields = this.form.querySelectorAll('input[type="email"]');
        emailFields.forEach(field => {
            if (field.value && !this.isValidEmail(field.value)) {
                this.showError(field, 'Geçerli bir e-posta adresi girin');
                isValid = false;
                errors.push('E-posta formatı hatalı');
            }
        });

        const phoneFields = this.form.querySelectorAll('input[type="tel"]');
        phoneFields.forEach(field => {
            if (field.value && !this.isValidPhone(field.value)) {
                this.showError(field, 'Geçerli bir telefon numarası girin (05XX XXX XX XX)');
                isValid = false;
                errors.push('Telefon formatı hatalı');
            }
        });

        const passwordFields = this.form.querySelectorAll('input[type="password"][minlength]');
        passwordFields.forEach(field => {
            const minLength = parseInt(field.getAttribute('minlength'));
            if (field.value && field.value.length < minLength) {
                this.showError(field, `En az ${minLength} karakter olmalıdır`);
                isValid = false;
                errors.push(`Şifre en az ${minLength} karakter olmalı`);
            }
        });

        const password = this.form.querySelector('input[name="password"]');
        const passwordConfirm = this.form.querySelector('input[name="password_confirm"]');
        if (password && passwordConfirm) {
            if (password.value !== passwordConfirm.value) {
                this.showError(passwordConfirm, 'Şifreler eşleşmiyor');
                isValid = false;
                errors.push('Şifreler eşleşmiyor');
            }
        }

        const termsCheckbox = this.form.querySelector('input[name="terms"]');
        if (termsCheckbox && !termsCheckbox.checked) {
            isValid = false;
            errors.push('Kullanım şartlarını kabul etmelisiniz');
        }

        if (isValid) {
            BiletGo.showLoading();
            this.form.submit();
        } else {
            BiletGo.showToast('Lütfen formu eksiksiz ve doğru doldurun!', 'error');
            const firstError = this.form.querySelector('.error-message');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        }
    }

    validateField(field) {
        this.clearError(field);

        if (field.hasAttribute('required') && !field.value.trim()) {
            this.showError(field, 'Bu alan zorunludur');
            return false;
        }

        if (field.type === 'email' && field.value && !this.isValidEmail(field.value)) {
            this.showError(field, 'Geçerli bir e-posta adresi girin');
            return false;
        }

        if (field.type === 'tel' && field.value && !this.isValidPhone(field.value)) {
            this.showError(field, 'Geçerli bir telefon numarası girin');
            return false;
        }

        return true;
    }

    showError(field, message) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        const existingError = formGroup.querySelector('.error-message');
        if (existingError) {
            existingError.remove();
        }

        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        errorDiv.style.color = 'var(--danger-color)';
        errorDiv.style.fontSize = '0.85rem';
        errorDiv.style.marginTop = '0.25rem';
        
        formGroup.appendChild(errorDiv);
        field.style.borderColor = 'var(--danger-color)';
    }

    clearError(field) {
        const formGroup = field.closest('.form-group');
        if (!formGroup) return;

        const errorMessage = formGroup.querySelector('.error-message');
        if (errorMessage) {
            errorMessage.remove();
        }
        field.style.borderColor = '';
    }

    isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    isValidPhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length === 11 && cleaned.startsWith('05');
    }

    getFieldLabel(field) {
        const label = field.closest('.form-group')?.querySelector('label');
        return label ? label.textContent.replace('*', '').trim() : field.name;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('register-form')) {
        new FormValidator('register-form');
    }

    if (document.getElementById('login-form')) {
        new FormValidator('login-form');
    }

    const forms = document.querySelectorAll('form[data-validate]');
    forms.forEach(form => {
        if (form.id) {
            new FormValidator(form.id);
        }
    });
});