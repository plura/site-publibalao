/**
 * Primitives shared by the dev routines and by the datasets they consume.
 *
 * Kept apart from the routines so a dataset can build a File without importing
 * the whole form filler.
 */


/**
 * Build a File for a [file] input. Contents are placeholder bytes — CF7 validates
 * on extension and size, not on the file actually being a readable document.
 *
 * @param {string} name File name; its extension decides the generated type.
 * @param {object} [options]
 * @param {string} [options.type] MIME type override.
 * @return {File}
 */
export function file(name, { type } = {}) {

	const ext = name.split('.').pop().toLowerCase();

	// A 1x1 transparent PNG, so image/* uploads survive anything that sniffs them.
	const png = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

	if (['png', 'jpg', 'jpeg', 'gif', 'webp'].includes(ext)) {

		const bytes = Uint8Array.from(atob(png), c => c.charCodeAt(0));

		return new File([bytes], name, { type: type ?? 'image/png' });

	}

	// Minimal PDF skeleton: no xref table, so it will not open in a reader, but it
	// carries the right magic bytes for anything that checks them.
	if (ext === 'pdf') {

		const pdf = '%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n' +
			'2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n' +
			'3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\n' +
			'trailer<</Root 1 0 R>>\n%%EOF\n';

		return new File([pdf], name, { type: type ?? 'application/pdf' });

	}

	return new File([`placeholder for ${name}`], name, { type: type ?? 'text/plain' });

}


/**
 * Poll until a predicate returns something truthy.
 *
 * Needed because form fields are not always ready when the form is: this site
 * populates the country <select> from a REST call, so its options appear well
 * after DOMContentLoaded.
 *
 * @param {Function} fn Predicate; its return value resolves the promise.
 * @param {object} [options]
 * @param {number} [options.timeout] Milliseconds before giving up.
 * @param {number} [options.interval] Milliseconds between attempts.
 * @return {Promise<*>} Resolves with the predicate's value, or null on timeout.
 */
export function waitFor(fn, { timeout = 5000, interval = 50 } = {}) {

	return new Promise(resolve => {

		const started = Date.now();

		const tick = () => {

			const value = fn();

			if (value) {
				resolve(value);
			} else if (Date.now() - started > timeout) {
				resolve(null);
			} else {
				setTimeout(tick, interval);
			}

		};

		tick();

	});

}
