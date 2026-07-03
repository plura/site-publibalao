// page-loader.js
import { resolvePageFromHref } from './api2.js';

const _pbAgreementCache = new Map();

/**
 * Build a unique cache key for a link based on href, acf_field, fallback flag, and extra params.
 */
function _buildCacheKey(href, acf_field, fallback_to_page, params) {
	const qp = params ? new URLSearchParams(params).toString() : '';
	return `${href}::acf=${acf_field || ''}::fb=${fallback_to_page ? 1 : 0}::qp=${qp}`;
}

async function _preloadLinkElement(a, acf_field, fallback_to_page, params) {
	if (!(a instanceof Element)) return;
	const href = a.getAttribute('href') || a.href || '';
	const key = _buildCacheKey(href, acf_field, fallback_to_page, params);
	if (_pbAgreementCache.has(key)) return;

	try {
		const data = await resolvePageFromHref(href, { acf_field, fallback_to_page, params });
		_pbAgreementCache.set(key, data);
		a.dataset.pbResolved = '1';
		console.debug('[publibalao/page] preloaded', href, data);
	} catch (err) {
		console.warn('[publibalao/page] preload error', href, err);
	}
}

/**
 * Internal: deliver resolved page result to the element + handler.
 * @private
 */
function _deliverResolvedPage(el, data, handler, cached = false) {
	console.log(`[publibalao/page]${cached ? ' (cached)' : ''}`, data);
	el.dataset.pbResolved = '1';
	if (typeof handler === 'function') {
        handler({
            title: data.page_title,
            content: data.content_html,
            page_id: data.page_id
        });
	}
}

/**
 * Initialize link handler + preload.
 * @param {object} options
 *   selector: CSS selector for links
 *   acf_field: ACF field to use for linked content
 *   fallback_to_page: boolean
 *   handler: optional callback function called with {title, content, page_id} when content is ready
 *   root: DOM element to bind to (optional)
 *   params: extra query params to forward to API (e.g., { lang_current: 'en', lang_default: 'pt' })
 */
export function initAgreementLinksWithPreload({
	selector = 'form .mec-book-field-agreement a',
	acf_field,
	fallback_to_page = true,
	handler,
	root = document,
	params
} = {}) {
	if (!root._pbDelegatedAgreementBound) {
		root._pbDelegatedAgreementBound = true;
		root.addEventListener('click', async (e) => {
			const a = e.target instanceof Element ? e.target.closest(selector) : null;
			if (!a) return;
			e.preventDefault();

			const href = a.getAttribute('href') || a.href || '';
			const key = _buildCacheKey(href, acf_field, fallback_to_page, params);

			if (_pbAgreementCache.has(key)) {
				const cached = _pbAgreementCache.get(key);
				_deliverResolvedPage(a, cached, handler, true);
				return;
			}

			try {
				const data = await resolvePageFromHref(href, { acf_field, fallback_to_page, params });
				_pbAgreementCache.set(key, data);
				_deliverResolvedPage(a, data, handler, false);
			} catch (err) {
				console.error('[publibalao/page] error', err);
			}
		});
	}

	// Preload existing links
	root.querySelectorAll(selector).forEach(a => _preloadLinkElement(a, acf_field, fallback_to_page, params));

	// Observe future links (multistep support)
	const observer = new MutationObserver(mutations => {
		mutations.forEach(mutation => {
			if (mutation.type === 'childList' && mutation.addedNodes.length) {
				mutation.addedNodes.forEach(node => {
					if (!(node instanceof Element)) return;
					if (node.matches && node.matches(selector)) {
						_preloadLinkElement(node, acf_field, fallback_to_page, params);
					} else {
						const found = node.querySelectorAll ? node.querySelectorAll(selector) : [];
						found.forEach(a => _preloadLinkElement(a, acf_field, fallback_to_page, params));
					}
				});
			}
		});
	});

	observer.observe(root, {
		childList: true,
		subtree: true
	});
}
