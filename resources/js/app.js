import './bootstrap';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

function escapeHtml(value) {
	return String(value)
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&#039;');
}

function toParagraphs(text) {
	return String(text)
		.split(/\n{2,}/)
		.map((segment) => segment.trim())
		.filter(Boolean)
		.map((segment) => `<p class="mb-2">${escapeHtml(segment)}</p>`)
		.join('');
}

function renderTextTools(data) {
	const cleanedText = data.cleaned_text ?? '';
	const summary = data.summary ?? { title: 'Rangkuman', plain_summary: '', bullets: [] };
	const summaryBullets = Array.isArray(summary.bullets) ? summary.bullets : [];
	const sourceName = data.source_name ?? '';
	const copyText = [summary.plain_summary ?? '', ...summaryBullets.map((bullet) => {
		if (bullet && typeof bullet === 'object') {
			return `${bullet.point ?? ''}: ${bullet.explanation ?? ''}`.trim();
		}

		return String(bullet ?? '');
	})].filter(Boolean).join('\n');

	return `
		<div class="result-scroll">
			<div class="result-head">
				<div>
					<div class="small text-uppercase text-muted fw-semibold letter-spacing">Output</div>
					<h3 class="result-title">${escapeHtml(summary.title ?? 'Rangkuman')}</h3>
					${sourceName ? `<div class="text-muted small">Sumber: ${escapeHtml(sourceName)}</div>` : ''}
				</div>
				<button type="button" class="result-copy" data-copy-text="${escapeHtml(copyText)}">
					<i class="bi bi-clipboard"></i>
					Copy ringkasan detail
				</button>
			</div>

			<div class="result-box mb-3">
				<div class="fw-semibold mb-2">Ringkasan sangat detail versi gampang</div>
				<p class="mb-0">${escapeHtml(summary.plain_summary ?? 'Ringkasan singkat akan muncul di sini.')}</p>
			</div>

			<div class="result-box mb-3">
				<div class="fw-semibold mb-2">Poin penting dan penjelasan</div>
				<ul class="result-bullets">
					${summaryBullets.map((bullet) => {
						if (bullet && typeof bullet === 'object') {
							return `
								<li>
									<div>
										<div class="fw-semibold">${escapeHtml(bullet.point ?? '')}</div>
										<div class="text-muted small">${escapeHtml(bullet.explanation ?? '')}</div>
									</div>
								</li>
							`;
						}

						return `<li>${escapeHtml(bullet)}</li>`;
					}).join('') || '<li>Belum ada poin.</li>'}
				</ul>
			</div>

			<details class="result-box">
				<summary class="fw-semibold">Lihat teks asli yang sudah dibersihkan</summary>
				<div class="mt-3">${toParagraphs(cleanedText)}</div>
			</details>
		</div>
	`;
}

function renderFlashcards(data) {
	const cards = data.cards ?? [];
	const questions = data.questions ?? [];

	if (data.mode === 'quiz') {
		return `
			<div class="result-scroll">
				<div class="result-head">
					<div>
						<div class="small text-uppercase text-muted fw-semibold letter-spacing">Interactive quiz</div>
						<h3 class="result-title">Kuis pilihan ganda</h3>
					</div>
				</div>
				<div class="quiz-list">
					${questions.map((question, index) => {
						const options = (question.options ?? []).map((option, optionIndex) => `
							<button type="button" class="quiz-option" data-correct="${optionIndex === Number(question.answer_index) ? 'true' : 'false'}" data-explanation="${escapeHtml(question.explanation ?? '')}">
								<span class="fw-semibold me-2">${String.fromCharCode(65 + optionIndex)}.</span>${escapeHtml(option)}
							</button>
						`).join('');

						return `
							<div class="quiz-item" data-quiz-item>
								<div class="fw-semibold mb-2">${index + 1}. ${escapeHtml(question.question ?? '')}</div>
								<div class="quiz-options">${options}</div>
								<div class="quiz-feedback d-none" data-quiz-feedback></div>
							</div>
						`;
					}).join('')}
				</div>
			</div>
		`;
	}

	return `
		<div class="result-scroll">
			<div class="result-head">
				<div>
					<div class="small text-uppercase text-muted fw-semibold letter-spacing">Study cards</div>
					<h3 class="result-title">Flashcards interaktif</h3>
				</div>
			</div>
			<div class="flashcard-grid">
				${cards.map((card) => `
					<div class="flip-card" data-flip-card>
						<div class="flip-card-inner">
							<div class="flip-face front">
								<div>
									<div class="small text-uppercase text-muted fw-semibold letter-spacing mb-2">Front</div>
									<div class="fw-semibold">${escapeHtml(card.front ?? '')}</div>
								</div>
								<div class="small text-muted">Klik kartu untuk melihat jawaban</div>
							</div>
							<div class="flip-face back">
								<div>
									<div class="small text-uppercase text-muted fw-semibold letter-spacing mb-2">Back</div>
									<div>${escapeHtml(card.back ?? '')}</div>
								</div>
								<div class="small text-muted">Klik lagi untuk kembali</div>
							</div>
						</div>
					</div>
				`).join('')}
			</div>
		</div>
	`;
}

function renderCitation(data) {
	return `
		<div class="result-scroll">
			<div class="result-head">
				<div>
					<div class="small text-uppercase text-muted fw-semibold letter-spacing">Citation</div>
					<h3 class="result-title">${escapeHtml(data.format ?? 'Sitasi')}</h3>
				</div>
				<button type="button" class="result-copy" data-copy-text="${escapeHtml(data.citation ?? '')}">
					<i class="bi bi-clipboard"></i>
					Copy
				</button>
			</div>
			<div class="result-box citation-output">${escapeHtml(data.citation ?? '')}</div>
		</div>
	`;
}

function renderStudyPlan(data) {
	const days = data.days ?? [];

	return `
		<div class="result-scroll">
			<div class="result-head">
				<div>
					<div class="small text-uppercase text-muted fw-semibold letter-spacing">Study plan</div>
					<h3 class="result-title">${escapeHtml(data.summary ?? 'Jadwal belajar')}</h3>
				</div>
			</div>
			<div class="quiz-list">
				${days.map((day) => `
					<div class="quiz-item">
						<div class="fw-semibold mb-1">${escapeHtml(day.day ?? '')}</div>
						<div class="text-muted small mb-2">${escapeHtml(day.focus ?? '')}</div>
						<div class="mb-2 small text-muted">Estimasi ${escapeHtml(day.estimated_hours ?? '')} jam</div>
						<ul class="result-bullets mb-0">
							${(day.tasks ?? []).map((task) => `<li>${escapeHtml(task)}</li>`).join('')}
						</ul>
					</div>
				`).join('')}
			</div>
		</div>
	`;
}

function setLoading(form, isLoading) {
	const buttons = form.querySelectorAll('[data-submit-label]');

	buttons.forEach((button) => {
		const label = button.querySelector('.btn-label');
		const spinner = button.querySelector('.btn-spinner');
		const originalText = button.dataset.submitLabel;

		if (label) {
			label.textContent = isLoading ? 'Memproses...' : originalText;
		}

		if (spinner) {
			spinner.classList.toggle('d-none', !isLoading);
		}

		button.disabled = isLoading;
	});
}

function renderResult(target, html) {
	target.classList.remove('empty-state');
	target.innerHTML = html;
}

async function submitAiForm(form, submitter) {
	const target = document.querySelector(form.dataset.renderTarget);

	if (!target) {
		return;
	}

	const formData = new FormData(form);

	if (submitter?.name) {
		formData.set(submitter.name, submitter.value);
	}

	setLoading(form, true);

	try {
		const response = await fetch(form.action, {
			method: 'POST',
			headers: {
				'X-CSRF-TOKEN': csrfToken,
				Accept: 'application/json',
			},
			body: formData,
		});

		const payload = await response.json();

		if (!response.ok) {
			const message = payload?.message ?? 'Permintaan gagal diproses.';
			throw new Error(message);
		}

		if (form.dataset.aiForm === 'text-tools') {
			renderResult(target, renderTextTools(payload.data ?? {}));
		} else if (form.dataset.aiForm === 'flashcards') {
			renderResult(target, renderFlashcards(payload.data ?? {}));
		} else if (form.dataset.aiForm === 'citation') {
			renderResult(target, renderCitation(payload.data ?? {}));
		} else if (form.dataset.aiForm === 'study-plan') {
			renderResult(target, renderStudyPlan(payload.data ?? {}));
		}
	} catch (error) {
		renderResult(target, `
			<div class="empty-state-inner">
				<i class="bi bi-exclamation-triangle"></i>
				<h3 class="h5 mb-2">${escapeHtml(error.message ?? 'Terjadi kesalahan')}</h3>
				<p class="text-muted mb-0">Periksa input Anda atau API key yang dipakai, lalu coba lagi.</p>
			</div>
		`);
	} finally {
		setLoading(form, false);
	}
}

document.addEventListener('submit', (event) => {
	const form = event.target;

	if (!form.matches('[data-ai-form]')) {
		return;
	}

	event.preventDefault();
	submitAiForm(form, event.submitter ?? null);
});

document.addEventListener('click', async (event) => {
	const copyButton = event.target.closest('[data-copy-text]');

	if (copyButton) {
		const text = copyButton.getAttribute('data-copy-text') ?? '';

		try {
			await navigator.clipboard.writeText(text);
			const original = copyButton.innerHTML;
			copyButton.innerHTML = '<i class="bi bi-check2"></i> Copied';
			setTimeout(() => {
				copyButton.innerHTML = original;
			}, 1200);
		} catch {
			copyButton.innerHTML = '<i class="bi bi-x"></i> Failed';
		}

		return;
	}

	const flipCard = event.target.closest('[data-flip-card]');

	if (flipCard) {
		flipCard.classList.toggle('is-flipped');
		return;
	}

	const quizOption = event.target.closest('.quiz-option');

	if (quizOption) {
		const item = quizOption.closest('[data-quiz-item]');

		if (!item) {
			return;
		}

		const feedback = item.querySelector('[data-quiz-feedback]');
		const isCorrect = quizOption.dataset.correct === 'true';
		const explanation = quizOption.dataset.explanation ?? '';

		item.querySelectorAll('.quiz-option').forEach((option) => {
			option.disabled = true;

			if (option === quizOption) {
				option.classList.add(isCorrect ? 'is-correct' : 'is-wrong');
			} else if (option.dataset.correct === 'true') {
				option.classList.add('is-correct');
			}
		});

		if (feedback) {
			feedback.classList.remove('d-none');
			feedback.textContent = isCorrect ? `Benar. ${explanation}` : `Belum tepat. ${explanation}`;
		}
	}
});
