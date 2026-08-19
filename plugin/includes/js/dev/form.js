/**
 * Development-only form filler.
 *
 * Knows about Contact Form 7 markup — including the conditional-group and
 * multi-step add-ons this site uses — but nothing about any particular form:
 * the values come from the caller, so the same engine serves every CF7 form on
 * any Plura site.
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
 * Needed because CF7 fields are not always ready when the form is: this site
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


/**
 * Find the controls CF7 rendered for a field name.
 *
 * Multi-value fields (checkbox, multi-select) are posted as `name[]`, so both
 * spellings have to be tried.
 *
 * @param {HTMLFormElement} form
 * @param {string} name
 * @return {Element[]}
 */
function controls(form, name) {

	const escaped = CSS.escape(name);

	return [...form.querySelectorAll(`[name="${escaped}"], [name="${escaped}[]"]`)];

}


/**
 * Whether a control sits inside a conditional group that is currently hidden.
 *
 * Deliberately not a general visibility test: fields on later steps of a
 * multi-step form are hidden too, and those do need filling.
 *
 * @param {Element} element
 * @return {boolean}
 */
function inHiddenGroup(element) {

	return !!element.closest('.wpcf7cf-hidden');

}


/**
 * Assign one value and let CF7 (and any listeners) know it changed.
 *
 * @param {Element[]} elements Controls sharing the field name.
 * @param {*} value
 * @return {Promise<boolean>} Whether anything was set.
 */
async function setControl(elements, value) {

	const first = elements[0];
	const type = first.type;

	if (type === 'file') {

		const files = (Array.isArray(value) ? value : [value]).filter(v => v instanceof File);

		if (!files.length) {
			return false;
		}

		const transfer = new DataTransfer();

		files.forEach(f => transfer.items.add(f));

		first.files = transfer.files;

	} else if (type === 'checkbox' || type === 'radio') {

		// A boolean toggles a lone checkbox (acceptance); anything else selects the
		// options whose value matches. Clicked rather than assigned, so CF7's own
		// handlers — and the conditional-group logic — see a real interaction.
		const wanted = typeof value === 'boolean'
			? (value ? [first.value] : [])
			: (Array.isArray(value) ? value : [value]).map(String);

		elements.forEach(element => {

			const should = typeof value === 'boolean' ? value : wanted.includes(element.value);

			if (element.checked !== should) {
				element.click();
			}

		});

		return true;

	} else if (first.tagName === 'SELECT') {

		// Options may still be loading.
		const option = await waitFor(() => [...first.options].find(o => o.value === String(value)));

		if (!option) {
			return false;
		}

		first.value = option.value;

	} else {

		// Respect the range a [date] field declares rather than posting a value the
		// browser will reject on submit.
		let next = String(value);

		if (type === 'date') {
			if (first.min && next < first.min) next = first.min;
			if (first.max && next > first.max) next = first.max;
		}

		first.value = next;

	}

	first.dispatchEvent(new Event('input', { bubbles: true }));
	first.dispatchEvent(new Event('change', { bubbles: true }));

	return true;

}


/**
 * Fill a CF7 form from a set of values keyed by field name.
 *
 * Every control exists in the DOM from the start, including those on later steps
 * of a multi-step form, so one pass fills the whole thing.
 *
 * @param {HTMLFormElement} form
 * @param {object} values Field name => value. Files come from file().
 * @param {object} [options]
 * @param {boolean} [options.groups] Fill fields inside hidden conditional groups too.
 * @return {Promise<{filled: string[], skipped: string[], missing: string[]}>}
 */
export async function fillForm(form, values, { groups = false } = {}) {

	const filled = [], skipped = [], missing = [];

	for (const [name, value] of Object.entries(values)) {

		const elements = controls(form, name);

		if (!elements.length) {
			missing.push(name);
			continue;
		}

		if (!groups && inHiddenGroup(elements[0])) {
			skipped.push(name);
			continue;
		}

		(await setControl(elements, value) ? filled : skipped).push(name);

	}

	console.info('[pb/dev] filled %d, skipped %d, missing %d', filled.length, skipped.length, missing.length);

	if (missing.length) {
		console.warn('[pb/dev] no control for:', missing.join(', '));
	}

	return { filled, skipped, missing };

}


/**
 * Advance a cf7mls multi-step form by one step.
 *
 * @param {HTMLFormElement} form
 * @return {boolean} Whether a next button was found and clicked.
 */
export function nextStep(form) {

	const button = [...form.querySelectorAll('button.cf7mls_next, input.cf7mls_next')]
		.find(b => b.offsetParent !== null);

	button?.click();

	return !!button;

}
