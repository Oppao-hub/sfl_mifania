import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Targets specific to the individual row
    static targets = ['quantity', 'subtotal'];

    // The URL we passed in from Twig
    static values = { updateUrl: String }

    async update(event) {
        event.preventDefault();

        // Grab the data-type attribute ("increment" or "decrement") from the clicked button
        const actionType = event.currentTarget.dataset.type;

        try {
            // Ping the Symfony backend behind the scenes
            const response = await fetch(this.updateUrlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ action: actionType })
            });

            if (response.ok) {
                const data = await response.json();

                // 1. Update this specific row's quantity and subtotal targets instantly
                this.quantityTarget.value = data.newQuantity;
                this.subtotalTarget.innerHTML = `PHP ${data.newSubtotal}`;

                // 2. Update the Global Order Summary by finding their IDs
                document.getElementById('global-cart-subtotal').innerHTML = `PHP ${data.newTotal}`;
                document.getElementById('global-cart-total').innerHTML = `PHP ${data.newTotal}`;
            }
        } catch (error) {
            console.error("Cart update failed", error);
        }
    }
}
