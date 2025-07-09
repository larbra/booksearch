<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../database/db.php';

header('Content-Type: application/json');

try {
	$title = $_GET['title'] ?? null;
	$genreIds = $_GET['genres'] ?? [];
	$authorIds = $_GET['authors'] ?? [];

	$genreIds = array_map('intval', (array) $genreIds);
	$authorIds = array_map('intval', (array) $authorIds);

	$books = searchBooks($pdo, $title, $genreIds, $authorIds);
	
	echo json_encode(['success' => true, 'books' => $books]);
	
} catch (Exception $e) {
	echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>