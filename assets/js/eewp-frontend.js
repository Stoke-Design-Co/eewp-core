(() => {
	'use strict';

	const data = window.eewpFrontend || {};
	const postId = data.postId || 0;
	const toggle = document.querySelector('.eewp-toggle');
	const toolbar = document.querySelector('.eewp-toolbar');
	const body = document.body;
	const elementorConfig = data.elementor || {};
	const keepSelectors = Array.isArray(elementorConfig.keepSelectors) ? elementorConfig.keepSelectors : [];
	// Cover classic sections/containers and new flexbox containers (.e-con).
	const elementorSelector = '.elementor-section, .elementor-container, .elementor-element, .e-con';
	let elementorNodes = [];

	if (!toggle || !postId) {
		return;
	}

	const storageKey = 'eewp_mode_site';

	function refreshElementorNodes() {
		elementorNodes = Array.from(document.querySelectorAll(elementorSelector));
	}

	function shouldKeep(node) {
		return keepSelectors.some((selector) => {
			try {
				return node.matches(selector) || node.closest(selector);
			} catch (err) {
				return false;
			}
		});
	}

	function toggleElementorVisibility(isEasy) {
		if (!elementorNodes.length) {
			return;
		}

		elementorNodes.forEach((node) => {
			if (shouldKeep(node)) {
				return;
			}

			if (isEasy) {
				if (node.dataset.eewpDisplay === undefined) {
					node.dataset.eewpDisplay = node.style.display || '';
				}
				node.style.display = 'none';
				return;
			}

			if (node.dataset.eewpDisplay !== undefined) {
				node.style.display = node.dataset.eewpDisplay;
				delete node.dataset.eewpDisplay;
			}
		});
	}

	function setMode(mode) {
		const isEasy = mode === 'easy';
		body.classList.toggle('eewp-mode-easy', isEasy);
		toggle.setAttribute('aria-pressed', isEasy ? 'true' : 'false');
		toggleElementorVisibility(isEasy);
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
		refreshElementorNodes();
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
