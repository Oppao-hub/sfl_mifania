import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'quantityDisplay',
        'hiddenQuantityInput',
        'colorDisplay',
        'hiddenColorInput',
        'colorButton',
        'sizeDisplay',
        'hiddenSizeInput',
        'sizeButton'
    ]

    connect() {
        console.log("Product Controller Connected!");
    }

    // --- Quantity Logic ---
    increment(event) {
        event.preventDefault();
        let val = parseInt(this.quantityDisplayTarget.value);
        this.updateQuantity(val + 1);
    }

    decrement(event) {
        event.preventDefault();
        let val = parseInt(this.quantityDisplayTarget.value);
        if (val > 1) {
            this.updateQuantity(val - 1);
        }
    }

    updateQuantity(newVal) {
        this.quantityDisplayTarget.value = newVal;
        this.hiddenQuantityInputTarget.value = newVal;
    }

    // --- Color Logic ---
    selectColor(event) {
        const button = event.currentTarget;
        const colorName = button.dataset.colorName;

        // 1. Update text display
        this.colorDisplayTarget.textContent = colorName;

        // 2. Update hidden form input
        this.hiddenColorInputTarget.value = colorName;

        // 3. Handle visual highlighting (rings)
        this.colorButtonTargets.forEach(btn => {
            btn.classList.remove('ring-gray-400');
            btn.classList.add('ring-transparent');
        });
        button.classList.remove('ring-transparent');
        button.classList.add('ring-gray-400');
    }

    // --- Size Logic ---
    selectSize(event) {
        const button = event.currentTarget;
        const sizeVal = button.dataset.sizeVal;

        // 1. Update text display
        this.sizeDisplayTarget.textContent = sizeVal;

        // 2. Update hidden form input
        this.hiddenSizeInputTarget.value = sizeVal;

        // 3. Handle visual highlighting (borders/backgrounds)
        this.sizeButtonTargets.forEach(btn => {
            btn.classList.remove('border-gray-900', 'bg-gray-900', 'text-white');
            btn.classList.add('border-gray-200', 'bg-white', 'text-gray-500');
        });

        button.classList.remove('border-gray-200', 'bg-white', 'text-gray-500');
        button.classList.add('border-gray-900', 'bg-gray-900', 'text-white');
    }
}
