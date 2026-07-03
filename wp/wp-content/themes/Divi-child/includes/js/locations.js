function PBLocations({restPath, mapHolder, listHolder}) {


	let map, map_group, markers, ui_map, ui_list;


	const

		PRFX = 'pb-locations-',

		init = data => {

			if( mapHolder ) {

				init_map( mapHolder );

				init_data( data );

			}

		},

		init_data = data => { 

			markers = [];

			let ui_list_nav, ui_list_nav_item, ui_list_nav_item_trigger, ul_id = `${PRFX}map-list`;

			if( ui_list ) {

				( ui_list_nav = ui_list.appendChild( document.createElement('ul') ) ).classList.add( `${ ul_id }-nav` );
				
			}


			data.forEach( location => { 

				marker = L.marker( [ Number( location.lat ), Number( location.lng ) ] )

							//.setIcon( getIcon() )

							.bindPopup(`${location.name}`, {className: `${PRFX}-location`})

							.addTo( map );

				markers.push( marker );



				( ui_list_nav_item = ui_list_nav.appendChild( document.createElement('li') ) ).classList.add(`${ul_id}-nav-item`);

				( ui_list_nav_item_trigger = ui_list_nav_item.appendChild( document.createElement('a') ) ).classList.add(`${ ul_id }-nav-item-trigger`);

				ui_list_nav_item_trigger.textContent = location.name;

			});

			zoom( markers );

		},


		init_map = target => {


			const n = new Date().getTime(), map_id = `${PRFX}map-${ n }`;
 
			( ui_map = target.appendChild( document.createElement('div') ) ).setAttribute('id', map_id);

			map = L.map( map_id, {scrollWheelZoom: false} );

			//L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			L.tileLayer('http://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}.png', {

				attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'

			}).addTo( map );


			if( listHolder ) {

				empty( listHolder );

				( ui_list = listHolder.appendChild( document.createElement('div') ) ).setAttribute('id', `${PRFX}map-locations-${ n }` );

			}


			ui_map.classList.add( `${PRFX}map` );


		},


		empty = element => {

			while ( element.firstChild ) {
  				
  				element.removeChild( element.lastChild );
			}

		},


		zoom = markers => {

			if( map_group ) {

				map_group.clearLayers();

				map_group.remove();

			}

			if( markers.length ) {

				map_group = L.featureGroup( markers ).addTo( map );

				map.fitBounds( map_group.getBounds() );

			}

		};


	console.log(restPath + 'pb/v1/location/' );


	fetch(restPath + 'pb/v1/location/').then(response => response.json()).then(response => init(response));

}