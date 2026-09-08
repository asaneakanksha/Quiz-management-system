CREATE DATABASE IF NOT EXISTS quizmaster;

USE quizmaster;

CREATE TABLE IF NOT EXISTS quizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    subject VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS question (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('mcq','truefalse')
        NOT NULL DEFAULT 'mcq',
    option_a VARCHAR(255) DEFAULT NULL,
    option_b VARCHAR(255) DEFAULT NULL,
    option_c VARCHAR(255) DEFAULT NULL,
    option_d VARCHAR(255) DEFAULT NULL,
    correct_answer VARCHAR(20) NOT NULL,
    marks INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (quiz_id)
        REFERENCES quizes(id)
        ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quiz_result (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    score INT NOT NULL DEFAULT 0,
    total_marks INT NOT NULL DEFAULT 0,
    percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (quiz_id)
        REFERENCES quizes(id)
        ON DELETE CASCADE
);