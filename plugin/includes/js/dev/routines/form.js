/**
 * Dev routine: fill a Contact Form 7 form from a dataset.
 *
 * Knows about CF7 markup — including the conditional-group and multi-step
 * add-ons — but nothing about any particular form: the values come from the
 * caller, so the same routine serves every CF7 form on any Plura site.
 *
 *   ?dev=form&devid=<dataset>
 */

import { waitFor } from '../utils.js';


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
			? []
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
 * @param {object} values Field name => value. Files come from utils.file().
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


/**
 * Entry point called by the dispatcher.
 *
 * @param {object} context
 * @param {HTMLFormElement} context.target Form to fill.
 * @param {object} context.data Dataset resolved from ?devid=.
 * @param {URLSearchParams} context.params Full query string, for routine flags.
 * @return {Promise<object|undefined>}
 */
export async function run({ target, data, params }) {

	if (!target) {

		console.error('[pb/dev] form routine needs a target form');

		return;

	}

	if (!data) {

		console.error('[pb/dev] form routine needs a dataset — pass ?devid=');

		return;

	}

	//?devgroups=1 fills conditional groups that are currently closed
	return fillForm(target, data, { groups: params?.get('devgroups') === '1' });

}
