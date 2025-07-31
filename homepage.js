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

document.addEventListener("scroll", () => {
    const main3News = document.querySelector(".main3-news");
    const rect = main3News.getBoundingClientRect();
    if (rect.top < window.innerHeight && rect.bottom >= 0) {
        main3News.classList.add("visible"); // Add the visible class when in view
    } else {
        main3News.classList.remove("visible"); // Remove the visible class when out of view
    }
});

document.addEventListener("scroll", () => {
    const main4 = document.querySelector(".main4");
    const rect = main4.getBoundingClientRect();
    if (rect.top < window.innerHeight && rect.bottom >= 0) {
        main4.classList.add("visible"); // Add the visible class when in view
    } else {
        main4.classList.remove("visible"); // Remove the visible class when out of view
    }
});
