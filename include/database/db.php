<?php
$host = 'localhost';
$dbname = 'booksearch-app';
$username = 'root';
$password = '';

try {
	$pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
	die("Ошибка подключения к базе данных: " . $e->getMessage());
}

function searchBooks($pdo, $title = null, $genreIds = [], $authorIds = [])
{
	$sql = "SELECT b.id, b.title, b.description, b.image_path, 
            GROUP_CONCAT(DISTINCT a.full_name SEPARATOR ', ') as authors,
            GROUP_CONCAT(DISTINCT g.name SEPARATOR ', ') as genres
            FROM books b
            LEFT JOIN book_authors ba ON b.id = ba.book_id
            LEFT JOIN authors a ON ba.author_id = a.id
            LEFT JOIN book_genres bg ON b.id = bg.book_id
            LEFT JOIN genres g ON bg.genre_id = g.id
            WHERE 1=1";

	$params = [];

	if (!empty($title)) {
		$sql .= " AND MATCH(b.title, b.description) AGAINST(? IN BOOLEAN MODE)";
		$params[] = $title;
	}

	if (!empty($genreIds)) {
		$placeholders = implode(',', array_fill(0, count($genreIds), '?'));
		$sql .= " AND b.id IN (SELECT book_id FROM book_genres WHERE genre_id IN ($placeholders))";
		$params = array_merge($params, $genreIds);
	}

	if (!empty($authorIds)) {
		$placeholders = implode(',', array_fill(0, count($authorIds), '?'));
		$sql .= " AND b.id IN (SELECT book_id FROM book_authors WHERE author_id IN ($placeholders))";
		$params = array_merge($params, $authorIds);
	}

	$sql .= " GROUP BY b.id";

	$stmt = $pdo->prepare($sql);
	$stmt->execute($params);

	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllGenres($pdo)
{
	$stmt = $pdo->query("SELECT id, name FROM genres ORDER BY name");
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAllAuthors($pdo)
{
	$stmt = $pdo->query("SELECT id, full_name FROM authors ORDER BY full_name");
	return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>