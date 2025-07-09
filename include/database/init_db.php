<?php
require_once 'db.php';

try {
	$pdo->exec("
      CREATE TABLE IF NOT EXISTS genres (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL UNIQUE
      );
      
      CREATE TABLE IF NOT EXISTS authors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            full_name VARCHAR(255) NOT NULL UNIQUE
      );
      
      CREATE TABLE IF NOT EXISTS books (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            image_path VARCHAR(255),
            FULLTEXT (title, description)
      );
      
      CREATE TABLE IF NOT EXISTS book_genres (
            book_id INT NOT NULL,
            genre_id INT NOT NULL,
            PRIMARY KEY (book_id, genre_id),
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (genre_id) REFERENCES genres(id) ON DELETE CASCADE
      );
      
      CREATE TABLE IF NOT EXISTS book_authors (
            book_id INT NOT NULL,
            author_id INT NOT NULL,
            PRIMARY KEY (book_id, author_id),
            FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
            FOREIGN KEY (author_id) REFERENCES authors(id) ON DELETE CASCADE
      );
   ");

	echo "База данных успешно создана!";
} catch (PDOException $e) {
	die("Ошибка создания таблиц: " . $e->getMessage());
}
?>