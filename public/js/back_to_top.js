document.addEventListener("DOMContentLoaded", () => {
    const backBtn = document.getElementById("back-to-top");

    console.log("Back to Top Button Element:", backBtn);

    //show hide the btn
    const toggleVisibility = () => {
        //show the button if the scroll position is past 300 pixels
        if (window.scrollY > 300) {
            backBtn.classList.remove("opacity-0", "pointer-events-none");
            backBtn.classList.add("opacity-100");
        } else {
            backBtn.classList.remove("opacity-100");
            backBtn.classList.add("opacity-0", "pointer-events-none");
        }
    };

    //scroll to top
    const scrollToTop = () => {
        window.scrollTo({
            top: 0,
            behavior: "smooth",
        });
    };

    //add event listener
    window.addEventListener("scroll", toggleVisibility);
    backBtn.addEventListener("click", scrollToTop);

    toggleVisibility();
});
