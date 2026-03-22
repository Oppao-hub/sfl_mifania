// // Variables to store the current state
// let activeForm = null;
// let activeSelect = null;
// let previousValue = null;

// /**
//  * Opens the modal and prepares the form for submission.
//  * * @param {HTMLFormElement} form - The form surrounding the select
//  * @param {HTMLSelectElement} selectElement - The dropdown that changed
//  * @param {string} originalVal - The value before the change (to revert if cancelled)
//  * @param {string} title - (Optional) Custom title for the modal
//  * @param {string} message - (Optional) Custom message. If not provided, generates a default one.
//  */
// function triggerConfirmation(form, selectElement, originalVal, title = 'Update Status', message = null) {
//     activeForm = form;
//     activeSelect = selectElement;
//     previousValue = originalVal;

//     const newValue = selectElement.value;

//     // Set Title
//     document.getElementById('confirmModalTitle').innerText = title;

//     // Set Message (Use provided message OR generate one)
//     const msgElement = document.getElementById('confirmModalMessage');
//     if (message) {
//         msgElement.innerHTML = message;
//     } else {
//         msgElement.innerHTML = `Are you sure you want to change the status from <b>${originalVal}</b> to <b>${newValue}</b>?`;
//     }

//     // Show Modal
//     document.getElementById('genericConfirmModal').classList.remove('hidden');
// }

// function executeConfirm() {
//     if (activeForm) {
//         activeForm.submit();
//     }
// }

// function closeConfirmModal() {
//     document.getElementById('genericConfirmModal').classList.add('hidden');

//     // Revert the select if the user cancelled
//     if (activeSelect && previousValue) {
//         activeSelect.value = previousValue;
//     }

//     // Clean up
//     activeForm = null;
//     activeSelect = null;
//     previousValue = null;
// }
