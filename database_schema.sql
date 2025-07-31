CREATE TABLE tblshsstudent (
    shsstud_ID INT PRIMARY KEY AUTO_INCREMENT, -- Unique student ID
    shstud_firstname VARCHAR(50) NOT NULL,     -- Student's first name
    shstud_lastname VARCHAR(50) NOT NULL,      -- Student's last name
    section_ID INT NOT NULL,                   -- Foreign key to tblshssection
    grade_level VARCHAR(10) NOT NULL           -- Grade level (e.g., "11", "12")
);

CREATE TABLE tblshssection (
    section_ID INT PRIMARY KEY AUTO_INCREMENT, -- Unique section ID
    section_Name VARCHAR(50) NOT NULL          -- Name of the section (e.g., "Capricorn B")
);
