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

/* ---------- Riwayat (localStorage) ---------- */

const HISTORY_STORAGE_KEY = 'studiiai-history-v1';
const HISTORY_LIMIT = 12;

function canUseLocalStorage() {
	try {
		return typeof localStorage !== 'undefined';
	} catch {
		return false;
	}
}

function loadHistoryEntries() {
	if (!canUseLocalStorage()) {
		return [];
	}

	try {
		const raw = localStorage.getItem(HISTORY_STORAGE_KEY);
		const entries = JSON.parse(raw ?? '[]');

		return Array.isArray(entries) ? entries : [];
	} catch {
		return [];
	}
}

function saveHistoryEntries(entries) {
	if (!canUseLocalStorage()) {
		return;
	}

	try {
		localStorage.setItem(HISTORY_STORAGE_KEY, JSON.stringify(entries.slice(0, HISTORY_LIMIT)));
	} catch {
		// Abaikan kegagalan storage.
	}
}

function historyTimestamp(isoString) {
	try {
		return new Intl.DateTimeFormat('id-ID', {
			dateStyle: 'medium',
			timeStyle: 'short',
		}).format(new Date(isoString));
	} catch {
		return isoString;
	}
}

function truncateText(value, maxLength = 80) {
	const text = String(value ?? '').replace(/\s+/g, ' ').trim();

	if (text.length <= maxLength) {
		return text;
	}

	return `${text.slice(0, maxLength - 1)}…`;
}

function sanitizeHistoryData(tool, data) {
	return tool === 'text-tools'
		? { summary: data.summary ?? null, source_name: data.source_name ?? '' }
		: data;
}

function buildHistoryEntry(tool, data, formData) {
	const createdAt = new Date().toISOString();
	let title = 'Hasil baru';
	let preview = '';
	let target = '';
	let storedData = data;

	if (tool === 'text-tools') {
		storedData = sanitizeHistoryData(tool, data);
		title = data.summary?.title ?? 'Rangkuman PDF';
		preview = data.source_name ? `Sumber: ${data.source_name}` : 'Rangkuman PDF';
		target = '#text-tools-output';
	} else if (tool === 'flashcards') {
		title = data.mode === 'quiz' ? 'Kuis pilihan ganda' : 'Flashcards interaktif';
		preview = truncateText(formData.get('topic') ?? 'Topik belajar');
		target = '#flashcards-output';
	} else if (tool === 'citation') {
		title = `Sitasi ${data.format ?? ''}`.trim();
		preview = data.citation ? truncateText(data.citation, 90) : 'Referensi siap salin';
		target = '#citation-output';
	} else if (tool === 'study-plan') {
		title = data.summary ?? 'Jadwal belajar';
		preview = truncateText(formData.get('items_text') ?? 'Daftar tugas');
		target = '#study-plan-output';
	}

	return {
		id: `${Date.now()}-${Math.random().toString(16).slice(2)}`,
		tool,
		title,
		preview,
		target,
		createdAt,
		data: storedData,
	};
}

function getToolLabel(entry) {
	if (entry.tool === 'text-tools') {
		return 'Rangkuman PDF';
	}

	if (entry.tool === 'flashcards') {
		return entry.data?.mode === 'quiz' ? 'Kuis' : 'Flashcards';
	}

	if (entry.tool === 'citation') {
		return 'Sitasi';
	}

	if (entry.tool === 'study-plan') {
		return 'Study plan';
	}

	return 'Hasil';
}

function renderStoredToolResult(entry) {
	if (entry.tool === 'text-tools') {
		return renderTextTools(entry.data ?? {});
	}

	if (entry.tool === 'flashcards') {
		return renderFlashcards(entry.data ?? {});
	}

	if (entry.tool === 'citation') {
		return renderCitation(entry.data ?? {});
	}

	if (entry.tool === 'study-plan') {
		return renderStudyPlan(entry.data ?? {});
	}

	return '';
}

function renderHistoryPanel() {
	const container = document.querySelector('[data-history-list]');

	if (!container) {
		return;
	}

	const entries = loadHistoryEntries();

	if (!entries.length) {
		container.innerHTML = `
			<div class="empty-state-inner py-4">
				<i class="bi bi-clock-history"></i>
				<h3 class="h5 mb-2">Riwayat belum ada</h3>
				<p class="text-muted mb-0">Setiap hasil yang berhasil dibuat akan muncul di sini dan bisa dibuka ulang.</p>
			</div>
		`;
		return;
	}

	container.innerHTML = `
		<div class="history-list">
			${entries.map((entry) => `
				<div class="history-item">
					<div>
						<div class="history-meta">${escapeHtml(getToolLabel(entry))} • ${escapeHtml(historyTimestamp(entry.createdAt))}</div>
						<div class="fw-semibold mb-1">${escapeHtml(entry.title ?? 'Hasil')}</div>
						<div class="text-muted small">${escapeHtml(entry.preview ?? '')}</div>
					</div>
					<div class="history-actions">
						<button type="button" class="btn btn-sm btn-primary-soft" data-history-open data-history-id="${escapeHtml(entry.id)}">Buka</button>
						<button type="button" class="btn btn-sm btn-outline-secondary" data-history-delete data-history-id="${escapeHtml(entry.id)}">Hapus</button>
					</div>
				</div>
			`).join('')}
		</div>
	`;
}

function openHistoryEntry(entryId) {
	const entry = loadHistoryEntries().find((item) => item.id === entryId);

	if (!entry) {
		return;
	}

	const target = document.querySelector(entry.target ?? '');

	if (!target) {
		return;
	}

	renderResult(target, renderStoredToolResult(entry));
	target.scrollIntoView({ behavior: 'smooth', block: 'start' });

	if (entry.tool === 'text-tools' && entry.data?.summary) {
		updateChatStudyContext(entry.data.summary.title, entry.data.summary.plain_summary);
	}
}

function deleteHistoryEntry(entryId) {
	saveHistoryEntries(loadHistoryEntries().filter((entry) => entry.id !== entryId));
	renderHistoryPanel();
}

function clearHistory() {
	saveHistoryEntries([]);
	renderHistoryPanel();
}

function pushHistory(tool, data, formData) {
	const entries = loadHistoryEntries();
	entries.unshift(buildHistoryEntry(tool, data, formData));
	saveHistoryEntries(entries);
	renderHistoryPanel();
}

/* ---------- AI Tutor Chatbot ---------- */

const CHAT_STORAGE_KEY = 'studiiai-chat-v1';
let currentStudyContext = null;

function formatChatMarkdown(text) {
	if (!text) return '';
	let html = escapeHtml(text);
	html = html.replace(/```([\s\S]*?)```/g, '<pre><code>$1</code></pre>');
	html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
	html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
	html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
	html = html.replace(/(?:^|\n)[-*•]\s+(.+)/g, '<li>$1</li>');
	html = html.replace(/(<li>[\s\S]*?<\/li>)/g, '<ul>$1</ul>');
	html = html.replace(/<\/ul>\s*<ul>/g, '');
	html = html.replace(/\n{2,}/g, '</p><p>');
	html = html.replace(/\n/g, '<br>');
	return `<p>${html}</p>`;
}

function loadChatHistory() {
	if (!canUseLocalStorage()) return [];
	try {
		const raw = localStorage.getItem(CHAT_STORAGE_KEY);
		const parsed = JSON.parse(raw ?? '[]');
		return Array.isArray(parsed) ? parsed : [];
	} catch {
		return [];
	}
}

function saveChatHistory(history) {
	if (!canUseLocalStorage()) return;
	try {
		localStorage.setItem(CHAT_STORAGE_KEY, JSON.stringify(history.slice(-16)));
	} catch {
		// Ignore storage errors
	}
}

function updateChatStudyContext(title, plainSummary) {
	currentStudyContext = `Materi: ${title}\nRingkasan: ${plainSummary}`;
	const badge = document.getElementById('chat-context-badge');
	const label = badge?.querySelector('.chat-context-title');
	if (badge && label) {
		label.textContent = `Materi aktif: ${truncateText(title, 22)}`;
		badge.classList.remove('d-none');
	}
}

function removeChatStudyContext() {
	currentStudyContext = null;
	const badge = document.getElementById('chat-context-badge');
	if (badge) {
		badge.classList.add('d-none');
	}
}

function appendChatBubble(role, content) {
	const container = document.querySelector('[data-chat-messages]');
	if (!container) return;

	const isUser = role === 'user';
	const bubble = document.createElement('div');
	bubble.className = `chat-bubble ${isUser ? 'chat-bubble-user' : 'chat-bubble-bot'}`;

	const inner = document.createElement('div');
	inner.className = 'chat-bubble-content';
	if (isUser) {
		inner.textContent = content;
	} else {
		inner.innerHTML = formatChatMarkdown(content);
	}

	bubble.appendChild(inner);
	container.appendChild(bubble);
	container.scrollTop = container.scrollHeight;
}

function renderSavedChatMessages() {
	const history = loadChatHistory();
	if (!history.length) return;

	history.forEach((msg) => {
		appendChatBubble(msg.role, msg.content);
	});
}

function openChatDrawer() {
	const drawer = document.getElementById('ai-chat-drawer');
	if (!drawer) return;
	drawer.classList.remove('d-none');
	drawer.setAttribute('aria-hidden', 'false');
	const container = document.querySelector('[data-chat-messages]');
	if (container) container.scrollTop = container.scrollHeight;
	const input = document.querySelector('[data-chat-input]');
	if (input) input.focus();
}

function closeChatDrawer() {
	const drawer = document.getElementById('ai-chat-drawer');
	if (!drawer) return;
	drawer.classList.add('d-none');
	drawer.setAttribute('aria-hidden', 'true');
}

function toggleChatDrawer() {
	const drawer = document.getElementById('ai-chat-drawer');
	if (!drawer) return;
	if (drawer.classList.contains('d-none')) {
		openChatDrawer();
	} else {
		closeChatDrawer();
	}
}

async function handleChatSubmit(event) {
	event.preventDefault();
	const form = event.target;
	const input = form.querySelector('[data-chat-input]');
	const sendBtn = form.querySelector('[data-chat-send]');
	const typing = document.querySelector('[data-chat-typing]');
	const personaSelect = document.querySelector('[data-chat-persona]');
	const persona = personaSelect?.value ?? 'tutor';

	const message = input.value.trim();
	if (!message) return;

	input.value = '';
	input.style.height = 'auto';

	appendChatBubble('user', message);

	const history = loadChatHistory();
	const payloadHistory = history.slice(-6).map((h) => ({ role: h.role, content: h.content }));

	if (typing) typing.classList.remove('d-none');
	if (sendBtn) sendBtn.disabled = true;

	try {
		const response = await fetch(form.action, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-CSRF-TOKEN': csrfToken,
				Accept: 'application/json',
			},
			body: JSON.stringify({
				message,
				history: payloadHistory,
				persona,
				context: currentStudyContext,
			}),
		});

		const payload = await response.json();
		if (!response.ok) {
			throw new Error(payload?.message ?? 'Gagal menghubungi tutor AI.');
		}

		const reply = payload?.data?.reply ?? 'Maaf, belum bisa menjawab saat ini.';
		appendChatBubble('model', reply);

		history.push({ role: 'user', content: message });
		history.push({ role: 'model', content: reply });
		saveChatHistory(history);
	} catch (error) {
		appendChatBubble('model', `⚠️ ${error.message || 'Terjadi gangguan saat memproses jawaban.'}`);
	} finally {
		if (typing) typing.classList.add('d-none');
		if (sendBtn) sendBtn.disabled = false;
		input.focus();
	}
}

/* ---------- Renderer hasil ---------- */

function renderTextTools(data) {
	const summary = data.summary ?? { title: 'Rangkuman', plain_summary: '', bullets: [] };
	const summaryBullets = Array.isArray(summary.bullets) ? summary.bullets : [];
	const keyTerms = Array.isArray(summary.key_terms) ? summary.key_terms : [];
	const reviewQuestions = Array.isArray(summary.review_questions) ? summary.review_questions : [];
	const sourceName = data.source_name ?? '';

	const copyParts = [
		summary.title ?? '',
		summary.plain_summary ?? '',
		...summaryBullets.map((bullet) => {
			if (bullet && typeof bullet === 'object') {
				return `- ${bullet.point ?? ''}: ${bullet.explanation ?? ''}`.trim();
			}

			return `- ${String(bullet ?? '')}`;
		}),
	];

	if (keyTerms.length) {
		copyParts.push('', 'Istilah penting:');
		keyTerms.forEach((item) => copyParts.push(`- ${item.term ?? ''}: ${item.definition ?? ''}`));
	}

	if (reviewQuestions.length) {
		copyParts.push('', 'Pertanyaan review:');
		reviewQuestions.forEach((question, index) => copyParts.push(`${index + 1}. ${question}`));
	}

	const copyText = copyParts.filter((part) => part !== null && part !== undefined).join('\n').trim();

	const keyTermsHtml = keyTerms.length ? `
		<div class="result-box mb-3">
			<div class="fw-semibold mb-2">Istilah penting</div>
			<ul class="result-bullets">
				${keyTerms.map((item) => `
					<li>
						<div>
							<div class="fw-semibold">${escapeHtml(item.term ?? '')}</div>
							<div class="text-muted small">${escapeHtml(item.definition ?? '')}</div>
						</div>
					</li>
				`).join('')}
			</ul>
		</div>
	` : '';

	const reviewHtml = reviewQuestions.length ? `
		<div class="result-box mb-3">
			<div class="fw-semibold mb-2">Uji pemahamanmu</div>
			<div class="text-muted small mb-2">Coba jawab dulu dalam hati sebelum membuka materi lagi.</div>
			<ol class="mb-0 ps-3">
				${reviewQuestions.map((question) => `<li class="mb-1">${escapeHtml(question)}</li>`).join('')}
			</ol>
		</div>
	` : '';

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

			${keyTermsHtml}
			${reviewHtml}
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
					<div class="fw-semibold" data-quiz-score data-total="${questions.length}">Skor: 0/${questions.length}</div>
				</div>
				<div class="quiz-list" data-quiz-list>
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
			${/\[[^\]]+\]/.test(data.citation ?? '') ? '<div class="text-muted small mt-2">Bagian dalam [kurung siku] belum ada di sumber. Lengkapi dulu sebelum dipakai.</div>' : ''}
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
							${(day.tasks ?? []).map((task) => `
								<li>
									<label class="d-flex gap-2 align-items-start mb-0">
										<input type="checkbox" class="form-check-input mt-1">
										<span>${escapeHtml(task)}</span>
									</label>
								</li>
							`).join('')}
						</ul>
					</div>
				`).join('')}
			</div>
		</div>
	`;
}

/* ---------- Form & event ---------- */

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

function updateQuizScore(item) {
	const list = item.closest('[data-quiz-list]');
	const scoreEl = list?.parentElement?.querySelector('[data-quiz-score]');

	if (!list || !scoreEl) {
		return;
	}

	const total = Number(scoreEl.dataset.total ?? 0);
	const answered = list.querySelectorAll('[data-quiz-item].is-answered').length;
	const correct = list.querySelectorAll('[data-quiz-item].is-answered-correct').length;

	scoreEl.textContent = answered >= total
		? `Skor akhir: ${correct}/${total}`
		: `Skor: ${correct}/${total}`;
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

		const renderers = {
			'text-tools': renderTextTools,
			flashcards: renderFlashcards,
			citation: renderCitation,
			'study-plan': renderStudyPlan,
		};
		const tool = form.dataset.aiForm;
		const data = payload.data ?? {};

		if (renderers[tool]) {
			renderResult(target, renderers[tool](data));
			pushHistory(tool, data, formData);

			if (tool === 'text-tools' && data.summary) {
				updateChatStudyContext(data.summary.title ?? 'Materi PDF', data.summary.plain_summary ?? '');
			}
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
	const chatForm = event.target.closest('[data-chat-form]');
	if (chatForm) {
		handleChatSubmit(event);
		return;
	}

	const form = event.target.matches?.('[data-ai-form]')
		? event.target
		: event.target.closest?.('form[data-ai-form]');

	if (!form) {
		return;
	}

	event.preventDefault();
	submitAiForm(form, event.submitter ?? null);
});

document.addEventListener('click', async (event) => {
	if (event.target.closest('[data-toggle-chat]')) {
		toggleChatDrawer();
		return;
	}

	if (event.target.closest('[data-chat-close]')) {
		closeChatDrawer();
		return;
	}

	if (event.target.closest('[data-open-chat]')) {
		openChatDrawer();
		return;
	}

	if (event.target.closest('[data-chat-clear]')) {
		if (confirm('Hapus riwayat chat dengan Tutor AI?')) {
			saveChatHistory([]);
			const container = document.querySelector('[data-chat-messages]');
			if (container) {
				container.innerHTML = `
					<div class="chat-bubble chat-bubble-bot">
						<div class="chat-bubble-content">
							Riwayat chat telah dibersihkan. Ada materi atau tugas kuliah lain yang ingin kamu diskusikan?
						</div>
					</div>
				`;
			}
		}
		return;
	}

	if (event.target.closest('[data-chat-remove-context]')) {
		removeChatStudyContext();
		return;
	}

	const promptChip = event.target.closest('[data-chat-suggest]');
	if (promptChip) {
		const input = document.querySelector('[data-chat-input]');
		if (input) {
			input.value = promptChip.dataset.chatSuggest ?? '';
			input.focus();
		}
		return;
	}

	const historyOpen = event.target.closest('[data-history-open]');

	if (historyOpen) {
		openHistoryEntry(historyOpen.dataset.historyId);
		return;
	}

	const historyDelete = event.target.closest('[data-history-delete]');

	if (historyDelete) {
		deleteHistoryEntry(historyDelete.dataset.historyId);
		return;
	}

	if (event.target.closest('[data-history-clear]')) {
		if (confirm('Hapus semua riwayat?')) {
			clearHistory();
		}

		return;
	}

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

		if (!item || item.classList.contains('is-answered')) {
			return;
		}

		const feedback = item.querySelector('[data-quiz-feedback]');
		const isCorrect = quizOption.dataset.correct === 'true';
		const explanation = quizOption.dataset.explanation ?? '';

		item.classList.add('is-answered');

		if (isCorrect) {
			item.classList.add('is-answered-correct');
		}

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

		updateQuizScore(item);
	}
});

document.addEventListener('input', (event) => {
	const input = event.target.closest('[data-chat-input]');
	if (input) {
		input.style.height = 'auto';
		input.style.height = `${Math.min(input.scrollHeight, 120)}px`;
	}
});

document.addEventListener('keydown', (event) => {
	if (event.key === 'Enter' && !event.shiftKey) {
		const input = event.target.closest('[data-chat-input]');
		if (input) {
			event.preventDefault();
			const form = input.closest('form');
			if (form) form.requestSubmit();
		}
	}
});

function initApp() {
	renderHistoryPanel();
	renderSavedChatMessages();
}

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', initApp);
} else {
	initApp();
}