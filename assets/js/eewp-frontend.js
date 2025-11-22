(() => {
	'use strict';

	const data = window.eewpFrontend || {};
	const postId = data.postId || 0;
	const toggle = document.querySelector('.eewp-toggle');
	const body = document.body;

	if (!toggle || !postId) {
		return;
	}

	const storageKey = `eewp_mode_${postId}`;

	function setMode(mode) {
		const isEasy = mode === 'easy';
		body.classList.toggle('eewp-mode-easy', isEasy);
		toggle.setAttribute('aria-pressed', isEasy ? 'true' : 'false');
		try {
			localStorage.setItem(storageKey, isEasy ? 'easy' : 'normal');
		} catch (err) {
			// Ignore storage failures.
		}
	}

	function getStoredMode() {
		try {
			return localStorage.getItem(storageKey);
		} catch (err) {
			return null;
		}
	}

	function init() {
		const stored = getStoredMode();
		if (stored === 'easy') {
			setMode('easy');
		} else {
			setMode('normal');
		}

		toggle.addEventListener('click', (event) => {
			event.preventDefault();
			const isEasy = body.classList.contains('eewp-mode-easy');
			setMode(isEasy ? 'normal' : 'easy');
		});
	}

	document.addEventListener('DOMContentLoaded', init);
})();
