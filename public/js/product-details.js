document.addEventListener('turbo:load', () => {

    // --- 1. Quantity Logic Safety Check ---
    const plusBtn = document.getElementById('qtyPlus');

    // Only run this if the plus button actually exists on the page
    if (plusBtn) {
        const qtyDisplay = document.getElementById('qtyDisplay');
        const hiddenInput = document.getElementById('hiddenQuantityInput');
        const minusBtn = document.getElementById('qtyMinus');

        plusBtn.addEventListener('click', () => {
            let val = parseInt(qtyDisplay.value);
            qtyDisplay.value = val + 1;
            hiddenInput.value = val + 1;
        });

        minusBtn.addEventListener('click', () => {
            let val = parseInt(qtyDisplay.value);
            if (val > 1) {
                qtyDisplay.value = val - 1;
                hiddenInput.value = val - 1;
            }
        });
    }

    // --- 2. Color Logic Safety Check ---
    const colorPicker = document.getElementById('colorPicker');
    if (colorPicker) {
        const colorDisplay = document.getElementById('colorDisplay');
        colorPicker.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                colorDisplay.innerText = btn.getAttribute('data-color');
                colorPicker.querySelectorAll('button').forEach(b => b.classList.remove('ring-2', 'ring-brand'));
                btn.classList.add('ring-2', 'ring-brand');
            });
        });
    }

    // --- 3. Size Logic Safety Check ---
    const sizePicker = document.getElementById('sizePicker');
    if (sizePicker) {
        const sizeDisplay = document.getElementById('sizeDisplay');
        sizePicker.querySelectorAll('button').forEach(btn => {
            btn.addEventListener('click', () => {
                sizeDisplay.innerText = btn.getAttribute('data-size');
                sizePicker.querySelectorAll('button').forEach(b => {
                    b.classList.remove('bg-brand', 'text-white', 'border-brand');
                    b.classList.add('bg-white', 'text-light-brown', 'border-gray-200');
                });
                btn.classList.add('bg-brand', 'text-white', 'border-brand');
                btn.classList.remove('bg-white', 'text-light-brown', 'border-gray-200');
            });
        });
    }
});
