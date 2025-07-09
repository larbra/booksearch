<body>
	<div class="container">
		<h1>Поиск книг</h1>

		<form id="searchForm">
			<div class="form-group">
				<label for="title">Название книги:</label>
				<input type="text" id="title" name="title" placeholder="Введите название...">
			</div>

			<div class="spoiler">
				<button type="button" class="spoiler-toggle">Жанры ▼</button>
				<div class="spoiler-content">
					<?php foreach ($genres as $genre): ?>
						<label class="checkbox-label">
							<input type="checkbox" name="genres[]" value="<?= $genre['id'] ?>">
							<?= htmlspecialchars($genre['name']) ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="spoiler">
				<button type="button" class="spoiler-toggle">Авторы ▼</button>
				<div class="spoiler-content">
					<?php foreach ($authors as $author): ?>
						<label class="checkbox-label">
							<input type="checkbox" name="authors[]" value="<?= $author['id'] ?>">
							<?= htmlspecialchars($author['full_name']) ?>
						</label>
					<?php endforeach; ?>
				</div>
			</div>

			<button type="submit" class="search-button">Поиск</button>
			<a href="?page=parser" class="search-button">Добавить данные</a>
			<a href="?page=clear_db" class="search-button">Очистить базу данных</a>
		</form>

		<div id="results" class="books-container">
			
		</div>
	</div>

</body>