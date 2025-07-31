const burgerMenu = document.getElementById('burger-menu');
const sideMenu = document.getElementById('side-menu');
const closeMenu = document.getElementById('close-menu');

// Open the menu when clicking the burger icon
burgerMenu.addEventListener('click', function () {
    sideMenu.classList.add('open');
});

// Close the menu when clicking the close button
closeMenu.addEventListener('click', function () {
    sideMenu.classList.remove('open');
});

// Define image categories with arrays of images
const imageCategories = {
    school: ["TPC-IMAGES/school.png", "TPC-IMAGES/school2.png"],
    room: ["TPC-IMAGES/classroom.jpg", "TPC-IMAGES/classroom2.jpg", "TPC-IMAGES/classroom3.jpg"],
    library: ["TPC-IMAGES/library.jpg"],
    chemistryLab: ["TPC-IMAGES/chemlab.jpg", "TPC-IMAGES/chemlab2.jpg"],
    computerLab: ["TPC-IMAGES/comlab.jpg", "TPC-IMAGES/comlab2.jpg"],
    kitchen: ["TPC-IMAGES/kitchen.jpg", "TPC-IMAGES/kitchen2.jpg"],
    facultyRoom: ["TPC-IMAGES/staff.jpg", "TPC-IMAGES/staff2.jpg"]
};

let currentCategory = "school"; // Default category
let currentIndex = 0; // Current image index

// Function to update the displayed image
function updateImage() {
    const images = imageCategories[currentCategory];
    document.getElementById("displayed-image").src = images[currentIndex];
}

// Function to show the next image
function showNextImage() {
    const images = imageCategories[currentCategory];
    currentIndex = (currentIndex + 1) % images.length; // Loop back to the first image
    updateImage();
}

// Function to show the previous image
function showPreviousImage() {
    const images = imageCategories[currentCategory];
    currentIndex = (currentIndex - 1 + images.length) % images.length; // Loop back to the last image
    updateImage();
}

// Function to change the category and display the first image of that category
function changeImage(category) {
    currentCategory = category; // Update the current category
    currentIndex = 0; // Reset to the first image in the category
    updateImage();
}
