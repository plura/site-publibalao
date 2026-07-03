function popupid(popupId) {
	return `#popmake-${popupId}`;
}

/**
 * Update the content of a Popup Maker popup container.
 * @param {number|string} popupId
 * @param {object} data { title: string, content: string, postId?: number|string }
 */
export function popUpdatePopupContent(popupId, data) {
	const popup = document.querySelector(popupid(popupId));
	if (!popup) {
		console.warn(`Popup with ID ${popupId} not found.`);
		return;
	}

	// If we have postId and it’s the same as previously loaded, skip update
	if (data.id != null && popup.dataset.pbLoadedPostId === String(data.id)) {
		return;
	}

	// Mark as loaded
	if (data.id != null) {
		popup.dataset.pbLoadedPostId = String(data.id);
	}

	const titleEl   = popup.querySelector(':scope > .popmake-title');
	const contentEl = popup.querySelector(':scope > .popmake-content');

	if (titleEl)   titleEl.innerHTML   = data.title   || '';
	if (contentEl) contentEl.innerHTML = data.content || '';
}

/**
 * Open a Popup Maker popup by ID, update its content if needed, then open it.
 * @param {number|string} popupId
 * @param {object} data { title: string, content: string, postId?: number|string }
 */
export function popOpenAndUpdatePopup(popupId, data) {
	if (!window.PUM || typeof window.PUM.open !== 'function') {
		console.warn('Popup Maker API (PUM.open) not found.');
		return;
	}

	// Open popup
	window.PUM.open(popupId);

	// Update content
	popUpdatePopupContent(popupId, data);
}
