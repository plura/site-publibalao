import { resolvePage } from './api2.js';
import { initAgreementLinksWithPreload } from './page-loader.js';
import { popOpenAndUpdatePopup } from './popup.js';


document.addEventListener('DOMContentLoaded', () => {

	//popup para
	const source = {
		acf_field: 'pb-source-id',
		params: window.pb_data?.lang ? { lang_current: window.pb_data.lang.current, lang_default: window.pb_data.lang.default, nocache: Date.now() } : {},
		popup_id: !window.pb_data?.lang || window.pb_data.lang.current === 'pt-pt' ? 12939 : 13332
	};

	//add main width variable
	document.documentElement.style.setProperty('--w', `calc(100dvw)`);


	const observer = new ResizeObserver(entries => {

		document.querySelectorAll('.pb-grid').forEach(grid =>

			grid.style.setProperty(`--grid-w`, `${grid.offsetWidth}px`)

		);

	});

	observer.observe(document.body);


	const page = (id) => {

		const ids = Array.isArray(id) ? id : [id];

		for (let [index, value] of ids.entries()) {

			if (document.body.classList.contains(value)) {
				return true;
			}

		}

		return false;

	};


	//const target = document.querySelector('.bcp-slider[data-id="intro1"'), timeout = 5000, url = '';

	console.log('pb scripts loaded');

	//Image Carousel
	const carousel_targets = document.querySelectorAll('.pb-carousel-images');

	if (carousel_targets.length) {

		carousel_targets.forEach(target => {

			[...target.children].forEach(element => element.classList.add('f-carousel__slide'));

			const carousel = new Carousel(target, {

				transition: 'crossfade'


			}, { Autoplay });


		});

	}


	//popup buttons
	document.querySelectorAll('.pb-button-content-popup').forEach(element => {
		console.log(element);
		resolvePage({ id: element.dataset.pbTargetId, acf_field: source.acf_field, params: source.params }).then(data => {
			element.addEventListener('click', event => {
				event.preventDefault();
				popOpenAndUpdatePopup(source.popup_id, { title: data.page_title, content: data.content_html, id: data.id });

			})
		});

	});





	//HEADINGS NAV
	const pb_headings_nav_holder = document.querySelector('.pb-headings-nav'), pb_headings_nav_target = document.querySelector('.et_pb_post_content');

	if (pb_headings_nav_holder && pb_headings_nav_target) {

		createTreeNavigation({ target: pb_headings_nav_target, holder: pb_headings_nav_holder, threshold: 0.6 });

	}



	// Example: pass a NodeList (from querySelectorAll)
	if (document.body.classList.contains('single-mec-events')) {



		initAgreementLinksWithPreload({
			selector: 'form .mec-book-field-agreement a',
			acf_field: source.acf_field,
			fallback_to_page: true,
			params: source.params,
			handler: ({ title, content, page_id }) => {
				popOpenAndUpdatePopup(popid, { title, content, id: page_id });
			}
		});

	}

});