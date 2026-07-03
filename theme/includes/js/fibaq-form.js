function PBForm({config}) {


	let form = document.querySelector('form.wpcf7-form');

	//translate buttons / include select correct translations
	const trans = response => {

		let buttons = form.querySelectorAll('button:is(.cf7mls_back, .cf7mls_next)'),

			select = form.querySelector('select[name="pilot-country"]'), option;


		//add countries
		response.data.countries.split(', ').forEach( country => {

			( option = select.appendChild( document.createElement('option') ) ).textContent = country;

			option.value = country;

		});


		//add file labels
		Object.entries( response.labels ).forEach( ([key, value]) => {

			const wrapper = form.querySelector(`input[type="file"][name="${key}"]`)?.closest('.wpcf7-form-control-wrap');

			if( wrapper ) {

				wrapper.setAttribute('data-label', value );

				wrapper.classList.add('input-file-parent');

			}

		} );


		//pt
		if( !config.lang.match(/pt/) ) {

			for( n in response.dictionary_pt2en ) {

				const reg = new RegExp( `^${n}$` );

				buttons.forEach( button => {

					const node = button.childNodes[0];

					if( node.nodeName.match(/text/) && node.nodeValue.match( reg ) ) {

						 node.nodeValue = response.dictionary_pt2en[n];

					}

				});

			}

		}
	
	};
	 
	//fetch language data for select / buttons
	if( config.fibaq ) {

		trans( config.fibaq );

	} else {

		fetch( `${ config.restURL }pb/v1/lang/?lang=${ config.lang }`).then(response => response.json()).then(response => trans( response ) );

	}

	//add attribute (using placeholder in date in cf7 is not possible)
	form.querySelectorAll('input[type="date"]').forEach( input => input.setAttribute('data-placeholder', input.name.replace(/(.+-)/, '')) );


	//MULTI STEP FORM FIXING
	//sometimes the invalid tip is append incorrectly appended outside of the 'wrapper'.
	//this observers adds it back in.
	form.querySelectorAll('p > span.wpcf7-form-control-wrap:only-child').forEach( element => {

		const obs = new MutationObserver( entries => {

			if( element.parentNode.children.length > 1 ) {

				element.parentNode.children[0].append( element.parentNode.children[1] );

			}

		});

		obs.observe( element.parentNode, {childList: true, subtree: true} );

	});

}
