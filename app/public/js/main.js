document.addEventListener('DOMContentLoaded', function() {
    
    // Alert otomatik kapanma (5 saniye sonra)
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Tarih input'ları için minimum tarih ayarla (bugün)
    const dateInputs = document.querySelectorAll('input[type="date"]');
    const today = new Date().toISOString().split('T')[0];
    dateInputs.forEach(input => {
        if (!input.value) {
            input.value = today;
        }
        input.setAttribute('min', today);
    });

    // Smooth scroll (sayfa içi link'ler için)
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Confirm delete/cancel işlemleri
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm') || 'Bu işlemi gerçekleştirmek istediğinizden emin misiniz?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });

    // Form validasyonu için klavye kontrolleri
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ef4444';
                } else {
                    field.style.borderColor = '#e5e7eb';
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showToast('Lütfen tüm zorunlu alanları doldurun!', 'error');
            }
        });
    });

    // Input focus efektleri
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
        });
    });

    // Telefon numarası formatı (05XXXXXXXXX)
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substring(0, 11);
            }
            this.value = value;
        });
    });

    // Fiyat formatı (para birimi)
    const priceElements = document.querySelectorAll('.price, [data-price]');
    priceElements.forEach(element => {
        const price = element.textContent.trim();
        if (!isNaN(price) && price !== '') {
            element.textContent = formatPrice(parseFloat(price));
        }
    });

    // Loading durumunu göster/gizle
    const loadingForms = document.querySelectorAll('form[data-loading]');
    loadingForms.forEach(form => {
        form.addEventListener('submit', function() {
            showLoading();
        });
    });
});

// Toast bildirimi göster
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type}`;
    toast.textContent = message;
    toast.style.position = 'fixed';
    toast.style.top = '20px';
    toast.style.right = '20px';
    toast.style.zIndex = '9999';
    toast.style.minWidth = '250px';
    toast.style.animation = 'slideIn 0.3s ease-out';
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Modal açma/kapama
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
}

// Modal dışına tıklanınca kapat
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// ESC tuşu ile modal kapat
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="display: flex"]');
        openModals.forEach(modal => {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        });
    }
});

// Fiyat formatlama (1234.56 -> 1.234,56 ₺)
function formatPrice(price) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY',
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    }).format(price);
}

// Tarih formatlama (2025-01-15 -> 15 Ocak 2025)
function formatDate(dateString) {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return date.toLocaleDateString('tr-TR', options);
}

// Loading göster
function showLoading() {
    const loading = document.createElement('div');
    loading.id = 'loading-overlay';
    loading.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: rgba(0,0,0,0.5); display: flex; align-items: center; 
                    justify-content: center; z-index: 9999;">
            <div style="background: white; padding: 2rem; border-radius: 12px; 
                        text-align: center;">
                <div style="width: 50px; height: 50px; border: 4px solid #e5e7eb; 
                            border-top-color: #2563eb; border-radius: 50%; 
                            animation: spin 1s linear infinite; margin: 0 auto 1rem;"></div>
                <p style="color: #1f2937; font-weight: 600;">Yükleniyor...</p>
            </div>
        </div>
    `;
    document.body.appendChild(loading);
}

// Loading gizle
function hideLoading() {
    const loading = document.getElementById('loading-overlay');
    if (loading) {
        loading.remove();
    }
}

// Debounce fonksiyonu (arama için)
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

document.querySelectorAll('select').forEach(select => {
    select.style.pointerEvents = 'auto';
    select.style.cursor = 'pointer';
});

// Sayfa başına scroll
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Element'in görünür olup olmadığını kontrol et
function isElementInViewport(el) {
    const rect = el.getBoundingClientRect();
    return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
    );
}

// Animasyon için CSS ekle
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }
    
    .focused input,
    .focused select,
    .focused textarea {
        border-color: #2563eb !important;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
`;
document.head.appendChild(style);

// Sayfa yüklenme tamamlandığında loading'i kaldır
window.addEventListener('load', function() {
    hideLoading();
});

// Console log engelleme (production için)
if (window.location.hostname !== 'localhost' && window.location.hostname !== '127.0.0.1') {
    console.log = function() {};
    console.warn = function() {};
    console.error = function() {};
}

// Global değişkenler
window.BiletGo = {
    showToast: showToast,
    openModal: openModal,
    closeModal: closeModal,
    formatPrice: formatPrice,
    formatDate: formatDate,
    showLoading: showLoading,
    hideLoading: hideLoading,
    scrollToTop: scrollToTop
};

console.log('🚍 BiletGo sistemi yüklendi!');