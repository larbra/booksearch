<?php
$pdo->exec("DELETE FROM book_authors");
$pdo->exec("DELETE FROM book_genres");
$pdo->exec("DELETE FROM books");
$pdo->exec("DELETE FROM authors");
$pdo->exec("DELETE FROM genres");
echo "<script>window.location.href = '?';</script>";