// popup2.js
import { createElement as el } from './utils.js';

let popupEl, popupId;

/**
 * Ensure a reusable hidden popup container exists in the DOM.
 * Returns the element.
 */
function ensurePopup() {
	if (popupEl) return popupEl;

	popupId = `pb-popup-${Date.now()}`;
	popupEl = el('div', { 
		class: 'pb-popup', 
		id: popupId, 
		style: { display: 'none' }, 
		role: 'dialog', 
		'aria-modal': 'true' 
	}, [
		el('div', { class: 'pb-popup-title', 'aria-live': 'polite' }, ''),
		el('div', { class: 'pb-popup-content' }, '')
	]);

	// Important: append to DOM so Fancybox can clone it by selector
	document.body.appendChild(popupEl);

	return popupEl;
}

/**
 * Update the popup content. If `data.id` matches last loaded, skip DOM work.
 * @param {{ title?: string, content?: string, id?: number|string }} data
 */
function popUpdate(data) {
	const elPopup = ensurePopup();

	// Optional optimization: skip if same content id already loaded
	if (data?.id != null && elPopup.dataset.pbLoadedPostId === String(data.id)) {
		return;
	}
	if (data?.id != null) {
		elPopup.dataset.pbLoadedPostId = String(data.id);
	}

	const titleEl   = elPopup.querySelector('.pb-popup-title');
	const contentEl = elPopup.querySelector('.pb-popup-content');

	if (titleEl)   titleEl.innerHTML   = data?.title   ?? '';
	if (contentEl) contentEl.innerHTML = data?.content ?? '';
}

/**
 * Public API: update content, then open with Fancybox.
 * @param {{ title?: string, content?: string, id?: number|string }} data
 */
export function popUpdatePopupContent(data) {
	if (!window.Fancybox || typeof window.Fancybox.show !== 'function') {
		console.warn('Fancybox not available. Did you load @fancyapps/ui?');
		return;
	}

	popUpdate(data);

	// You can also pass the element directly instead of a selector:
	// Fancybox.show([{ src: ensurePopup(), type: 'html' }]);
	// but cloning by ID keeps your current approach:
	window.Fancybox.show([{ src: `#${popupId}`, type: 'clone' }]);
}
