// api.js
import { buildParamsFromHref } from './utils.js';

/**
 * Resolve a page via the Publibalao REST API.
 *
 * Accepts:
 *   - id: number
 *   - url: string
 *   - page_path: string
 *   - acf_field: string (optional)
 *   - fallback_to_page: boolean (default true)
 */
export async function resolvePage({
	id,
	url,
	page_path,
	acf_field,
	fallback_to_page = true,
	// Derived from the localized REST root rather than hardcoded: WordPress lives in
	// a /wp/ subdirectory here, so a root-relative /wp-json/ path does not resolve.
	base = `${window.plura_wp_data?.restURL ?? '/wp-json/'}publibalao/v1/page`,
	params: extraParams = {}
} = {}) {
	const params = new URLSearchParams();
	if (id) params.set('id', String(id));
	if (url) params.set('url', String(url));
	if (page_path) params.set('page_path', String(page_path));
	if (acf_field) params.set('acf_field', String(acf_field));
	if (fallback_to_page) params.set('fallback_to_page', '1');
	for (const [k, v] of Object.entries(extraParams || {})) {
		if (v !== undefined && v !== null && !params.has(k)) {
			params.set(k, String(v));
		}
	}
	const res = await fetch(`${base}?${params.toString()}`, {
		credentials: 'same-origin'
	});

	if (!res.ok) {
		const text = await res.text().catch(() => '');
		throw new Error(`[PublibalaoAPI] HTTP ${res.status}: ${text || 'Request failed'}`);
	}
	return res.json();
}

/**
 * Shortcut that takes an href and automatically extracts
 * both url + page_path using buildParamsFromHref().
 */
export async function resolvePageFromHref(href, opts = {}) {
	const { url, page_path } = buildParamsFromHref(href);
	return resolvePage({ url, page_path, ...opts });
}
