// utils.js
export function buildParamsFromHref(href) {
	try {
		const u = new URL(href, window.location.href);
		const page_path = u.pathname.replace(/^\/+|\/+$/g, '');
		return { url: u.href, page_path };
	} catch {
		return { url: href || '', page_path: '' };
	}
}


export function createElement(tag, attrs = {}, children = []) {
	const el = document.createElement(tag);
	for (const [key, value] of Object.entries(attrs)) {
		if (key === 'class') {
			el.className = value;
		} else if (key === 'style' && typeof value === 'object') {
			for (const [prop, val] of Object.entries(value)) {
				el.style[prop] = val;
			}
		} else {
			el.setAttribute(key, value);
		}
	}
	if (!Array.isArray(children)) {
		children = [children];
	}
	children.forEach(child => {
		if (typeof child === 'string') {
			el.appendChild(document.createTextNode(child));
		} else if (child instanceof Node) {
			el.appendChild(child);
		}
	});
	return el;
}
