class CouponManager {
    constructor() {
        this.discountAmount = 0;
        this.discountPercent = 0;
        this.init();
    }

    init() {
        const applyButton = document.querySelector('.apply-coupon-btn');
        if (applyButton) {
            applyButton.addEventListener('click', () => this.applyCoupon());
        }

        const couponInput = document.getElementById('coupon');
        if (couponInput) {
            couponInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.applyCoupon();
                }
            });
        }
    }

    async applyCoupon() {
        const couponInput = document.getElementById('coupon');
        const couponCode = couponInput.value.trim().toUpperCase();

        if (!couponCode) {
            BiletGo.showToast('Lütfen bir kupon kodu girin!', 'warning');
            return;
        }

        const applyButton = document.querySelector('.apply-coupon-btn');
        const originalText = applyButton.textContent;
        applyButton.textContent = 'Kontrol ediliyor...';
        applyButton.disabled = true;

        try {
            const response = await fetch('/api/check-coupon.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    coupon_code: couponCode,
                    route_id: this.getRouteId()
                })
            });

            const data = await response.json();

            if (data.success) {
                this.discountPercent = data.discount_percent;
                this.calculateDiscount();
                BiletGo.showToast(`Kupon uygulandı! %${this.discountPercent} indirim`, 'success');
                couponInput.disabled = true;
                applyButton.textContent = '✓ Uygulandı';
                applyButton.style.background = '#10b981';
            } else {
                BiletGo.showToast(data.message || 'Geçersiz kupon kodu!', 'error');
                applyButton.textContent = originalText;
                applyButton.disabled = false;
            }
        } catch (error) {
            console.error('Kupon kontrolü hatası:', error);
            BiletGo.showToast('Bir hata oluştu, lütfen tekrar deneyin!', 'error');
            applyButton.textContent = originalText;
            applyButton.disabled = false;
        }
    }

    calculateDiscount() {
        const subtotalElement = document.querySelector('[data-price]');
        if (!subtotalElement) return;

        const subtotal = parseFloat(subtotalElement.dataset.price);
        this.discountAmount = (subtotal * this.discountPercent) / 100;

        const discountElement = document.querySelector('.price-row.discount span:last-child');
        if (discountElement) {
            discountElement.textContent = `- ${BiletGo.formatPrice(this.discountAmount)}`;
            discountElement.parentElement.style.color = '#10b981';
        }

        const total = subtotal - this.discountAmount;
        const totalElement = document.querySelector('.price-row.total strong:last-child');
        if (totalElement) {
            totalElement.textContent = BiletGo.formatPrice(total);
        }

        this.saveCouponData(this.discountAmount);
    }

    saveCouponData(amount) {
        let input = document.querySelector('[name="coupon_discount"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'coupon_discount';
            document.querySelector('form').appendChild(input);
        }
        input.value = amount;

        let codeInput = document.querySelector('[name="coupon_code"]');
        if (!codeInput) {
            codeInput = document.createElement('input');
            codeInput.type = 'hidden';
            codeInput.name = 'coupon_code';
            document.querySelector('form').appendChild(codeInput);
        }
        codeInput.value = document.getElementById('coupon').value.toUpperCase();
    }

    getRouteId() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('id') || document.querySelector('[data-route-id]')?.dataset.routeId;
    }

    reset() {
        this.discountAmount = 0;
        this.discountPercent = 0;
        const couponInput = document.getElementById('coupon');
        if (couponInput) {
            couponInput.value = '';
            couponInput.disabled = false;
        }
        const applyButton = document.querySelector('.apply-coupon-btn');
        if (applyButton) {
            applyButton.textContent = 'Uygula';
            applyButton.disabled = false;
            applyButton.style.background = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('coupon')) {
        window.couponManager = new CouponManager();
    }
});