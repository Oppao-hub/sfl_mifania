// --- Dark Mode Initialization and Toggle Logic ---

/**
 * Checks localStorage or system preference to set initial theme,
 * preventing FOUC (Flash of Unstyled Content) by setting the class immediately on <html>.
 */
const initializeTheme = () => {
    const prefersDark = window.matchMedia(
        "(prefers-color-scheme: dark)"
    ).matches;
    const savedTheme = localStorage.getItem("theme");

    let initialTheme = "light";

    if (savedTheme) {
        initialTheme = savedTheme;
    } else if (prefersDark) {
        initialTheme = "dark";
    }

    if (initialTheme === "dark") {
        document.documentElement.classList.add("dark");
    } else {
        document.documentElement.classList.remove("dark");
    }
};

// Run on script load
initializeTheme();

/**
 * Global function to toggle theme and persist choice to localStorage.
 * Called by the Alpine.js component on the button.
 */
window.toggleTheme = function () {
    const isDark = document.documentElement.classList.contains("dark");
    if (isDark) {
        document.documentElement.classList.remove("dark");
        localStorage.setItem("theme", "light");
    } else {
        document.documentElement.classList.add("dark");
        localStorage.setItem("theme", "dark");
    }
};
// --- End Dark Mode Logic ---
