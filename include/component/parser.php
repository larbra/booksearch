<?php
// require '../../vendor/autoload.php';
// require_once '../../include/database/db.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DomCrawler\Crawler;

$pdo->exec("DELETE FROM book_authors");
$pdo->exec("DELETE FROM book_genres");
$pdo->exec("DELETE FROM books");
$pdo->exec("DELETE FROM authors");
$pdo->exec("DELETE FROM genres");

$client = new Client([
	'base_uri' => 'https://litlife.club',
	'timeout' => 15.0,
]);


function downloadImage($client, $url, $path)
{
	// не используется была идеи скидывать изображения в папку temp но подумал что это не нужно
	// if (!file_exists('temp')) {
	// 	mkdir('temp', 0777, true);
	// }

	try {
		$response = $client->get($url, ['sink' => $path]);
		return $response->getStatusCode() === 200 ? $path : null;
	} catch (RequestException $e) {
		echo "Ошибка загрузки изображения: " . $e->getMessage() . "\n";
		return null;
	}
}

try {
	echo "Получаю список книг...\n";
	$response = $client->get('/books');
	$html = (string) $response->getBody();

	// использовал для отладки
	file_put_contents('debug_page.html', $html);

	$crawler = new Crawler($html);

	$bookLinks = $crawler->filter('a')->each(function (Crawler $node) {
		$href = $node->attr('href');
		if (!$href)
			return null;
		$href = ltrim($href, '@');
		if (preg_match('#^https://litlife\\.club/books/\\d+(-[\\w-]+)?/?$#', $href)) {
			return $href;
		}
		return null;
	});
	$bookLinks = array_filter($bookLinks);
	$bookLinks = array_unique($bookLinks);

	// использовал для полученния ссылок на книги чтобы не парсить все книги (можно удалить но я оставил)
	file_put_contents('book_links.txt', implode("\n", $bookLinks));
	echo "Найдено ссылок на книги: " . count($bookLinks) . "\n";

	// тут настройка количества книг для парсинга 
	$bookLinks = array_slice($bookLinks, 0, 10);

	$processedBooks = 0;

	foreach ($bookLinks as $bookUrl) {
		echo "\nОбрабатываю книгу: $bookUrl\n";

		try {
			$response = $client->get($bookUrl);
			$bookHtml = (string) $response->getBody();
			$bookCrawler = new Crawler($bookHtml);

			preg_match('/Книга \"(.+?)\"/', $bookCrawler->filter('title')->text(), $m);
			$title = isset($m[1]) ? $m[1] : 'Без названия';

			$description = 'Описание отсутствует';
			$bookCrawler->filter('description')->each(function (Crawler $node) use (&$description) {

				if (preg_match('/([А-ЯA-Z][^.!?\n]{80,}[.!?])/u', $node->text(), $m)) {
					$description = trim($m[1]);
				}
			});


			$imagePath = null;
			$imageNode = $bookCrawler->filter('img.lazyload')->first();
			if ($imageNode->count() > 0) {
				$imageUrl = $imageNode->attr('data-src') ?: $imageNode->attr('src');
				if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
					$imageUrl = 'https://litlife.club' . $imageUrl;
				}
				if ($imageUrl) {
				
					if (!file_exists('assets/image')) {
						mkdir('assets/image', 0777, true);
					}
			
					$ext = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
		
					$imageFileName = uniqid('img_', true) . ($ext ? ('.' . $ext) : '');
					$imagePath = 'assets/image/' . $imageFileName;
					$downloaded = downloadImage($client, $imageUrl, $imagePath);
					if (!$downloaded) {
						$imagePath = null;
					}
				}
			}

	
			$author = [];
			$bookCrawler->filter('h3:contains("Писатель:")')->each(function (Crawler $node) use (&$author) {
				$next = $node->nextAll()->first();
				if ($next->count()) {
					$author[] = trim($next->text());
				}
			});
			if (empty($author)) {
		
				$author = $bookCrawler->filter('a[href*="/authors/"]')->each(function (Crawler $node) {
					return trim($node->text());
				});
				if (empty($author)) {
					$author = ['Неизвестный автор'];
					echo "Не удалось найти авторов, используется значение по умолчанию\n";
				}
			}

	
			$genres = [];
			$bookCrawler->filter('h3:contains("Жанры:")')->each(function (Crawler $node) use (&$genres) {
				$genresText = $node->text();
				$genres = array_map('trim', explode(',', str_replace('Жанры:', '', $genresText)));
			});
			if (empty($genres)) {
	
				$genres = $bookCrawler->filter('a[href*="/genres/"]')->each(function (Crawler $node) {
					return trim($node->text());
				});
				if (empty($genres)) {
					$genres = ['Без жанра'];
					echo "Не удалось найти жанры, используется значение по умолчанию\n";
				}
			}

	
			$pdo->beginTransaction();

		
			$authorIds = [];
			foreach ($author as $authorName) {
				$stmt = $pdo->prepare("INSERT IGNORE INTO authors (full_name) VALUES (?)");
				$stmt->execute([$authorName]);
				$authorId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM authors WHERE full_name = " . $pdo->quote($authorName))->fetchColumn();
				$authorIds[] = $authorId;
			}

	
			$genreIds = [];
			foreach ($genres as $genreName) {
				$stmt = $pdo->prepare("INSERT IGNORE INTO genres (name) VALUES (?)");
				$stmt->execute([$genreName]);
				$genreId = $pdo->lastInsertId() ?: $pdo->query("SELECT id FROM genres WHERE name = " . $pdo->quote($genreName))->fetchColumn();
				$genreIds[] = $genreId;
			}

			$stmt = $pdo->prepare("INSERT INTO books (title, description, image_path) VALUES (?, ?, ?)");
			$stmt->execute([$title, $description, $imagePath]);
			$bookId = $pdo->lastInsertId();

		
			foreach ($authorIds as $authorId) {
				$stmt = $pdo->prepare("SELECT COUNT(*) FROM book_authors WHERE book_id = ? AND author_id = ?");
				$stmt->execute([$bookId, $authorId]);
				if ($stmt->fetchColumn() == 0) {
					$insert = $pdo->prepare("INSERT INTO book_authors (book_id, author_id) VALUES (?, ?)");
					$insert->execute([$bookId, $authorId]);
				}
			}

	
			foreach ($genreIds as $genreId) {
				$stmt = $pdo->prepare("INSERT INTO book_genres (book_id, genre_id) VALUES (?, ?)");
				$stmt->execute([$bookId, $genreId]);
			}

			$pdo->commit();
			$processedBooks++;
			echo "Успешно добавлена книга: '$title'\n";

		} catch (RequestException $e) {
			echo "Ошибка при загрузке страницы книги: " . $e->getMessage() . "\n";
			continue;
		} catch (Exception $e) {
			if ($pdo->inTransaction()) {
				$pdo->rollBack();
			}
			echo "Ошибка при обработке книги: " . $e->getMessage() . "\n";
			continue;
		}

	
		sleep(rand(1, 3));
	}

	echo "\nПарсинг завершен. Успешно обработано книг: $processedBooks\n";
	echo "<script>window.location.href = '?';</script>";
	$allLinks = $crawler->filter('a')->each(function (Crawler $node) {
		return $node->attr('href');
	});
	// тоже использовал чтобы получить ссылки 
	file_put_contents('all_links.txt', implode(PHP_EOL, $allLinks));

} catch (Exception $e) {
	echo "Критическая ошибка: " . $e->getMessage() . "\n";
	exit(1);
}