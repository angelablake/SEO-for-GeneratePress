( function () {
	'use strict';

	const identityType = document.querySelector( '#seogp-identity-type' );
	const personRows = document.querySelectorAll( '.seogp-person-only' );
	const photoId = document.querySelector( '#seogp-person-photo-id' );
	const photoPreview = document.querySelector( '.seogp-person-photo__preview' );
	const selectPhoto = document.querySelector( '.seogp-select-photo' );
	const removePhoto = document.querySelector( '.seogp-remove-photo' );

	function updatePersonFields() {
		personRows.forEach( ( row ) => { row.hidden = ! identityType || identityType.value !== 'person'; } );
	}

	if ( identityType ) {
		identityType.addEventListener( 'change', updatePersonFields );
		updatePersonFields();
	}

	if ( selectPhoto && window.wp && wp.media ) {
		selectPhoto.addEventListener( 'click', function () {
			const frame = wp.media( { title: seogpAdmin.choosePhoto, button: { text: seogpAdmin.usePhoto }, library: { type: 'image' }, multiple: false } );
			frame.on( 'select', function () {
				const image = frame.state().get( 'selection' ).first().toJSON();
				const previewUrl = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;
				photoId.value = image.id;
				photoPreview.innerHTML = '<img src="' + previewUrl.replace( /"/g, '&quot;' ) + '" alt="">';
				selectPhoto.textContent = seogpAdmin.replaceButton;
				removePhoto.hidden = false;
			} );
			frame.open();
		} );
	}

	if ( removePhoto ) {
		removePhoto.addEventListener( 'click', function () {
			photoId.value = '';
			photoPreview.innerHTML = '';
			selectPhoto.textContent = seogpAdmin.chooseButton;
			removePhoto.hidden = true;
		} );
	}

	const profileList = document.querySelector( '.seogp-profile-urls' );
	const addProfile = document.querySelector( '.seogp-add-profile' );
	if ( profileList && addProfile ) {
		addProfile.addEventListener( 'click', function () {
			const row = document.createElement( 'div' );
			row.className = 'seogp-profile-url';
			const input = document.createElement( 'input' );
			input.type = 'url'; input.className = 'regular-text'; input.name = profileList.dataset.name;
			input.placeholder = 'https://example.com/profile'; input.inputMode = 'url';
			const remove = document.createElement( 'button' );
			remove.type = 'button'; remove.className = 'button-link-delete seogp-remove-profile'; remove.textContent = seogpAdmin.remove;
			row.append( input, remove ); profileList.append( row ); input.focus();
		} );
		profileList.addEventListener( 'click', function ( event ) {
			if ( event.target.classList.contains( 'seogp-remove-profile' ) ) event.target.closest( '.seogp-profile-url' ).remove();
		} );
	}
}() );
