import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['shippingFee', 'orderTotal'];

    static values = {
        subtotal: Number,
    };

    connect() {
        this.refreshTotals();
        this.element.querySelectorAll('input[name="checkout[deliveryOption]"]').forEach((input) => {
            input.addEventListener('change', () => this.refreshTotals());
        });
    }

    refreshTotals() {
        const selected = this.element.querySelector('input[name="checkout[deliveryOption]"]:checked');
        const fee = selected ? parseFloat(selected.dataset.feeAmount || '0') : 0;
        const total = this.subtotalValue + fee;

        if (this.hasShippingFeeTarget) {
            this.shippingFeeTarget.textContent = `₱${fee.toFixed(2)}`;
        }
        if (this.hasOrderTotalTarget) {
            this.orderTotalTarget.textContent = `₱${total.toFixed(2)}`;
        }
    }
}
