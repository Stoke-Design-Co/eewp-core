(() => {
	'use strict';

	const metaBox = document.querySelector('.eewp-meta-box');

	if (!metaBox) {
		return;
	}

	const rowsWrapper = metaBox.querySelector('.eewp-rows-wrapper');
	const rowsContainer = metaBox.querySelector('.eewp-rows');
	const addButton = metaBox.querySelector('.eewp-add-row');
	const enabledToggle = metaBox.querySelector('input[name="eewp_enabled"]');
	const templateEl = document.getElementById('eewp-row-template');
	const templateHtml = templateEl ? templateEl.innerHTML.trim() : '';

	const placeholderText = metaBox.dataset.placeholder || 'No image selected';

	const mediaArgs = {
		title: (window.eewpAdmin && eewpAdmin.mediaTitle) || 'Select image',
		button: {
			text: (window.eewpAdmin && eewpAdmin.mediaButton) || 'Use this image',
		},
		multiple: false,
	};

	let mediaFrame = null;

	function toggleRowsVisibility() {
		if (!rowsWrapper) {
			return;
		}
		rowsWrapper.style.display = enabledToggle && enabledToggle.checked ? '' : 'none';
	}

	function setNextIndex(value) {
		if (rowsContainer) {
			rowsContainer.dataset.nextIndex = String(value);
		}
	}

	function getNextIndex() {
		if (!rowsContainer) {
			return 0;
		}
		const value = parseInt(rowsContainer.dataset.nextIndex || rowsContainer.children.length || 0, 10);
		return Number.isNaN(value) ? 0 : value;
	}

	function updatePreview(row, attachmentId, attachmentHtml) {
		const preview = row.querySelector('.eewp-image-preview');
		const input = row.querySelector('.eewp-image-id');
		if (input) {
			input.value = attachmentId ? attachmentId : '';
		}

		if (!preview) {
			return;
		}

		if (attachmentId && attachmentHtml) {
			preview.innerHTML = attachmentHtml;
		} else {
			preview.innerHTML = `<span class="eewp-image-placeholder">${placeholderText}</span>`;
		}
	}

	function attachRowEvents(row) {
		row.addEventListener('click', (event) => {
			const target = event.target;

			if (target.closest('.eewp-delete-row')) {
				event.preventDefault();
				row.remove();
				renumberRows();
				return;
			}

			if (target.closest('.eewp-select-image')) {
				event.preventDefault();
				openMediaFrame(row);
				return;
			}

			if (target.closest('.eewp-remove-image')) {
				event.preventDefault();
				updatePreview(row, null, null);
				return;
			}

			if (target.closest('.eewp-move-up')) {
				event.preventDefault();
				const prev = row.previousElementSibling;
				if (prev) {
					rowsContainer.insertBefore(row, prev);
					renumberRows();
				}
				return;
			}

			if (target.closest('.eewp-move-down')) {
				event.preventDefault();
				const next = row.nextElementSibling;
				if (next) {
					rowsContainer.insertBefore(next, row);
					renumberRows();
				}
			}
		});
	}

	function renumberRows() {
		const rows = rowsContainer ? Array.from(rowsContainer.querySelectorAll('.eewp-row')) : [];
		rows.forEach((row, index) => {
			row.dataset.index = String(index);
			row.querySelectorAll('input, textarea').forEach((field) => {
				const name = field.getAttribute('name');
				if (name) {
					field.setAttribute('name', name.replace(/eewp_rows\\[[^\\]]+\\]/, `eewp_rows[${index}]`));
				}
				if (field.id && field.id.indexOf('eewp-row-text-') === 0) {
					field.id = `eewp-row-text-${index}`;
				}
			});

			const label = row.querySelector('label[for^="eewp-row-text-"]');
			if (label) {
				label.setAttribute('for', `eewp-row-text-${index}`);
			}
		});

		setNextIndex(rows.length);
	}

	function addRow() {
		if (!rowsContainer || !templateHtml) {
			return;
		}

		const index = getNextIndex();
		const markup = templateHtml.replace(/__INDEX__/g, index);
		const wrapper = document.createElement('div');
		wrapper.innerHTML = markup;
		const row = wrapper.firstElementChild;

		if (!row) {
			return;
		}

		rowsContainer.appendChild(row);
		attachRowEvents(row);
		setNextIndex(index + 1);
		renumberRows();
	}

	function openMediaFrame(row) {
		if (mediaFrame) {
			mediaFrame.off('select');
		}

		mediaFrame = wp.media(mediaArgs);
		mediaFrame.on('select', () => {
			const attachment = mediaFrame.state().get('selection').first().toJSON();
			const size = (attachment.sizes && (attachment.sizes.medium || attachment.sizes.thumbnail)) || null;
			const src = size && size.url ? size.url : attachment.url;
			const alt = attachment.alt || attachment.title || '';
			const html = src ? `<img src="${src}" alt="${alt.replace(/"/g, '&quot;')}" />` : '';
			updatePreview(row, attachment.id, html);
		});
		mediaFrame.open();
	}

	function bootstrap() {
		toggleRowsVisibility();

		if (rowsContainer) {
			Array.from(rowsContainer.querySelectorAll('.eewp-row')).forEach((row) => attachRowEvents(row));
			if (!rowsContainer.dataset.nextIndex) {
				setNextIndex(rowsContainer.children.length);
			}
		}

		if (addButton) {
			addButton.addEventListener('click', (event) => {
				event.preventDefault();
				addRow();
			});
		}

		if (enabledToggle) {
			enabledToggle.addEventListener('change', toggleRowsVisibility);
		}
	}

	document.addEventListener('DOMContentLoaded', bootstrap);
})();
