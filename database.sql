CREATE DATABASE IF NOT EXISTS lottie_gallery;
USE lottie_gallery;

CREATE TABLE animations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255),
    url VARCHAR(255),
    tags TEXT,
    category VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
