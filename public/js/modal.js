const modal = document.getElementById("successModal");
if (modal) {
    const closeBtn = document.getElementById("closeModal");
    modal.style.display = "block";

    // Auto close after 3 seconds
    setTimeout(() => {
        modal.style.display = "none";
    }, 3000);

    // Close modal on X click
    closeBtn.onclick = () => {
        modal.style.display = "none";
    };

    // Close modal if clicked outside
    window.onclick = (event) => {
        if (event.target === modal) modal.style.display = "none";
    };
}
