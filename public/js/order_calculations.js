document.addEventListener('DOMContentLoaded', function() {
    const itemContainer = document.getElementById('order-items-fields');
    const addButton = document.getElementById('add-item-btn');
    const totalAmountField = document.getElementById('order_totalAmount'); // Ensure this ID matches your form output

    // --- CALCULATION LOGIC ---

    function updateRow(row) {
        const productSelect = row.querySelector('.item-product-select');
        const quantityInput = row.querySelector('.item-quantity');
        const priceInput = row.querySelector('.item-price');
        const subtotalInput = row.querySelector('.item-subtotal');

        // 1. Get Price from the selected option's data-price attribute
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const price = parseFloat(selectedOption.dataset.price || 0);

        // 2. Get Quantity
        const quantity = parseInt(quantityInput.value || 0);

        if (isNaN(quantity) || quantity < 1) {
            quantity = 1;
            quantityInput.value = 1;
        }

        // 3. Calculate Subtotal
        const subtotal = price * quantity;

        // 4. Update Inputs (toFixed(2) ensures 2 decimal places like 10.00)
        priceInput.value = price.toFixed(2);
        subtotalInput.value = subtotal.toFixed(2);

        // 5. Update the Grand Total
        updateGrandTotal();
    }

    function updateGrandTotal() {
        let total = 0;
        // Loop through all subtotal fields
        document.querySelectorAll('.item-subtotal').forEach(input => {
            total += parseFloat(input.value || 0);
        });

        if (totalAmountField) {
            totalAmountField.value = total.toFixed(2);
        }

        const rewardField = document.getElementById('order_rewardPointsEarned');

        if (rewardField) {
            // EXAMPLE LOGIC: 1 Point for every 10 Pesos spent.
            // Change '/ 10' to whatever math you use.
            // Math.floor removes decimals (e.g., 95.50 becomes 9 points, not 9.5)
            const points = Math.floor(total / 50); //1 point for every 50 pesos

            rewardField.value = points;
        }
    }

    // --- EVENT DELEGATION (Magic Listener) ---
    // Instead of adding listeners to every single input manually,
    // we listen to the big container. This works for existing AND new items automatically.
    itemContainer.addEventListener('change', function(e) {
        // If Product Dropdown changes
        if (e.target.classList.contains('item-product-select')) {
            const row = e.target.closest('.order-item-row');
            updateRow(row);
        }
    });

    itemContainer.addEventListener('input', function(e) {
        // If Quantity Input changes
        if (e.target.classList.contains('item-quantity')) {
            const row = e.target.closest('.order-item-row');
            updateRow(row);
        }
    });

    // --- ADD / REMOVE LOGIC (Your existing code + calculation trigger) ---

    let counter = itemContainer.children.length;

    function addItem() {
        const prototype = itemContainer.dataset.prototype;
        const newFormHtml = prototype.replace(/__name__/g, counter);
        counter++;

        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = newFormHtml;
        const newRow = tempDiv.firstElementChild;

        // Remove Button Logic
        const removeBtn = newRow.querySelector('.remove-item-btn');
        if (removeBtn) {
            removeBtn.addEventListener('click', function() {
                newRow.remove();
                updateGrandTotal(); // Recalculate total when item is removed
            });
        }

        itemContainer.appendChild(newRow);

        // Trigger update on the new row immediately (to set initial price)
        updateRow(newRow);
    }

    addButton.addEventListener('click', addItem);

    // Remove listener for existing items
    itemContainer.querySelectorAll('.remove-item-btn').forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.order-item-row').remove();
            updateGrandTotal();
        });
    });

    // Initial Calculation (in case form is loaded with data)
    itemContainer.querySelectorAll('.order-item-row').forEach(row => updateRow(row));
});
