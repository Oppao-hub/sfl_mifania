import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'priceBadge', 'priceSlider']

    updateBadge() {
        this.priceBadgeTarget.textContent = `PHP ${this.priceSliderTarget.value}`;
    }

    submitForm() {
        this.formTarget.requestSubmit();
    }
}
