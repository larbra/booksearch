document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.spoiler-toggle').forEach(button => {
		button.addEventListener('click', function () {
			const content = this.nextElementSibling;
			content.classList.toggle('show');
			this.textContent = content.classList.contains('show')
				? this.textContent.replace('▼', '▲')
				: this.textContent.replace('▲', '▼');
		});
	});

	const searchForm = document.getElementById('searchForm');
	const resultsContainer = document.getElementById('results');
	let searchTimeout;

	function performSearch() {
		const formData = new FormData(searchForm);
		const params = new URLSearchParams();

		for (const [key, value] of formData.entries()) {
			if (value) {
				params.append(key, value);
			}
		}

		fetch(`include/component/search.php?${params.toString()}`)
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					displayResults(data.books);
				} else {
					resultsContainer.innerHTML = `<p class="error">Ошибка: ${data.error}</p>`;
				}
			})
			.catch(error => {
				resultsContainer.innerHTML = `<p class="error">Произошла ошибка: ${error.message}</p>`;
			});
	}

	function displayResults(books) {
		if (books.length === 0) {
			resultsContainer.innerHTML = '<p>Книги не найдены. Попробуйте изменить параметры поиска.</p>';
			return;
		}

		let html = '';
		books.forEach(book => {
			html += `
					<div class="book">
						<div class="book-image">
							${book.image_path
					? `<img src="${book.image_path}" alt="${book.title}">`
					: '<div class="no-image">Нет изображения</div>'}
						</div>
						<div class="book-info">
							<div class="book-title">${book.title}</div>
							<div class="book-authors">Авторы: ${book.authors || 'Не указаны'}</div>
							<div class="book-genres">Жанры: ${book.genres || 'Не указаны'}</div>
							<div class="book-description">${book.description || 'Описание отсутствует.'}</div>
						</div>
					</div>
			`;
		});

		resultsContainer.innerHTML = html;
	}

	searchForm.addEventListener('submit', function (e) {
		e.preventDefault();
		performSearch();
	});


	document.getElementById('title').addEventListener('blur', performSearch);

	document.querySelectorAll('input[name="genres[]"], input[name="authors[]"]').forEach(checkbox => {
		checkbox.addEventListener('change', performSearch);
	});


	performSearch();
});