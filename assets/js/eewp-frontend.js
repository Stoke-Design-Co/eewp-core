(() => {
	'use strict';

	const data = window.eewpFrontend || {};
	const postId = data.postId || 0;
	const toggle = document.querySelector('.eewp-toggle');
	const toolbar = document.querySelector('.eewp-toolbar');
	const body = document.body;
	const elementorConfig = data.elementor || {};
	const keepSelectors = Array.isArray(elementorConfig.keepSelectors) ? elementorConfig.keepSelectors : [];

	if (!toggle || !postId) {
		return;
	}

	const storageKey = 'eewp_mode_site';

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

	function buildElementorVisibilityStyles() {
		const hideTargets = ['.elementor-section', '.e-con'];
		const keepTargets = [
			'.elementor-location-header',
			'.elementor-location-footer',
			'.eewp-keep',
			'[data-eewp-retain=\"yes\"]',
			...keepSelectors,
		]
			.map((selector) => (typeof selector === 'string' ? selector.trim() : ''))
			.filter((selector, index, arr) => selector && arr.indexOf(selector) === index);

		if (!keepTargets.length) {
			return;
		}

		const style = document.createElement('style');
		style.id = 'eewp-elementor-visibility';
		style.textContent = `
body.eewp-mode-easy ${hideTargets.join(', body.eewp-mode-easy ')} { display: none !important; }
body.eewp-mode-easy ${keepTargets.join(', body.eewp-mode-easy ')} { display: revert !important; }
`;
		document.head.appendChild(style);
	}

	function init() {
		buildElementorVisibilityStyles();
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
