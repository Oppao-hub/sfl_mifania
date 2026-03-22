// document.addEventListener("DOMContentLoaded", () => {
//     const sidebar = document.getElementById("sidebar");
//     const toggleButton = document.getElementById("sidebar-toggle-btn");

//     if (!sidebar || !toggleButton) {
//         console.error("Sidebar or toggle button not found.");
//         return;
//     }

//     toggleButton.addEventListener("click", () => {
//         // Toggle the 'collapsed' class on the sidebar for instant UI feedback
//         sidebar.classList.toggle("collapsed");

//         const isCollapsed = sidebar.classList.contains("collapsed");

//         // Persist the state to the server
//         const toggleUrl = toggleButton.dataset.toggleUrl;
//         if (toggleUrl) {
//             fetch(toggleUrl, {
//                 method: "POST",
//                 headers: {
//                     "Content-Type": "application/json",
//                     "X-Requested-With": "XMLHttpRequest",
//                 },
//                 body: JSON.stringify({ collapsed: isCollapsed }),
//             }).catch((error) =>
//                 console.error("Error updating sidebar state:", error)
//             );
//         }
//     });
// });
