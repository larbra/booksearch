<?php
require_once 'vendor/autoload.php';
include ('include/database/db.php');
// include ('include/database/init_db.php'); - использовал для создания таблиц в базе данных
$genres = getAllGenres($pdo);
$authors = getAllAuthors($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Book Search App</title>
   <link rel="stylesheet" href="assets/css/style.css">
	<script src="assets/js/script.js" defer></script>
</head>
<body>
   <?php


	/* посчитал что лучше использовать динамические страницы чем каждый раз подключать файлы, 
	я понимаю что это просто тестовое задание и можно было сделать проще, но я решил сделать так,
	чтобы на будущее можно было подключить хедер и футер и не писать их в каждом файле
	*/

	include('views/layout/header.php');
	if(isset($_GET['page'])) {
		$page = $_GET['page'];
		if($page == 'search') {
			include('include/component/search.php');
		}
		if($page == 'parser') {
			include('include/component/parser.php');
		}
		if($page == 'clear_db') {
			include('include/clear_db.php');
		}
	}else{
		include('views/pages/home.php');
	}
	include('views/layout/footer.php');
	?>
</body>
</html>