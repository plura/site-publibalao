document.addEventListener('DOMContentLoaded', () => {

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
	Fancybox.bind('.wp-block-image a');



	//FIBAQ
	
	if( page( ['wpmlobj-id-14', 'single-pb_event'] ) ) {

		//popup via menu
		document.querySelector('header .menu-item-808 a')?.addEventListener('click', event => {

			event.preventDefault();

			PUM.open(228);

		});

	
	//MAP CONTACTOS (PUBLIBALAO E FIBAQ) / HOME / PACOTES E SERVICOS
	} else if( page( ['wpmlobj-id-2065', 'wpmlobj-id-5639', 'wpmlobj-id-2851', 'wpmlobj-id-2869'] ) ) { 


		//Home / Contactos (Publibalao e FIBAQ)
		if( page( ['wpmlobj-id-2065', 'wpmlobj-id-2851', 'wpmlobj-id-5639'] ) ) {

			console.log('wee');
		
			new PBLocations({
				mapHolder: document.getElementById('map-holder'),
				listHolder: document.getElementById('map-locations-holder'),
				restPath: pbobj.restURL
			});

		}

		//Home / Pacotes e Serviços
		if( page( ['wpmlobj-id-2851', 'wpmlobj-id-2869'] ) ) {

			const observer = new ResizeObserver( entries => {

				let grid = document.querySelector('#services-holder .pb-grid'),
					grid_item = grid.querySelector('.pb-grid-item:last-child'),
					slider_holder = document.getElementById('service-weddings-video-holder'),
					slider = slider_holder.querySelector('rs-module-wrap'); 

				if( slider ) {		 

					[slider_holder, grid_item] 

					.forEach( element => element.style.setProperty('--sliderh', `${slider.offsetHeight}px`) );


				}

			});

			observer.observe( document.body );


			//PACOTES
			if( page('wpmlobj-id-2869') ) {

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
	} else if( page('wpmlobj-id-3066') ) {

		new PBForm({config: pbobj});
    
	}

});