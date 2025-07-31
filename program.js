document.addEventListener("scroll", () => {
    const mainElement = document.querySelector(".main");
    const rect = mainElement.getBoundingClientRect();
    if (rect.top < window.innerHeight && rect.bottom >= 0) {
        mainElement.classList.add("visible");
    }
});

document.addEventListener("scroll", () => {
    const header = document.querySelector(".header1");
    if (window.scrollY > 0) {
        header.classList.add("scrolled");
    } else {
        header.classList.remove("scrolled");
    }
});

document.addEventListener("DOMContentLoaded", () => {
    const navLinks = document.querySelectorAll(".nav-links a");

    // Function to set the active class
    const setActiveLink = () => {
        const currentPath = window.location.pathname.split("/").pop();
        navLinks.forEach(link => {
            if (link.getAttribute("href") === currentPath) {
                link.classList.add("active");
            } else {
                link.classList.remove("active");
            }
        });
    };

    // Run the function on page load
    setActiveLink();
});
