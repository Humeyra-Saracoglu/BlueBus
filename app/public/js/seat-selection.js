class SeatSelection {
    constructor() {
        this.selectedSeat = null;
        this.pricePerSeat = 0;
        this.init();
    }

    init() {
        const priceElement = document.querySelector('[data-seat-price]');
        if (priceElement) {
            this.pricePerSeat = parseFloat(priceElement.dataset.seatPrice);
        }
        const seats = document.querySelectorAll('.seat-available');
        seats.forEach(seat => {
            seat.addEventListener('click', () => this.toggleSeat(seat));
        });

        const buyButton = document.getElementById('buy-ticket');
        if (buyButton) {
            buyButton.addEventListener('click', () => this.purchaseTicket());
        }
    }

    toggleSeat(seatElement) {
        if (seatElement.classList.contains('seat-occupied')) {
            BiletGo.showToast('Bu koltuk dolu!', 'error');
            return;
        }

        const seatNumber = seatElement.dataset.seat;

        document.querySelectorAll('.seat-selected').forEach(seat => {
            seat.classList.remove('seat-selected');
        });

        seatElement.classList.add('seat-selected');
        this.selectedSeat = seatNumber;
        this.updateSummary();
    }

    updateSummary() {
        const selectedSeatDisplay = document.getElementById('selected-seat');
        if (selectedSeatDisplay) {
            selectedSeatDisplay.textContent = this.selectedSeat || '-';
        }

        const seatInput = document.getElementById('seat_number_input');
        if (seatInput) {
            seatInput.value = this.selectedSeat || '';
        }

        this.calculateTotal();
    }

    calculateTotal() {
        const priceElement = document.getElementById('ticket-price');
        const totalElement = document.getElementById('total-price');

        if (priceElement && totalElement) {
            // Kupon indirimi varsa uygula
            const discountElement = document.querySelector('[data-discount-amount]');
            const discount = discountElement 
                ? parseFloat(discountElement.dataset.discountAmount) 
                : 0;

            const subtotal = this.pricePerSeat;
            const total = subtotal - discount;

            totalElement.textContent = BiletGo.formatPrice(total);
        }
    }

    purchaseTicket() {
        if (!this.selectedSeat) {
            BiletGo.showToast('Lütfen bir koltuk seçin!', 'warning');
            return;
        }

        const confirmMsg = `Koltuk ${this.selectedSeat} için bilet satın almak istediğinizden emin misiniz?`;
        if (confirm(confirmMsg)) {
            // Form submit
            const form = document.getElementById('purchase-form');
            if (form) {
                BiletGo.showLoading();
                form.submit();
            }
        }
    }

    reset() {
        document.querySelectorAll('.seat-selected').forEach(seat => {
            seat.classList.remove('seat-selected');
        });
        this.selectedSeat = null;
        this.updateSummary();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.seats-grid')) {
        window.seatSelection = new SeatSelection();
    }
});

function toggleSeat(element) {
    if (window.seatSelection) {
        window.seatSelection.toggleSeat(element);
    }
}

function purchaseTicket() {
    if (window.seatSelection) {
        window.seatSelection.purchaseTicket();
    }
}