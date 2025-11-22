(() => {
	'use strict';

	const data = window.eewpFrontend || {};
	const postId = data.postId || 0;
	const toggle = document.querySelector('.eewp-toggle');
	const toolbar = document.querySelector('.eewp-toolbar');
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

	function applyToolbarPosition() {
		if (!toolbar || !data.toolbar || !data.toolbar.position) {
			return;
		}
		toolbar.classList.remove('eewp-toolbar--left', 'eewp-toolbar--right');
		toolbar.classList.add(`eewp-toolbar--${data.toolbar.position === 'left' ? 'left' : 'right'}`);
	}

	function init() {
		const stored = getStoredMode();
		if (stored === 'easy') {
			setMode('easy');
		} else {
			setMode('normal');
		}

		applyToolbarPosition();

		toggle.addEventListener('click', (event) => {
			event.preventDefault();
			const isEasy = body.classList.contains('eewp-mode-easy');
			setMode(isEasy ? 'normal' : 'easy');
		});
	}

	document.addEventListener('DOMContentLoaded', init);
})();
