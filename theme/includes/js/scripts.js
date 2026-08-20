// Entry module: deferred by definition, so it runs once the document is parsed
// and before DOMContentLoaded — no listener needed to reach the DOM.


const page = (id) => {

	const ids = Array.isArray( id ) ? id : [ id ];

	for( let  [index, value] of ids.entries() ) {

		if( document.body.classList.contains(value) ) {
			return true;
		}

	}

	return false;

};

//add 'real width' variable
//document.documentElement.style.setProperty('--w', `calc(100vw - ${ p.scrollWidth() }px)`);



//replace language names with their country code only
document.querySelectorAll('header :is(.nav, .mobile_nav) .menu-item.wpml-ls-menu-item').forEach( element => {

	//Safari does not allow look behind...
	//const lng = element.getAttribute('class').match(/(?<=wpml-ls-item-)([a-z]+)/)[0];
	const lng = element.getAttribute('class').match(/(wpml-ls-item-)([a-z]+)/)[2];

	element.querySelector('span').textContent = lng;

});




//add img width variable to pb-sponsor/pb-team-member in order do normalize module/row height
const pb_team_observer = new ResizeObserver( entries =>

	entries.forEach( entry => entry.target.style.setProperty('--imgw', `${ entry.target.offsetWidth}px`) )

);

document.querySelectorAll(':is(.pb-team-member, .pb-sponsor) img').forEach( element => pb_team_observer.observe( element ) );



//Fancybox
//guarded: this is one entry module now, so a missing CDN global here would abort
//every page-specific branch below it
if( typeof Fancybox !== 'undefined' ) {

	Fancybox.bind('.wp-block-image a');

}



//FIBAQ

if( page( ['pb-page-fibaq', 'single-pb_event'] ) ) {

	//popup via menu
	document.querySelector('header .menu-item-808 a')?.addEventListener('click', event => {

		event.preventDefault();

		PUM.open(228);

	});


//MAP CONTACTOS (PUBLIBALAO E FIBAQ) / HOME / PACOTES E SERVICOS
} else if( page( ['pb-page-contacts-publibalao', 'pb-page-contacts-fibaq', 'pb-page-home', 'pb-page-packages'] ) ) { 


	//Home / Contactos (Publibalao e FIBAQ)
	if( page( ['pb-page-contacts-publibalao', 'pb-page-home', 'pb-page-contacts-fibaq'] ) ) {

		const { PBLocations } = await import('./locations.js');

		new PBLocations({
			mapHolder: document.getElementById('map-holder'),
			listHolder: document.getElementById('map-locations-holder'),
			restPath: plura_wp_data.restURL
		});

	}

	//Home / Pacotes e Serviços
	if( page( ['pb-page-home', 'pb-page-packages'] ) ) {

		const observer = new ResizeObserver( entries => {

			//the last child is the item whichever markup .pb-grid is holding, so this
			//survives the migration to [plura-wp-posts]
			const grid = document.querySelector('#services-holder .pb-grid'),
				grid_item = grid?.querySelector(':scope > :last-child'),
				slider_holder = document.getElementById('service-weddings-video-holder'),
				slider = slider_holder?.querySelector('rs-module-wrap');

			if( slider ) {

				[slider_holder, grid_item].filter( Boolean )

				.forEach( element => element.style.setProperty('--sliderh', `${slider.offsetHeight}px`) );

			}

		});

		observer.observe( document.body );


		//PACOTES
		if( page('pb-page-packages') ) {

			const select = document.querySelector('#popmake-2290 select');


			document.querySelectorAll('.pb-grid.packages .pb-grid-item').forEach( (element, index) => {

				element.addEventListener('click', event => {

					event.preventDefault();

					select.selectedIndex = index;

					PUM.open(2290);

				});		

			});


		}

	}


//FIBAQ SUBSITE [FORM]
} else if( page('pb-page-fibaq-registration') ) {

	const { PBForm } = await import('./fibaq-form.js');

	new PBForm({ config: {
		fibaq: window.pbobj?.fibaq,
		lang: plura_wp_data.lang,
		restURL: plura_wp_data.restURL
	} });

}

