document.addEventListener('DOMContentLoaded', () => {


	if( p.page( ['page-id-2109', 'page-id-2713'] ) ) { 
 
		new PBLocations({
			mapHolder: document.getElementById('map-holder'),
			listHolder: document.getElementById('map-locations-holder'),
			restPath: pbobj.restURL
		});


		if( p.page( 'page-id-2713' ) ) {

			const observer = new ResizeObserver( entries => {

				let grid = document.querySelector('#services-holder .pb-grid'),
					grid_item = document.querySelector('.grid-item:last-child'),
					slider_holder = document.getElementById('service-weddings-video-holder'),
					slider = slider_holder.querySelector('rs-module-wrap'); 

				if( slider ) {

					[ ['sliderh', slider_holder] ]

					.forEach( dim => dim[1].style.setProperty(`--${dim[0]}`, `${slider.offsetHeight}px`) );

					//[ ['sliderh', slider]/*, ['serviceh', service ]*/ ]

					//.forEach( dim => service.style.setProperty(`--${dim[0]}`, `${dim[1].offsetHeight}px`) );

				}

			});

			observer.observe( document.body );

		}


	//PACOTES
	} else if( p.page('page-id-2206' ) ) {

		const select = document.querySelector('#popmake-2290 select');

		document.querySelectorAll('.pb-package .et_pb_button').forEach( (element, index) => {

			element.addEventListener('click', event => {

				event.preventDefault();

				select.selectedIndex = index;

				PUM.open(2290);

			});		

		});


	}

});